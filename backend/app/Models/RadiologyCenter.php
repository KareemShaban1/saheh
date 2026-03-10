<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shared\Patient;
use App\Models\Shared\PatientReview;
use Modules\Clinic\User\Models\User;


class RadiologyCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'address',
        'phone',
        'email',
        'governorate_id',
        'city_id',
        'area_id',
        'website',
        'domain',
        'database',
        'description',
        'logo',
        'status',
    ];

    public function patients(){

        return $this->belongsToMany(
            Patient::class,
            'patient_organization',
            'organization_id',
            'patient_id',
        );

    }

    public function governorate(){
        return $this->belongsTo(Governorate::class);
    }

    public function city(){
        return $this->belongsTo(City::class);
    }

    public function area(){
        return $this->belongsTo(Area::class);
    }

    public function users()
    {
        return $this->morphMany(User::class, 'organization');
    }

    public function activationTokens()
    {
        return $this->morphMany(OrganizationActivationToken::class, 'organization');
    }

    public function reviews()
    {
        return $this->morphMany(PatientReview::class, 'organization')->where('is_active', true);
    }
}
