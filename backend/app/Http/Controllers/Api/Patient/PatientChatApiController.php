<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Clinic\Chat\Models\Chat;
use Modules\Clinic\Chat\Models\Message;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\User\Models\User;

class PatientChatApiController extends Controller
{
    use ApiHelperTrait;

    public function contacts()
    {
        $patient = $this->authPatient();

        $orgRows = DB::table('patient_organization')
            ->where('patient_id', $patient->id)
            ->where('assigned', 1)
            ->whereNull('deleted_at')
            ->get(['organization_id', 'organization_type', 'doctor_id']);

        $users = collect();
        foreach ($orgRows->groupBy(fn ($row) => $row->organization_type . '#' . $row->organization_id) as $rows) {
            $first = $rows->first();
            $orgUsers = User::query()
                ->where('organization_id', $first->organization_id)
                ->where('organization_type', $first->organization_type)
                ->get(['id', 'name', 'email', 'job_title'])
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'subtitle' => $u->job_title ?: $u->email,
                ]);
            $users = $users->concat($orgUsers);
        }

        $doctorIds = $orgRows->pluck('doctor_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($doctorIds->isNotEmpty()) {
            $doctorUsers = Doctor::query()
                ->whereIn('id', $doctorIds)
                ->with('user:id,name,email')
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->user?->id,
                    'name' => $d->user?->name,
                    'subtitle' => 'Doctor',
                ])
                ->filter(fn ($u) => !empty($u['id']));
            $users = $users->concat($doctorUsers);
        }

        $users = $users
            ->unique('id')
            ->values()
            ->map(fn ($u) => [
                'id' => (int) $u['id'],
                'type' => 'user',
                'name' => (string) ($u['name'] ?? 'Unknown user'),
                'subtitle' => (string) ($u['subtitle'] ?? ''),
            ])
            ->values();

        return $this->returnJSON(['users' => $users], 'Patient chat contacts', 'success');
    }

    public function conversations()
    {
        $patient = $this->authPatient();
        $data = Chat::query()
            ->with(['user:id,name', 'messages' => fn ($q) => $q->latest('id')->take(1)])
            ->where('patient_id', $patient->id)
            ->latest('updated_at')
            ->get()
            ->map(function ($chat) use ($patient) {
                $last = $chat->messages->first();
                return [
                    'id' => $chat->id,
                    'target_type' => 'user',
                    'target_id' => (int) $chat->user_id,
                    'title' => $chat->user?->name ?: 'Unknown user',
                    'last_message' => $last?->message,
                    'updated_at' => optional($last?->created_at ?: $chat->updated_at)->format('Y-m-d H:i'),
                    'unread' => (int) Message::query()
                        ->where('chat_id', $chat->id)
                        ->where('seen', false)
                        ->where(function ($q) use ($patient) {
                            $q->where('sender_id', '!=', $patient->id)
                                ->orWhere('sender_type', '!=', Patient::class);
                        })
                        ->count(),
                ];
            })
            ->values();

        return $this->returnJSON($data, 'Patient chat conversations', 'success');
    }

    public function openConversation(Request $request)
    {
        $patient = $this->authPatient();
        $validated = $request->validate([
            'target_type' => 'required|in:user',
            'target_id' => 'required|integer|min:1|exists:users,id',
        ]);

        $user = User::query()->findOrFail((int) $validated['target_id']);
        $isAllowed = DB::table('patient_organization')
            ->where('patient_id', $patient->id)
            ->where('assigned', 1)
            ->whereNull('deleted_at')
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->exists();
        if (!$isAllowed) {
            abort(403, 'You can only message users from your assigned organizations.');
        }

        $chat = Chat::firstOrCreate([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'peer_user_id' => null,
        ]);

        return $this->returnJSON(['chat_id' => $chat->id], 'Conversation opened', 'success');
    }

    public function messages($chatId)
    {
        $patient = $this->authPatient();
        $chat = Chat::query()
            ->where('id', (int) $chatId)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $data = Message::query()
            ->where('chat_id', $chat->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'chat_id' => $m->chat_id,
                'sender_id' => $m->sender_id,
                'sender_type' => $m->sender_type,
                'is_mine' => (int) $m->sender_id === (int) $patient->id && $m->sender_type === Patient::class,
                'message' => $m->message,
                'image_url' => $m->getFirstMediaUrl('message_image') ?: null,
                'seen' => (bool) $m->seen,
                'created_at' => optional($m->created_at)->format('Y-m-d H:i:s'),
            ])
            ->values();

        Message::query()
            ->where('chat_id', $chat->id)
            ->where(function ($q) use ($patient) {
                $q->where('sender_id', '!=', $patient->id)
                    ->orWhere('sender_type', '!=', Patient::class);
            })
            ->update(['seen' => true]);

        return $this->returnJSON($data, 'Messages', 'success');
    }

    public function sendMessage(Request $request, $chatId)
    {
        $patient = $this->authPatient();
        $chat = Chat::query()
            ->where('id', (int) $chatId)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $validated = $request->validate([
            'message' => 'nullable|string|max:5000',
            'image' => 'nullable|file|max:10240',
        ]);
        if (empty(trim((string) ($validated['message'] ?? ''))) && !$request->hasFile('image')) {
            abort(422, 'Message text or image is required.');
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $patient->id,
            'sender_type' => Patient::class,
            'message' => trim((string) ($validated['message'] ?? '')),
            'seen' => false,
        ]);

        if ($request->hasFile('image')) {
            $message->addMedia($request->file('image'))->toMediaCollection('message_image');
        }

        return $this->returnJSON([
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'is_mine' => true,
            'message' => $message->message,
            'image_url' => $message->getFirstMediaUrl('message_image') ?: null,
            'seen' => (bool) $message->seen,
            'created_at' => optional($message->created_at)->format('Y-m-d H:i:s'),
        ], 'Message sent', 'success');
    }

    private function authPatient(): Patient
    {
        $patient = request()->user();
        if (!$patient || !($patient instanceof Patient)) {
            abort(401, 'Unauthenticated');
        }
        return $patient;
    }
}
