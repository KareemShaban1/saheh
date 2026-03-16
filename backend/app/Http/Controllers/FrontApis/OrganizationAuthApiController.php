<?php

namespace App\Http\Controllers\FrontApis;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Clinic\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        $organization = $user->organization;
        if ($organization && !$this->canOrganizationLogin($organization)) {
            return response()->json([
                'status' => false,
                'message' => $this->organizationLoginStatusMessage($this->normalizeOrganizationStatus($organization->status)),
            ], 403);
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

    public function clinicRegister(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'clinic_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'specialty_id' => 'required|exists:specialties,id',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'clinic_email' => 'required|email',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ])->validate();

        $existing = Clinic::query()
            ->where('email', $validated['clinic_email'])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => $this->registrationStatusMessage($this->normalizeOrganizationStatus($existing->status)),
            ], 422);
        }

        if (User::query()->where('email', $validated['user_email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This user email is already registered.',
            ], 422);
        }

        DB::transaction(function () use ($validated): void {
            $organization = Clinic::create([
                'name' => $validated['clinic_name'],
                'start_date' => $validated['start_date'],
                'specialty_id' => $validated['specialty_id'],
                'governorate_id' => $validated['governorate_id'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'area_id' => $validated['area_id'] ?? null,
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['clinic_email'],
                'latitude' => (string) $validated['latitude'],
                'longitude' => (string) $validated['longitude'],
                'status' => 'pending',
                'is_active' => 0,
            ]);

            $user = User::create([
                'name' => $validated['user_name'],
                'email' => $validated['user_email'],
                'password' => Hash::make($validated['password']),
                'organization_id' => $organization->id,
                'organization_type' => Clinic::class,
		'job_title' => 'admin',
		'phone' => $validated['phone'],
            ]);

            $this->assignOrganizationAdminRole($user, 'clinic-admin', 'web', (int) $organization->id);
        });

        return response()->json([
            'success' => true,
            'message' => $this->registrationStatusMessage('pending'),
        ]);
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
        $organization = $user->organization;
        if ($organization && !$this->canOrganizationLogin($organization)) {
            return response()->json([
                'status' => false,
                'message' => $this->organizationLoginStatusMessage($this->normalizeOrganizationStatus($organization->status)),
            ], 403);
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

    public function medicalLaboratoryRegister(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'medical_laboratory_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'medical_laboratory_email' => 'required|email',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ])->validate();

        $existing = MedicalLaboratory::query()
            ->where('email', $validated['medical_laboratory_email'])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => $this->registrationStatusMessage($this->normalizeOrganizationStatus($existing->status)),
            ], 422);
        }

        if (User::query()->where('email', $validated['user_email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This user email is already registered.',
            ], 422);
        }

        DB::transaction(function () use ($validated): void {
            $organization = MedicalLaboratory::create([
                'name' => $validated['medical_laboratory_name'],
                'start_date' => $validated['start_date'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['medical_laboratory_email'],
                'governorate_id' => $validated['governorate_id'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'area_id' => $validated['area_id'] ?? null,
                'latitude' => (string) $validated['latitude'],
                'longitude' => (string) $validated['longitude'],
                'status' => 'pending',
                'is_active' => 0,
            ]);

            $user = User::create([
                'name' => $validated['user_name'],
                'email' => $validated['user_email'],
                'password' => Hash::make($validated['password']),
                'organization_id' => $organization->id,
                'organization_type' => MedicalLaboratory::class,
            ]);

            $this->assignOrganizationAdminRole($user, 'medical-laboratory-admin', 'medical_laboratory', (int) $organization->id);
        });

        return response()->json([
            'success' => true,
            'message' => $this->registrationStatusMessage('pending'),
        ]);
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
        $organization = $user->organization;
        if ($organization && !$this->canOrganizationLogin($organization)) {
            return response()->json([
                'status' => false,
                'message' => $this->organizationLoginStatusMessage($this->normalizeOrganizationStatus($organization->status)),
            ], 403);
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

    public function radiologyCenterRegister(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'radiology_center_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'radiology_center_email' => 'required|email',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ])->validate();

        $existing = RadiologyCenter::query()
            ->where('email', $validated['radiology_center_email'])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => $this->registrationStatusMessage($this->normalizeOrganizationStatus($existing->status)),
            ], 422);
        }

        if (User::query()->where('email', $validated['user_email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This user email is already registered.',
            ], 422);
        }

        DB::transaction(function () use ($validated): void {
            $organization = RadiologyCenter::create([
                'name' => $validated['radiology_center_name'],
                'start_date' => $validated['start_date'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['radiology_center_email'],
                'governorate_id' => $validated['governorate_id'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'area_id' => $validated['area_id'] ?? null,
                'latitude' => (string) $validated['latitude'],
                'longitude' => (string) $validated['longitude'],
                'status' => 'pending',
                'is_active' => 0,
            ]);

            $user = User::create([
                'name' => $validated['user_name'],
                'email' => $validated['user_email'],
                'password' => Hash::make($validated['password']),
                'organization_id' => $organization->id,
                'organization_type' => RadiologyCenter::class,
            ]);

            $this->assignOrganizationAdminRole($user, 'radiology-center-admin', 'radiology_center', (int) $organization->id);
        });

        return response()->json([
            'success' => true,
            'message' => $this->registrationStatusMessage('pending'),
        ]);
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

    private function normalizeOrganizationStatus(mixed $status): string
    {
        if (is_numeric($status)) {
            return (int) $status === 1 ? 'approved' : 'pending';
        }

        if (!is_string($status)) {
            return 'pending';
        }

        return match ($status) {
            '1', 'approved', 'active' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    private function registrationStatusMessage(string $status): string
    {
        return match ($status) {
            'rejected' => 'Your organization registration was rejected. Please contact support before submitting again.',
            'approved' => 'Your organization is already approved. Please use login.',
            default => 'Your organization registration is pending review.',
        };
    }

    private function canOrganizationLogin(object $organization): bool
    {
        $status = $this->normalizeOrganizationStatus($organization->status ?? null);
        return $status === 'approved' && (int) ($organization->is_active ?? 0) === 1;
    }

    private function organizationLoginStatusMessage(string $status): string
    {
        return match ($status) {
            'rejected' => 'Your organization account was rejected. Please contact support.',
            'approved' => 'Your organization account is not active yet. Please contact support.',
            default => 'Your organization registration is still pending approval.',
        };
    }

    private function assignOrganizationAdminRole(User $user, string $roleName, string $guardName, int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->where('team_id', $teamId)
            ->first();

        if (!$role) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => $guardName,
                'team_id' => $teamId,
            ]);

            $permissions = Permission::query()
                ->where('guard_name', $guardName)
                ->get();

            if ($permissions->isNotEmpty()) {
                $role->syncPermissions($permissions);
            }
        }

        $user->assignRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
