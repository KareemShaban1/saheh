<?php

namespace Modules\Clinic\Doctor\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\User\Models\User;
use Modules\Clinic\User\Models\UserDoctor;
use App\Models\Clinic;
use App\Models\Specialty;
use App\Models\Service;
use App\Models\Shared\PatientReview;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clinic_id',
        'phone',
        'specialty_id',
        'certifications',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ClinicScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function specialty()
    {
        // Explicit FQCN (Linux is case-sensitive; avoids casing issues)
        return $this->belongsTo(\App\Models\Specialty::class);
    }

    public function userDoctor()
    {
        return $this->hasMany(UserDoctor::class);
    }


    public function Services()
    {
        return $this->hasMany(
            Service::class,
            'doctor_id',
            'id',
        );
    }


    public function ServicesWithoutScope()
{
    return $this->hasMany(Service::class, 'doctor_id', 'id')
    ->where('type','main')
    ->withoutGlobalScopes();
}



    public function scopeVisibleTo($query, $user)
    {
        $query->with(['user']);

        $userRole = $user->roles->first()?->name;

        if ($userRole !== 'clinic-admin') {
            $doctorIds = \Modules\Clinic\User\Models\UserDoctor::where('user_id', $user->id)->pluck('doctor_id');
            $query->whereHas('userDoctor', fn($q) => $q->whereIn('doctor_id', $doctorIds));
        }

        return $query;
    }


    public function reviews()
    {
        return $this->hasMany(PatientReview::class);
    }
}
