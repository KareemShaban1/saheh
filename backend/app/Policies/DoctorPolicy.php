<?php

namespace App\Policies;

use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DoctorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \Modules\Clinic\Doctor\Models\Doctor  $doctor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Doctor $doctor)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \Modules\Clinic\Doctor\Models\Doctor  $doctor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Doctor $doctor)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \Modules\Clinic\Doctor\Models\Doctor  $doctor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Doctor $doctor)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \Modules\Clinic\Doctor\Models\Doctor  $doctor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Doctor $doctor)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \Modules\Clinic\Doctor\Models\Doctor  $doctor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Doctor $doctor)
    {
        //
    }
}
