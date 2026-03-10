<?php

namespace App\Policies;

use App\Models\RadiologyCenter;
use Modules\Clinic\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyCenterPolicy
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
     * @param  \App\Models\RadiologyCenter  $radiologyCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RadiologyCenter $radiologyCenter)
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
     * @param  \App\Models\RadiologyCenter  $radiologyCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RadiologyCenter $radiologyCenter)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \App\Models\RadiologyCenter  $radiologyCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, RadiologyCenter $radiologyCenter)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \App\Models\RadiologyCenter  $radiologyCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, RadiologyCenter $radiologyCenter)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Modules\Clinic\User\Models\User  $user
     * @param  \App\Models\RadiologyCenter  $radiologyCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, RadiologyCenter $radiologyCenter)
    {
        //
    }
}
