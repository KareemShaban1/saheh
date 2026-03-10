<?php

namespace Modules\Clinic\Announcement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'organization_type',
        'title',
        'body',
        'is_active',
        'start_date',
        'end_date',
        'type',
        'send_notification',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_notification' => 'boolean',
    ];

    public function organization()
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}