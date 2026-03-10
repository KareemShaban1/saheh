<?php

namespace App\Http\Controllers\Backend\RadiologyCenter;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use Modules\Clinic\Chat\Models\Chat;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    //

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'message' => 'required|string'
        ]);

        $chat = Chat::firstOrCreate([
            'user_id' => auth()->id(),
            'patient_id' => $request->patient_id
        ]);

        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'sender_type' => auth()->guard()->getName() === 'api' ? 'App\\Models\\Patient' : 'Modules\\Clinic\\User\\Models\\User',
            'message' => $request->message,
        ]);

        // Upload image if exists
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $message->addMedia($request->file('image'))->toMediaCollection('message_image');
            $imageUrl = $message->getFirstMediaUrl('message_image');
        }

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
            'image_url' => $imageUrl
        ]);
    }
}