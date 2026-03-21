<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMediaInteraction extends Model
{
    protected $table = 'organization_media_interactions';

    protected $fillable = [
        'organization_media_id',
        'patient_id',
        'liked',
        'saved',
    ];

    protected $casts = [
        'liked' => 'boolean',
        'saved' => 'boolean',
    ];
}

