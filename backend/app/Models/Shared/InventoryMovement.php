<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class InventoryMovement extends Model
{
    use HasFactory;


    protected $fillable = [
       'inventory_id',
       'type',
       'quantity',
       'movement_date',
       'notes',
    ];

    public function inventory()
    {
        return $this->belongsTo(OrganizationInventory::class);
    }
}
