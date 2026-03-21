<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMedia extends Model
{
    protected $table = 'organization_media';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'media_type',
        'title',
        'description',
        'file_path',
        'mime_type',
        'duration_seconds',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
    ];
}

