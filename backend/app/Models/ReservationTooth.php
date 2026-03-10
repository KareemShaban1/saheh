<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;

class ReservationTooth extends Model
{
    use HasFactory;

    protected $table = 'reservation_teeth';

    protected $fillable = [
        'reservation_id',
        'patient_id',
        'clinic_id',
        'tooth_number',
        'tooth_note',
        'general_note',
        'next_session_plan',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
