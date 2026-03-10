<?php

namespace App\Actions\Fortify;

use App\Models\Admin;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Models\RadiologyCenter;
use Illuminate\Support\Facades\Hash;
use Modules\Clinic\User\Models\User;

class CustomAuthentication
{
    public function authenticateClinicUser($request)
    {
        $email = $request->email;
        $password = $request->password;
        $user = User::where('email', '=', $email)
            ->where('organization_type', '=', Clinic::class)
            ->whereHas('organization', function ($query) {
                $query->where('status', 1);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return false;
    }

    public function authenticateMedicalLaboratoryUser($request)
    {
        $email = $request->email;
        $password = $request->password;
        $user = User::where('email', '=', $email)
            ->where('organization_type', '=', MedicalLaboratory::class)
            ->whereHas('organization', function ($query) {
                $query->where('status', 1);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return false;
    }

    public function authenticateRadiologyCenterUser($request)
    {
        $email = $request->email;
        $password = $request->password;
        $user = User::where('email', '=', $email)
            ->where('organization_type', '=', RadiologyCenter::class)
            ->whereHas('organization', function ($query) {
                $query->where('status', 1);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return false;
    }

    public function authenticatePatient($request)
    {

        $request->validate(
            [
                'email' => ['required'],
                'password' => ['required'],
            ],
            [
                'email.required' => 'برجاء أدخال البريد الألكترونى',
                'password.required' => 'برجاء أدخال كلمة المرور',

            ]
        );

        $email = $request->email;
        $password = $request->password;
        $patient = Patient::where('email', '=', $email)
        // ->whereHas('clinic',function($query){
        //     $query->where('status', 1);
        // })
            ->first();

        // dd($patient);

        if ($patient && Hash::check($password, $patient->password)) {
            return $patient;
        }

        return false;
    }

    public function authenticateAdmin($request)
    {

        $request->validate(
            [
                'email' => ['required'],
                'password' => ['required'],
            ],
            [
                'email.required' => 'برجاء أدخال البريد الألكترونى',
                'password.required' => 'برجاء أدخال كلمة المرور',

            ]
        );

        $email = $request->email;
        $password = $request->password;
        $admin = Admin::where('email', '=', $email)->first();

        if ($admin && Hash::check($password, $admin->password)) {
            return $admin;
        }

        return false;
    }
}
