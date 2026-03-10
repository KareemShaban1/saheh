<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{

    use HasFactory;

    public $table = 'settings';
    protected $fillable = [
        'clinic_id',
        'key',
        'value',
        'type',
        'organization_id',
        'organization_type',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new OrganizationScope);
    }

    public function clinic()
    {
        return $this->belongsTo(
            Clinic::class,
            'clinic_id',
            'id',
        );
    }

}
