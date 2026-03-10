<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LabServiceOption extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    // 

    protected $fillable = [
        'lab_service_id',
        'lab_service_category_id',
        'name',
        'value',
        'price',
        'unit',
        'normal_range',
        'module_id',
        'module_type',
    ];

    public function labService()
    {
        return $this->belongsTo(LabService::class, 'lab_service_id')->with('category');
    }

}
