<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use Modules\Clinic\User\Models\User;

class PatientReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'organization_type',
        'doctor_id',
        'patient_id',
        'rating',
        'comment',
        'changed_by',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function organization()
    {
        return $this->morphTo();
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}