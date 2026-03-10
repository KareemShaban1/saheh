<?php

namespace Modules\MedicalLaboratory\LabService\Models;

use App\Models\LabServiceOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\MedicalLaboratory\LabServiceCategory\Models\LabServiceCategory;

class LabService extends Model
{
    use HasFactory;

    protected $table = 'lab_services';

    protected $fillable = [
        'lab_service_category_id',
        'name',
        'price',
        'unit',
        'normal_range',
        'organization_id',
        'organization_type',
        'notes',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(LabServiceCategory::class, 'lab_service_category_id');
    }

    public function labServiceOptions()
    {
        return $this->hasMany(LabServiceOption::class);
    }

    public function organization()
    {
        return $this->morphTo();
    }
}
