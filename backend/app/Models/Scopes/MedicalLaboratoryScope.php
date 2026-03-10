<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class MedicalLaboratoryScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        if ($user && $user->organization_type === 'App\\Models\\MedicalLaboratory') {
            $builder->where('organization_id', $user->organization_id)
                    ->where('organization_type', 'App\\Models\\MedicalLaboratory');
        }
    }
}

