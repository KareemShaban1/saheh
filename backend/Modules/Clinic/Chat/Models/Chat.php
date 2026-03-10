<?php

namespace Modules\Clinic\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\User\Models\User;
use App\Models\Shared\Patient;
use Modules\Clinic\Chat\Models\Message;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'patient_id',
        'peer_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function peerUser()
    {
        return $this->belongsTo(User::class, 'peer_user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}