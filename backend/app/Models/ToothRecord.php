<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToothRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'organization_type',
        'patient_id',
        'tooth_number',
        'status',
        'notes'
    ];
}
