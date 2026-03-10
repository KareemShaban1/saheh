<?php

namespace Modules\Clinic\ReservationNumber\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;

class ReservationNumber extends Model
{
    protected $table = 'reservation_numbers';
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'reservation_date',
        'num_of_reservations'
    ];


    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class)->with('user');
    }
}
