<?php

namespace App\Models\Shared;

use App\Models\Scopes\ClinicScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Ray;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use Modules\Clinic\Prescription\Models\Prescription;
use App\Models\MedicalAnalysis;
use Modules\Clinic\ChronicDisease\Models\ChronicDisease;
use App\Models\OnlineReservation;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\PatientReview;

class Patient extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use SoftDeletes;

    protected $table = 'patients';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $fillable = [
        'name',
        'age',
        'address',
        'email',
        'password',
        'phone',
        'whatsapp_number',
        'blood_group',
        'patient_code',
        'gender',
        // 'clinic_id',
        'height',
        'weight',
        // 'image'
    ];

    protected static function booted()
    {

        // while creating order make order number take next available number
        static::creating(function (Patient $patient) {
            //20230001 - 20230002
            $patient->patient_code = Patient::getNextPatientCodeNumber();
        });

        // static::addGlobalScope(new ClinicScope);
    }

    public static function getNextPatientCodeNumber()
    {
        // SELECT MAX(number) FROM patients
        $year = Carbon::now()->year;
        $number = Patient::whereYear('created_at', $year)->max('patient_code');


        // if there is number in this year add 1 to this number
        if ($number) {
            return $number + 1;
        }
        // if not return 0001
        return $year . '0001';
    }


    public function reservations()
    {
        // $this refer to patient object
        // One-to-Many (One patient has many reservations)
        return $this->hasMany(Reservation::class, 'patient_id', 'id');
    }

    public function rays()
    {
        return $this->hasMany(
            Ray::class,
            'patient_id',
            'id',
        );
    }

    public function glassesDistance()
    {
        return $this->hasMany(
            GlassesDistance::class,
            'patient_id',
            'id',
        );
    }

    public function medicalAnalysis()
    {
        return $this->hasMany(
            MedicalAnalysis::class,
            'patient_id',
            'id',
        );
    }

    public function chronicDisease()
    {
        return $this->hasMany(
            ChronicDisease::class,
            'patient_id',
            'id',
        );
    }

    public function prescription()
    {
        return $this->hasMany(
            Prescription::class,
            'patient_id',
            'id',
        );
    }

    public function onlineReservations()
    {
        return $this->hasMany(
            OnlineReservation::class,
            'patient_id',
            'id',
        );
    }

    public function clinics()
    {
        return $this->morphedByMany(
            Clinic::class,         // model you want back
            'organization',        // morph name → columns are organization_id / organization_type
            'patient_organization', // pivot table
            'patient_id',          // FK to patients
            'organization_id'      // FK to clinics
        )->with('governorate', 'city', 'area');
    }

    public function medicalLaboratories()
    {
        return $this->morphedByMany(
            MedicalLaboratory::class,
            'organization',
            'patient_organization',
            'patient_id',
            'organization_id'
        );
    }


    public function radiologyCenters()
    {
        return $this->morphedByMany(
            RadiologyCenter::class,
            'organization',
            'patient_organization',
            'patient_id',
            'organization_id',
        );
    }

    public function scopeClinic($query)
    {

        return $query->whereHas('clinics', function ($q) {
            $q->where('organization_id', Auth::user()->organization_id)
                ->where('assigned', 1);
        });
    }


    public function scopeMedicalLaboratory($query)
    {
        return $query->whereHas('medicalLaboratories', function ($q) {
            $q->where('organization_id', Auth::user()->organization_id)
                ->where('assigned', 1);
        });
    }

    public function scopeRadiologyCenter($query)
    {
        return $query->whereHas('radiologyCenters', function ($q) {
            $q->where('organization_id', Auth::user()->organization_id)
                ->where('assigned', 1);
        });
    }

    public function scopeVisibleTo($query, $user)
    {
        $query->with(['reservations', 'doctors.user'])->clinic();

        $userRole = $user->roles->first()?->name;

        if ($userRole !== 'clinic-admin') {
            $doctorIds = \Modules\Clinic\User\Models\UserDoctor::where('user_id', $user->id)->pluck('doctor_id');
            $query->whereHas('doctors', fn($q) => $q->whereIn('doctor_id', $doctorIds));
        }

        return $query;
    }


    public function organization()
    {
        return $this->morphTo();
    }

    public function doctors()
    {
        return $this->belongsToMany(
            Doctor::class,
            'patient_organization',
            'patient_id', // Foreign key on pivot table pointing to Patient
            'doctor_id'   // Foreign key on pivot table pointing to Doctor
        )->whereNotNull('doctor_id')
            ->withTimestamps()
            ->withPivot(['organization_type', 'organization_id', 'assigned']) // if you need these
            ->wherePivot('assigned', true)
            ->whereNull('patient_organization.deleted_at'); // Handle soft deletes on pivot
    }

    public function clinicDoctors()
    {
        return $this->doctors()->wherePivot('organization_type', Clinic::class);
    }

    public function clinicReview(){

        $this->hasOne(PatientReview::class ,'patient_id');
    }

}
