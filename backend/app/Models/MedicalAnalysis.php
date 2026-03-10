<?php

namespace App\Models;

use App\Models\Scopes\MedicalLaboratoryScope;
use App\Models\Shared\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Clinic\Reservation\Models\Reservation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MedicalAnalysis extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;



    public $table = 'medical_analysis';

    protected $fillable = [
        'date',
        'report',
        'patient_id',
        'payment',
        'cost',
        'reservation_id',
        'organization_id',
        'organization_type',
        'doctor_name'
    ];

    protected $hidden = [
        'patient_id',
        'reservation_id',
        'organization_id',
        'organization_type',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [
        'images'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new MedicalLaboratoryScope);
    }



    // public function getImageUrlAttribute()
    // {
    //     if (!$this->images) {
    //         return 'https://scotturb.com/wp-content/uploads/2016/11/product-placeholder-300x300.jpg';
    //     }
    //     if (Str::startsWith($this->images, ['http://', 'https://'])) {
    //         return $this->images;
    //     }
    //     return asset('storage/medical_analysis/' . $this->images);
    // } // $analysis->image_url



    public function getImagesAttribute()
    {
        $images = [];

        foreach ($this->labServiceOptions as $fee) {
            foreach ($fee->getMedia('service_fee_images') as $media) {
                $images[] = $media->getUrl();
            }
        }

        return $images;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function labServiceOptions()
    {
        return $this->hasMany(LabServiceOption::class, 'module_id', 'id')
        ->with('labService');
    }

    public function getGroupedLabServiceOptions()
{
    return $this->labServiceOptions->groupBy(function ($option) {
        return optional($option->labService->category)->name ?? 'Uncategorized';
    });
}



    public function scopePaid($query)
    {
        return $query->where('payment', 'paid');
    }

    public function scopePatient($query)
    {
        return $query->where('patient_id', auth()->id());
    }
}
