<?php

namespace Modules\Clinic\Prescription\Models;

use App\Models\Scopes\ClinicScope;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Prescription extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'id','title','images','notes','reservation_id','patient_id','clinic_id'
    ];
    protected $table = 'prescriptions';

    protected $appends = ['images'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'reservation_id',
        'patient_id',
        // 'clinic_id',
    ];


    // protected static function booted()
    // {
    //     static::addGlobalScope(new ClinicScope);
    // }


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('prescription_images')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }

    public function scopePatient($query)
    {
        return $query->where('patient_id', auth()->id());
    }
}