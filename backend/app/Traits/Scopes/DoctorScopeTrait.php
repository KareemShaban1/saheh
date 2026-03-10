<?php
namespace App\Traits\Scopes;

use Illuminate\Support\Facades\Auth;
use Modules\Clinic\User\Models\UserDoctor;

trait DoctorScopeTrait
{
    public function applyDoctorScope($query)
    {
        $user = Auth::user();
        $userRole = $user->roles->first()?->name;

        if ($userRole !== 'clinic-admin') {
            $userDoctors = UserDoctor::where('user_id', $user->id)->pluck('doctor_id')->toArray();
            $userDoctors = $userDoctors ? $userDoctors : [];
            $query->whereIn('doctor_id', $userDoctors);
        }

        return $query;
    }
}
