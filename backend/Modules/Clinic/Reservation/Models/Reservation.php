<?php

namespace Modules\Clinic\Reservation\Models;

use App\Models\MedicalAnalysis;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use App\Models\Ray;
use App\Models\Payment;
use Modules\Clinic\Prescription\Models\Prescription;
use App\Models\Clinic;
use App\Models\ModuleService;
use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Modules\Clinic\ChronicDisease\Models\ChronicDisease;
use App\Models\Shared\Patient;
use Modules\Clinic\Doctor\Models\Doctor;


class Reservation extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'reservations';

    protected $primaryKey = 'id';

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'

    ];

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'doctor_id',
        'parent_id',
        'type',
        'reservation_number',
        'first_diagnosis',
        'final_diagnosis',
        // 'type',
        'cost',
        'payment',
        'date',
        'status',
        'acceptance',
        'month',
        'slot'
    ];

    protected static function booted()
    {

        static::addGlobalScope(new ClinicScope);
    }


    public function getImagesAttribute()
    {
        return $this->getMedia('reservation_attachments')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }



    // Inverse of one-to-many (One Reservation belongs to one Patient)
    // belongTo() come with one to one relationship
    // every reservation belong to one patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function rays()
    {
        return $this->hasMany(
            Ray::class,
            'reservation_id',
            'id'
        );
    }

    public function medicalAnalysis()
    {
        return $this->hasMany(
            MedicalAnalysis::class,
            'reservation_id',
            'id'
        );
    }

    public function chronicDisease()
    {
        return $this->hasMany(
            ChronicDisease::class,
            'reservation_id',
            'id',
        );
    }

    public function glassesDistance()
    {
        return $this->hasMany(
            GlassesDistance::class,
            'reservation_id',
            'id',
        );
    }

    public function prescription()
    {
        return $this->hasMany(
            Prescription::class,
            'reservation_id',
            'id',
        );
    }

    public function drugs()
    {
        return $this->hasMany(
            \Modules\Clinic\Prescription\Models\Drug::class,
            'reservation_id',
            'id',
        );
    }

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
        return $this->belongsTo(
            Doctor::class,
            'doctor_id',
            'id',
        );
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function sessions()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopePatient($query)
    {
        return $query->where('patient_id', auth()->id());
    }

    public function scopePaid($query)
    {
        return $query->where('payment', 'paid');
    }

    public function services()
    {
        return $this->morphMany(ModuleService::class, 'module')
            ->with(['service' => function ($query) {
                $query->withoutGlobalScopes();
            }]);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable')->orderByDesc('payment_date')->orderByDesc('id');
    }


}