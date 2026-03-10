<?php

namespace Modules\Clinic\ChronicDisease\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Scopes\ClinicScope;
use App\Models\Clinic;
use App\Models\Shared\Patient;

class ChronicDisease extends Model
{
    use HasFactory;

    protected $fillable = ['name','measure','date','notes','patient_id','reservation_id','clinic_id'];

    protected static function booted()
    {

        static::addGlobalScope(new ClinicScope);
    }

    public function reservation()
    {
        return $this->belongsTo(
            Reservation::class,
            'id',
            'id'
        );
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopePatient($query)
    {
        return $query->where('patient_id', auth()->id());
    }
}
