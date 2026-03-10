<?php

namespace Modules\Clinic\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Modules\Clinic\Chat\Models\Chat;

class Message extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'sender_type',
        'message',
        'seen',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender() {
        return $this->morphTo(__FUNCTION__, 'sender_type', 'sender_id');
      }
}