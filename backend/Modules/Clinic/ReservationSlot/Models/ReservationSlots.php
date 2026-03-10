<?php

namespace Modules\Clinic\ReservationSlot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;

class ReservationSlots extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'total_reservations'
    ];

    public function clinic()
    {
        return $this->belongsTo(
            Clinic::class,
            'clinic_id',
            'id',
        );
    }
       public function doctor()
    {
        return $this->belongsTo(Doctor::class,'doctor_id','id')->with('user');
    }
}
