<?php

namespace App\Http\Controllers\FrontApis;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Clinic\Chat\Models\Chat;
use Modules\Clinic\Chat\Models\Message;
use Modules\Clinic\User\Models\User;

class OrganizationChatApiController extends Controller
{
    use ApiHelperTrait;

    public function contacts()
    {
        $auth = $this->authUser();
        $orgClass = $this->resolveOrganizationClass((string) $auth->organization_type);

        $users = User::query()
            ->where('organization_id', $auth->organization_id)
            ->where('organization_type', $auth->organization_type)
            ->where('id', '!=', $auth->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'job_title'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'type' => 'user',
                'name' => $u->name,
                'subtitle' => $u->job_title ?: $u->email,
            ])
            ->values();

        $patientIds = DB::table('patient_organization')
            ->where('organization_id', $auth->organization_id)
            ->where('organization_type', $orgClass)
            ->where('assigned', 1)
            ->whereNull('deleted_at')
            ->pluck('patient_id')
            ->unique()
            ->values();

        $patients = Patient::query()
            ->whereIn('id', $patientIds)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'patient_code'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'type' => 'patient',
                'name' => $p->name,
                'subtitle' => trim(($p->patient_code ? ('#' . $p->patient_code . ' ') : '') . ($p->phone ?: '')),
            ])
            ->values();

        return $this->returnJSON([
            'users' => $users,
            'patients' => $patients,
        ], 'Chat contacts', 'success');
    }

    public function conversations()
    {
        $auth = $this->authUser();
        $query = Chat::query()
            ->with([
                'user:id,name',
                'peerUser:id,name',
                'patient:id,name',
                'messages' => fn ($q) => $q->latest('id')->take(1),
            ])
            ->where(function ($q) use ($auth) {
                $q->where('user_id', $auth->id)
                    ->orWhere('peer_user_id', $auth->id);
            })
            ->latest('updated_at');

        $data = $query->get()->map(function ($chat) use ($auth) {
            $isUserConversation = !is_null($chat->peer_user_id);
            $peer = $isUserConversation
                ? ((int) $chat->user_id === (int) $auth->id ? $chat->peerUser : $chat->user)
                : null;
            $last = $chat->messages->first();
            $lastImage = $last?->getFirstMediaUrl('message_image') ?: null;
            $lastVoice = $last?->getFirstMediaUrl('message_voice') ?: null;
            $lastMessageText = trim((string) ($last?->message ?? ''));
            if ($lastMessageText === '' && $lastVoice) {
                $lastMessageText = 'Voice message';
            } elseif ($lastMessageText === '' && $lastImage) {
                $lastMessageText = 'Image';
            }
            return [
                'id' => $chat->id,
                'target_type' => $isUserConversation ? 'user' : 'patient',
                'target_id' => $isUserConversation ? ($peer?->id) : ($chat->patient?->id),
                'title' => $isUserConversation ? ($peer?->name ?: 'Unknown user') : ($chat->patient?->name ?: 'Unknown patient'),
                'last_message' => $lastMessageText,
                'updated_at' => optional($last?->created_at ?: $chat->updated_at)->format('Y-m-d H:i'),
                'unread' => (int) Message::query()
                    ->where('chat_id', $chat->id)
                    ->where('seen', false)
                    ->where('sender_id', '!=', $auth->id)
                    ->count(),
            ];
        })->values();

        return $this->returnJSON($data, 'Chat conversations', 'success');
    }

    public function openConversation(Request $request)
    {
        $auth = $this->authUser();
        $validated = $request->validate([
            'target_type' => 'required|in:user,patient',
            'target_id' => 'required|integer|min:1',
        ]);

        if ($validated['target_type'] === 'user') {
            $target = User::query()
                ->where('id', (int) $validated['target_id'])
                ->where('organization_id', $auth->organization_id)
                ->where('organization_type', $auth->organization_type)
                ->firstOrFail();

            $ids = collect([(int) $auth->id, (int) $target->id])->sort()->values();
            $chat = Chat::firstOrCreate([
                'user_id' => (int) $ids[0],
                'peer_user_id' => (int) $ids[1],
                'patient_id' => null,
            ]);
        } else {
            $orgClass = $this->resolveOrganizationClass((string) $auth->organization_type);
            $assigned = DB::table('patient_organization')
                ->where('patient_id', (int) $validated['target_id'])
                ->where('organization_id', $auth->organization_id)
                ->where('organization_type', $orgClass)
                ->where('assigned', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (!$assigned) {
                abort(403, 'Patient is not assigned to this organization.');
            }

            $chat = Chat::firstOrCreate([
                'user_id' => (int) $auth->id,
                'patient_id' => (int) $validated['target_id'],
                'peer_user_id' => null,
            ]);
        }

        return $this->returnJSON(['chat_id' => $chat->id], 'Conversation opened', 'success');
    }

    public function messages($chatId)
    {
        $auth = $this->authUser();
        $chat = $this->authorizedChat((int) $chatId, (int) $auth->id);

        $data = Message::query()
            ->where('chat_id', $chat->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'chat_id' => $m->chat_id,
                'sender_id' => $m->sender_id,
                'sender_type' => $m->sender_type,
                'is_mine' => (int) $m->sender_id === (int) $auth->id && $m->sender_type === User::class,
                'message' => $m->message,
                'image_url' => $m->getFirstMediaUrl('message_image') ?: null,
                'voice_url' => $m->getFirstMediaUrl('message_voice') ?: null,
                'seen' => (bool) $m->seen,
                'created_at' => optional($m->created_at)->format('Y-m-d H:i:s'),
            ])
            ->values();

        Message::query()
            ->where('chat_id', $chat->id)
            ->where('sender_id', '!=', $auth->id)
            ->update(['seen' => true]);

        return $this->returnJSON($data, 'Messages', 'success');
    }

    public function sendMessage(Request $request, $chatId)
    {
        $auth = $this->authUser();
        $chat = $this->authorizedChat((int) $chatId, (int) $auth->id);

        $validated = $request->validate([
            'message' => 'nullable|string|max:5000',
            'image' => 'nullable|file|max:10240',
            'voice' => 'nullable|file|mimes:mp3,wav,m4a,aac,ogg,webm|max:20480',
        ]);

        if (
            empty(trim((string) ($validated['message'] ?? '')))
            && !$request->hasFile('image')
            && !$request->hasFile('voice')
        ) {
            abort(422, 'Message text, image, or voice is required.');
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $auth->id,
            'sender_type' => User::class,
            'message' => trim((string) ($validated['message'] ?? '')),
            'seen' => false,
        ]);

        if ($request->hasFile('image')) {
            $message->addMedia($request->file('image'))->toMediaCollection('message_image');
        }
        if ($request->hasFile('voice')) {
            $message->addMedia($request->file('voice'))->toMediaCollection('message_voice');
        }

        return $this->returnJSON([
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'is_mine' => true,
            'message' => $message->message,
            'image_url' => $message->getFirstMediaUrl('message_image') ?: null,
            'voice_url' => $message->getFirstMediaUrl('message_voice') ?: null,
            'seen' => (bool) $message->seen,
            'created_at' => optional($message->created_at)->format('Y-m-d H:i:s'),
        ], 'Message sent', 'success');
    }

    private function authUser()
    {
        $user = request()->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }
        return $user;
    }

    private function authorizedChat(int $chatId, int $userId): Chat
    {
        $chat = Chat::query()->findOrFail($chatId);
        if ((int) $chat->user_id !== $userId && (int) ($chat->peer_user_id ?? 0) !== $userId) {
            abort(403, 'You are not allowed to access this chat.');
        }
        return $chat;
    }

    private function resolveOrganizationClass(string $organizationType): string
    {
        return match ($organizationType) {
            Clinic::class => Clinic::class,
            MedicalLaboratory::class => MedicalLaboratory::class,
            RadiologyCenter::class => RadiologyCenter::class,
            default => $organizationType,
        };
    }
}
