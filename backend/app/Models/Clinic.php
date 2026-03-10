<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use App\Models\Shared\PatientReview;
use Modules\Clinic\User\Models\User;


class Clinic extends Model
{
    use HasFactory, HasDatabase, HasDomains;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'logo',
        'status',
        'is_active',
        'governorate_id',
        'city_id',
        'area_id',
        'website',
        'domain',
        'database',
        'start_date',
        'specialty_id',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'

    ];



    public function doctors(){
        return $this->hasMany(Doctor::class);
    }

    public function patients(){

        return $this->belongsToMany(
            Patient::class,
            'patient_organization',
            'organization_id',
            'patient_id',
        );

    }

    public function users()
    {
        return $this->morphMany(User::class, 'organization');
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

    public function specialty(){
        return $this->belongsTo(Specialty::class);
    }

    public function activationTokens()
    {
        return $this->morphMany(OrganizationActivationToken::class, 'organization');
    }

    public function reviews()
    {
        return $this->morphMany(PatientReview::class, 'organization')->where('is_active', true);
    }

    public function Services()
    {
        return $this->morphMany(Service::class,'organization')->withoutGlobalScopes();;
    }


}