<?php

namespace App\Http\Controllers\FrontApis;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        $key = (string) config('webpush.public_key', env('VAPID_PUBLIC_KEY'));
        if ($key === '') {
            return response()->json([
                'configured' => false,
                'public_key' => null,
                'message' => 'Web Push is not configured (missing VAPID keys).',
            ]);
        }

        return response()->json([
            'configured' => true,
            'public_key' => $key,
        ]);
    }

    public function subscribeOrganization(Request $request): JsonResponse
    {
        $user = Auth::guard('organization_api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->storeSubscription($request, $user);
    }

    public function subscribeAdmin(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin_api')->user();
        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->storeSubscription($request, $admin);
    }

    public function subscribePatient(Request $request): JsonResponse
    {
        $patient = Auth::guard('patient_api')->user();
        if (! $patient) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->storeSubscription($request, $patient);
    }

    public function unsubscribeOrganization(Request $request): JsonResponse
    {
        $user = Auth::guard('organization_api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->removeSubscription($request, $user);
    }

    public function unsubscribeAdmin(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin_api')->user();
        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->removeSubscription($request, $admin);
    }

    public function unsubscribePatient(Request $request): JsonResponse
    {
        $patient = Auth::guard('patient_api')->user();
        if (! $patient) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->removeSubscription($request, $patient);
    }

    private function storeSubscription(Request $request, object $subscribable): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'subscribable_type' => $subscribable->getMorphClass(),
                'subscribable_id' => $subscribable->getKey(),
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $request->input('content_encoding', 'aesgcm'),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ]
        );

        return response()->json(['status' => true]);
    }

    private function removeSubscription(Request $request, object $subscribable): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        PushSubscription::query()
            ->where('endpoint', $validated['endpoint'])
            ->where('subscribable_type', $subscribable->getMorphClass())
            ->where('subscribable_id', $subscribable->getKey())
            ->delete();

        return response()->json(['status' => true]);
    }
}
