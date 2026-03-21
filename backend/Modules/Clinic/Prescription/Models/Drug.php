<?php

namespace Modules\Clinic\Prescription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;

class Drug extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'dose',
        'frequency',
        'period',
        'notes',
        'reservation_id',
        'patient_id',
        'clinic_id',
        'doctor_id',
    ];
    protected $table = 'drugs';
    // every drug belong to one reservation
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
