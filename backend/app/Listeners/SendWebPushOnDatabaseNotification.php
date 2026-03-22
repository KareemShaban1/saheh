<?php

namespace App\Listeners;

use App\Services\WebPushService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class SendWebPushOnDatabaseNotification
{
    public function __construct(
        protected WebPushService $webPush
    ) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $this->webPush->isConfigured()) {
            return;
        }

        $notifiable = $event->notifiable;
        if (! $notifiable instanceof Model) {
            return;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        try {
            $data = $event->notification->toDatabase($notifiable);
        } catch (\Throwable $e) {
            Log::debug('webpush_to_database_failed', ['error' => $e->getMessage()]);

            return;
        }

        if (! is_array($data)) {
            return;
        }

        $this->webPush->sendToModel($notifiable, $data);
    }
}
