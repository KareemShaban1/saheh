<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOption extends Model
{
    use HasFactory;

    protected $table = 'service_options';
    protected $fillable = [
        'service_fee_id',
        'name',
        'unit',
        'normal_range',
    ];

    public function Service()
    {
        return $this->belongsTo(Service::class);
    }
}
