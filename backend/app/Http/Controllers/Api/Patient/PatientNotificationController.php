<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;

class PatientNotificationController extends Controller
{
    use ApiHelperTrait;

    public function index(Request $request)
    {
        /** @var Patient $patient */
        $patient = auth('patient_api')->user();
        if (! $patient) {
            return $this->returnWrong('Unauthenticated', [], 401);
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        $paginated = $patient->notifications()
            ->latest()
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($notification) {
            $payload = is_array($notification->data) ? $notification->data : [];

            return [
                'id' => (string) $notification->id,
                'type' => $payload['event'] ?? $payload['type'] ?? $notification->type,
                'module' => (string) ($payload['module'] ?? 'general'),
                'event' => $payload['event'] ?? null,
                'title' => $payload['title'] ?? 'Notification',
                'message' => $payload['message'] ?? $payload['body'] ?? $payload['content'] ?? '',
                'priority' => $payload['priority'] ?? 'medium',
                'action_url' => $payload['action_url'] ?? $payload['url'] ?? null,
                'is_read' => $notification->read_at !== null,
                'read_at' => optional($notification->read_at)->toDateTimeString(),
                'created_at' => optional($notification->created_at)->toDateTimeString(),
            ];
        })->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Notifications', 'success');
    }

    public function markAsRead(Request $request, string $id)
    {
        /** @var Patient $patient */
        $patient = auth('patient_api')->user();
        if (! $patient) {
            return $this->returnWrong('Unauthenticated', [], 401);
        }

        $updated = $patient->notifications()
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->returnJSON([
            'updated' => (bool) $updated,
        ], $updated ? 'Marked as read' : 'Already read or not found', 'success');
    }
}
