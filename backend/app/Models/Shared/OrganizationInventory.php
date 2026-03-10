<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'organization_type',
        'name',
        'quantity',
        'unit',
        'price',
        'description',
    ];


    public function scopeFromSameOrganization($query)
    {
        $user = auth()->user();

        return $query->where('organization_type', $user->organization_type)
            ->where('organization_id', $user->organization_id);
    }
}