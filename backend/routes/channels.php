<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::routes([
    'middleware' => ['auth:web,medical_laboratory,radiology_center']
]);


Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    \Log::info('Auth attempt', [
        'authUser' => $user?->id,
        'type' => get_class($user),
        'chatId' => $chatId,
    ]);
    $chat = Chat::find($chatId);
    return $chat && (
        ($user instanceof Modules\Clinic\User\Models\User && $chat->user_id === $user->id) ||
        ($user instanceof App\Models\Shared\Patient && $chat->patient_id === $user->id)
    );
});

