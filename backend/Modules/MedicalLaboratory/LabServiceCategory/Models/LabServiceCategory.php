<?php

namespace Modules\MedicalLaboratory\LabServiceCategory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\MedicalLaboratory\LabService\Models\LabService;

class LabServiceCategory extends Model
{
    use HasFactory;

    protected $table = 'lab_service_categories';

    protected $fillable = [
        'category_name',
        'is_active',
        'organization_id',
        'organization_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function labServices()
    {
        return $this->hasMany(LabService::class, 'lab_service_category_id');
    }

    public function organization()
    {
        return $this->morphTo();
    }
}
