<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\Doctor\Models\Doctor;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'service_name',
        'organization_id',
        'organization_type',
        'doctor_id',
        'price',
        'notes',
        'type'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new OrganizationScope);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeVisibleTo($query, $user)
    {
        $query->with(['doctor.user'])->clinic();

        $userRole = $user->roles->first()?->name;

        if ($userRole !== 'clinic-admin') {
            $doctorIds = \Modules\Clinic\User\Models\UserDoctor::where('user_id', $user->id)->pluck('doctor_id');
            $query->whereHas('doctor', fn($q) => $q->whereIn('doctor_id', $doctorIds));
        }

        return $query;
    }

    public function serviceOptions()
    {
        return $this->hasMany(ServiceOption::class);
    }

    /**
     * Backward-compatible alias for legacy code that still reads/writes "fee".
     */
    public function getFeeAttribute()
    {
        return $this->price;
    }

    public function setFeeAttribute($value): void
    {
        $this->attributes['price'] = $value;
    }

}