<?php

namespace App\Services;

use App\Models\PushSubscription;
use Base64Url\Base64Url;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function isConfigured(): bool
    {
        $pub = (string) config('webpush.public_key', '');
        $priv = (string) config('webpush.private_key', '');

        return $pub !== '' && $priv !== '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToModel(Model $notifiable, array $data): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, PushSubscription> $subs */
        $subs = $notifiable->pushSubscriptions()->get();
        if ($subs->isEmpty()) {
            return;
        }

        $title = (string) ($data['title'] ?? 'Notification');
        $body = (string) ($data['body'] ?? $data['message'] ?? '');
        $relative = (string) ($data['url'] ?? $data['action_url'] ?? '/');
        $url = $this->absoluteFrontendUrl($relative);
        $tag = (string) ($data['tag'] ?? ($data['event'] ?? 'app') . '-' . ($data['reservation_id'] ?? $data['id'] ?? uniqid('', true)));

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'tag' => $tag,
        ]);
        if ($payload === false) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('webpush.subject'),
                    'publicKey' => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ]);
        } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), '[VAPID]')) {
                $pub = (string) config('webpush.public_key');
                $priv = (string) config('webpush.private_key');
                Log::error('webpush_invalid_vapid', [
                    'message' => $e->getMessage(),
                    'decoded_public_bytes' => self::vapidDecodedLength($pub),
                    'decoded_private_bytes' => self::vapidDecodedLength($priv),
                    'hint' => 'Regenerate with: npx web-push generate-vapid-keys. Expect public→65 bytes, private→32 bytes decoded. Do not swap keys; one line per .env value (no extra quotes). Run: php artisan config:clear',
                ]);

                return;
            }

            throw $e;
        }

        foreach ($subs as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth' => $sub->auth_token,
                    ],
                    'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                ]);
            } catch (\Throwable $e) {
                Log::warning('webpush_invalid_subscription', ['id' => $sub->id, 'error' => $e->getMessage()]);
                $sub->delete();

                continue;
            }

            $report = $webPush->sendOneNotification($subscription, $payload);

            if ($report->isSuccess()) {
                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $sub->delete();

                continue;
            }

            Log::debug('webpush_send_failed', [
                'subscription_id' => $sub->id,
                'reason' => $report->getReason(),
            ]);
        }
    }

    /**
     * @return int|null Byte length after Base64Url decode, or null if empty/invalid.
     */
    private static function vapidDecodedLength(string $key): ?int
    {
        if ($key === '') {
            return null;
        }

        try {
            return strlen(Base64Url::decode($key));
        } catch (\Throwable) {
            return null;
        }
    }

    private function absoluteFrontendUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            $path = '/';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = (string) config('webpush.frontend_url', '');

        return $base . '/' . ltrim($path, '/');
    }
}
