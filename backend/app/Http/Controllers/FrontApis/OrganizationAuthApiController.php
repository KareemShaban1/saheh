<?php

namespace App\Http\Controllers\FrontApis;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\StoreMedicalLaboratoryRequest;
use App\Http\Requests\StoreRadiologyCenterRequest;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Clinic\User\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * API Login & Registration for Clinic, Medical Laboratory, Radiology Center (React frontend)
 * Returns JSON and issues Sanctum token on login so frontend can send Bearer token.
 */
class OrganizationAuthApiController extends Controller
{
    use ApiHelperTrait;

    public function clinicLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)
            ->where('organization_type', Clinic::class)
            ->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }
        $user->tokens()->where('name', 'clinic-api')->delete();
        $token = $user->createToken('clinic-api')->plainTextToken;
        return response()->json([
            'status' => true,
            'message' => __('auth.success'),
            'token' => $token,
            'user' => $this->formatOrganizationUser($user, 'clinic'),
        ]);
    }

    public function clinicRegister(StoreClinicRequest $request)
    {
        $controller = new \App\Http\Controllers\Backend\Clinic\AuthController();
        return $controller->storeClinicTempData($request);
    }

    public function medicalLaboratoryLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)
            ->where('organization_type', MedicalLaboratory::class)
            ->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }
        $user->tokens()->where('name', 'medical-lab-api')->delete();
        $token = $user->createToken('medical-lab-api')->plainTextToken;
        return response()->json([
            'status' => true,
            'message' => __('auth.success'),
            'token' => $token,
            'user' => $this->formatOrganizationUser($user, 'medical_laboratory'),
        ]);
    }

    public function medicalLaboratoryRegister(StoreMedicalLaboratoryRequest $request)
    {
        $controller = new \App\Http\Controllers\Backend\MedicalLaboratory\AuthController();
        return $controller->storeMedicalLaboratoryTempData($request);
    }

    public function radiologyCenterLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)
            ->where('organization_type', RadiologyCenter::class)
            ->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }
        $user->tokens()->where('name', 'radiology-api')->delete();
        $token = $user->createToken('radiology-api')->plainTextToken;
        return response()->json([
            'status' => true,
            'message' => __('auth.success'),
            'token' => $token,
            'user' => $this->formatOrganizationUser($user, 'radiology_center'),
        ]);
    }

    public function radiologyCenterRegister(StoreRadiologyCenterRequest $request)
    {
        $controller = new \App\Http\Controllers\Backend\RadiologyCenter\AuthController();
        return $controller->storeRadiologyCenterTempData($request);
    }

    /** Logout: revoke current token (Bearer required) */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => true, 'message' => __('auth.logged_out')]);
    }

    /** Current organization user profile */
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => true,
            'user' => $this->formatOrganizationUser($user),
        ]);
    }

    /** Update organization user profile */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'job_title' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6|max:255',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
        ];
        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated',
            'user' => $this->formatOrganizationUser($user),
        ]);
    }

    private function formatOrganizationUser(User $user, ?string $organizationGuard = null): array
    {
        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->organization_id);
        }

        $guard = $organizationGuard ?? match ($user->organization_type) {
            Clinic::class => 'clinic',
            MedicalLaboratory::class => 'medical_laboratory',
            RadiologyCenter::class => 'radiology_center',
            default => null,
        };

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'organization_type' => $user->organization_type,
            'organization_guard' => $guard,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
