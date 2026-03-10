<?php

namespace Modules\Clinic\Chat\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\Chat\Models\Chat;
use App\Models\Shared\Patient;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    //
    public function index()
    {
        $chats = Chat::with('messages')
            ->where('user_id', auth()->id())
            ->get();
        $patients = Patient::all();
        return view('backend.dashboards.clinic.pages.chats.index', compact('chats', 'patients'));
    }

    public function getChatByPatient(Request $request, $patientId)
    {
        $chat = Chat::with(['messages.media'])
            ->where('user_id', auth()->id())
            ->where('patient_id', $patientId)
            ->first();

        if (!$chat) {
            $chat = Chat::create([
                'user_id' => auth()->id(),
                'patient_id' => $patientId
            ]);
        }

        if ($request->ajax()) {
            $messages = $chat->messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'chat_id' => $msg->chat_id,
                    'sender_id' => $msg->sender_id,
                    'sender_type' => $msg->sender_type,
                    'created_at' => $msg->created_at,
                    'image_url' => $msg->getFirstMediaUrl('message_image') ?: null,
                ];
            });

            return response()->json([
                'chat' => $chat,
                'messages' => $messages
            ]);
        }

        $chats = Chat::with('messages')->where('user_id', auth()->id())->get();
        return view('backend.dashboards.clinic.pages.chats.index', compact('chats'));
    }
}