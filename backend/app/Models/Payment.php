<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_id',
        'payable_type',
        'payment_date',
        'amount',
        'remaining',
        'payment_way',
    ];

    protected $casts = [
        'payment_date' => 'datetime:Y-m-d H:i:s',
        'amount' => 'decimal:2',
        'remaining' => 'decimal:2',
    ];

    public function payable()
    {
        return $this->morphTo();
    }
}
