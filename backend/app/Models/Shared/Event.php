<?php

namespace App\Models\Shared;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Event extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'date',
        'organization_id',
        'organization_type',
    ];


    protected static function booted()
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
