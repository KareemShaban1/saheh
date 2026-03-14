<?php

namespace App\Http\Controllers\FrontApis\radiologyCenter;

use App\Http\Controllers\Controller;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shared\Patient;
use App\Models\Shared\Event;
use App\Models\Ray;
use App\Models\RayCategory;
use App\Models\RadiologyCenter;
use Modules\Clinic\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Front API: Radiology Center dashboard pages (JSON for React frontend)
 */
class RadiologyCenterDashboardApiController extends Controller
{
    use ApiHelperTrait;

    /**
     * Dashboard overview
     */
    public function dashboard()
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();
        $orgId = $user->organization_id ?? null;
        $orgType = $user->organization_type ?? null;
        $patients_count = Patient::query()->radiologyCenter()->count();
        $users_count = User::count();
        $eventsQuery = Event::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class);
        if ($orgId && $orgType) {
            $eventsQuery->where('organization_id', $orgId)->where('organization_type', $orgType);
        }
        $events_today = (clone $eventsQuery)->whereDate('date', Carbon::now('Egypt')->format('Y-m-d'))->count();
        $data = [
            'stats' => [
                ['label' => 'Patients', 'value' => (string) $patients_count],
                ['label' => 'Users', 'value' => (string) $users_count],
                ['label' => "Today's Events", 'value' => (string) $events_today],
            ],
            'reservations' => [],
        ];
        return $this->returnJSON($data, 'Dashboard data', 'success');
    }

    /**
     * Financial module data (summary + monthly trend).
     */
    public function financial(Request $request)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();
        $orgId = $user->organization_id ?? null;
        $orgType = $user->organization_type ?? null;

        if (!$orgId || !$orgType) {
            return $this->returnJSON([
                'summary' => [
                    'total_revenue' => 0,
                    'total_due' => 0,
                    'paid_count' => 0,
                    'unpaid_count' => 0,
                ],
                'trend' => [],
                'breakdown' => [],
            ], 'Financial data', 'success');
        }

        $months = max(3, min(12, (int) $request->get('months', 6)));
        $from = Carbon::now('Egypt')->startOfMonth()->subMonths($months - 1);
        $base = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->where('organization_id', $orgId)
            ->where('organization_type', $orgType);

        $paidTotal = (float) ((clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $dueTotal = (float) ((clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='not_paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $paidCount = (int) ((clone $base)->where('payment', 'paid')->count());
        $unpaidCount = (int) ((clone $base)->where('payment', 'not_paid')->count());

        $rows = (clone $base)
            ->whereDate('date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END),0) as revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='not_paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END),0) as due")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $trend = [];
        for ($i = 0; $i < $months; $i++) {
            $pointDate = (clone $from)->addMonths($i);
            $key = $pointDate->format('Y-m');
            $row = $rows->get($key);
            $trend[] = [
                'month' => $pointDate->format('M Y'),
                'revenue' => (float) ($row->revenue ?? 0),
                'due' => (float) ($row->due ?? 0),
            ];
        }

        return $this->returnJSON([
            'summary' => [
                'total_revenue' => $paidTotal,
                'total_due' => $dueTotal,
                'paid_count' => $paidCount,
                'unpaid_count' => $unpaidCount,
            ],
            'trend' => $trend,
            'breakdown' => [
                ['name' => 'Paid', 'value' => $paidTotal],
                ['name' => 'Due', 'value' => $dueTotal],
            ],
        ], 'Financial data', 'success');
    }

    /**
     * Reservations/events list (data for table)
     */
    public function reservationsData(Request $request)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();
        $query = Event::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class);
        if ($user->organization_id && $user->organization_type) {
            $query->where('organization_id', $user->organization_id)->where('organization_type', $user->organization_type);
        }
        $query->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = collect($paginated->items())->map(fn ($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'date' => $e->date,
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Events', 'success');
    }

    /**
     * Patients list for radiology dashboard.
     */
    public function patients(Request $request)
    {
        $this->ensureRadiologyAuth();

        $query = Patient::query()
            ->radiologyCenter()
            ->withTrashed()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = collect($paginated->items())->map(fn ($p) => [
            'id' => $p->id,
            'patient_code' => $p->patient_code,
            'name' => $p->name,
            'phone' => $p->phone ?? null,
            'email' => $p->email ?? null,
            'address' => $p->address ?? null,
            'age' => $p->age ?? null,
            'gender' => $p->gender ?? null,
            'blood_group' => $p->blood_group ?? null,
            'status' => $p->deleted_at ? 'inactive' : 'active',
        ])->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Patients', 'success');
    }

    public function patientDetails($id)
    {
        $this->ensureRadiologyAuth();

        $patient = Patient::query()
            ->radiologyCenter()
            ->withTrashed()
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
            'name' => $patient->name,
            'phone' => $patient->phone ?? null,
            'email' => $patient->email ?? null,
            'address' => $patient->address ?? null,
            'age' => $patient->age ?? null,
            'gender' => $patient->gender ?? null,
            'blood_group' => $patient->blood_group ?? null,
            'status' => $patient->deleted_at ? 'inactive' : 'active',
        ], 'Patient details', 'success');
    }

    public function createPatient(Request $request)
    {
        $this->ensureRadiologyAuth();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:patients,phone',
            'email' => 'nullable|email|max:255|unique:patients,email',
            'address' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:6|max:255',
            'whatsapp_number' => 'nullable|string|max:20',
            'age' => 'nullable|string|max:10',
            'gender' => 'required|in:male,female',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'height' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
        ]);

        $patient = Patient::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => !empty($validated['password']) ? Hash::make($validated['password']) : null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'],
            'blood_group' => $validated['blood_group'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
        ]);

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
        ], 'Patient created', 'success');
    }

    public function updatePatient(Request $request, $id)
    {
        $this->ensureRadiologyAuth();

        $patient = Patient::query()
            ->radiologyCenter()
            ->withTrashed()
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:patients,phone,' . $patient->id,
            'email' => 'nullable|email|max:255|unique:patients,email,' . $patient->id,
            'address' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:6|max:255',
            'whatsapp_number' => 'nullable|string|max:20',
            'age' => 'nullable|string|max:10',
            'gender' => 'required|in:male,female',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'height' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        $payload = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'],
            'blood_group' => $validated['blood_group'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
        ];
        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $patient->update($payload);
        if (($validated['status'] ?? null) === 'inactive' && !$patient->trashed()) {
            $patient->delete();
        }
        if (($validated['status'] ?? null) === 'active' && $patient->trashed()) {
            $patient->restore();
        }

        return $this->returnJSON(['id' => $patient->id], 'Patient updated', 'success');
    }

    public function deletePatient($id)
    {
        $this->ensureRadiologyAuth();

        $patient = Patient::query()
            ->radiologyCenter()
            ->findOrFail($id);
        $patient->delete();

        return $this->returnJSON(['id' => (int) $id], 'Patient deleted', 'success');
    }

    /**
     * Assign existing patient to current radiology center by patient code or QR value.
     */
    public function assignPatientByCode(Request $request)
    {
        $this->ensureRadiologyAuth();

        $validated = $request->validate([
            'patient_code' => 'nullable|string|max:255|required_without:qr_value',
            'qr_value' => 'nullable|string|max:2000|required_without:patient_code',
        ]);

        $rawCode = (string) ($validated['patient_code'] ?? $validated['qr_value'] ?? '');
        $code = $this->extractPatientCode($rawCode);
        if ($code === null || $code === '') {
            throw ValidationException::withMessages([
                'patient_code' => ['A valid patient code is required.'],
            ]);
        }

        $patient = Patient::query()->where('patient_code', $code)->first();
        if (!$patient) {
            throw ValidationException::withMessages([
                'patient_code' => ['Patient not found for this code.'],
            ]);
        }

        $assignedNewly = $this->assignPatientToCurrentRadiology((int) $patient->id);

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
            'assigned_newly' => $assignedNewly,
        ], $assignedNewly ? 'Patient assigned successfully' : 'Patient already assigned', 'success');
    }

    /**
     * Unassign patient from current radiology center.
     */
    public function unassignPatient($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        DB::table('patient_organization')
            ->where('patient_id', (int) $id)
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', RadiologyCenter::class)
            ->update([
                'assigned' => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->returnJSON(['id' => (int) $id], 'Patient unassigned', 'success');
    }

    /**
     * Patient profile/history with previous rays in current radiology center.
     */
    public function patientHistory($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $patient = Patient::query()
            ->radiologyCenter()
            ->where('id', $id)
            ->firstOrFail();

        $rays = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->where('patient_id', $patient->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($ray) => [
                'id' => $ray->id,
                'reservation_id' => $ray->reservation_id,
                'date' => $ray->date,
                'payment' => $ray->payment,
                'cost' => $ray->cost,
                'report' => $ray->report,
                'images' => $ray->getMedia('ray_images')->map(fn ($m) => $m->getUrl())->values()->all(),
                'created_at' => optional($ray->created_at)->format('Y-m-d H:i'),
            ])
            ->values();

        return $this->returnJSON([
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'address' => $patient->address,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
            ],
            'rays' => $rays,
        ], 'Patient history profile', 'success');
    }

    /**
     * Roles list for radiology center.
     */
    public function roles()
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $roles = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->withCount('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'guard_name' => $r->guard_name,
                'permissions_count' => (int) $r->permissions_count,
                'users_count' => (int) $r->users_count,
            ]);

        return $this->returnJSON($roles, 'Roles', 'success');
    }

    public function permissions()
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $permissionIds = DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('roles.team_id', $orgId)
            ->where('roles.guard_name', $guard)
            ->pluck('role_has_permissions.permission_id')
            ->unique()
            ->values();

        $permissions = Permission::query()
            ->whereIn('id', $permissionIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]);

        return $this->returnJSON($permissions, 'Permissions', 'success');
    }

    public function roleDetails($id)
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $role = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->with('permissions:id,name')
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions_count' => $role->permissions->count(),
            'users_count' => (int) $role->users()->count(),
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($v) => (int) $v)->values(),
            'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ], 'Role details', 'success');
    }

    public function createRole(Request $request)
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|integer|exists:permissions,id',
        ]);

        $exists = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->where('name', trim($validated['name']))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Role name already exists for this radiology center.'],
            ]);
        }

        $role = Role::create([
            'name' => trim($validated['name']),
            'guard_name' => $guard,
            'team_id' => $orgId,
        ]);

        $permissionIds = collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $allowedIds = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('id', $permissionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($allowedIds->count() !== $permissionIds->count()) {
            throw ValidationException::withMessages([
                'permission_ids' => ['One or more selected permissions are invalid for radiology roles.'],
            ]);
        }

        $permissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('id', $allowedIds->all())
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role created', 'success');
    }

    public function updateRole(Request $request, $id)
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $role = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|integer|exists:permissions,id',
        ]);

        $exists = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->where('name', trim($validated['name']))
            ->where('id', '!=', $role->id)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Role name already exists for this radiology center.'],
            ]);
        }

        $role->update(['name' => trim($validated['name'])]);
        $permissionIds = collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $allowedIds = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('id', $permissionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($allowedIds->count() !== $permissionIds->count()) {
            throw ValidationException::withMessages([
                'permission_ids' => ['One or more selected permissions are invalid for radiology roles.'],
            ]);
        }

        $permissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('id', $allowedIds->all())
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role updated', 'success');
    }

    public function deleteRole($id)
    {
        $this->ensureRadiologyAuth();
        $orgId = request()->user()->organization_id;
        $guard = 'radiology_center';

        $role = Role::query()
            ->where('team_id', $orgId)
            ->where('guard_name', $guard)
            ->findOrFail($id);
        $role->delete();

        return $this->returnJSON(['id' => (int) $id], 'Role deleted', 'success');
    }

    /**
     * Ray categories list for radiology dashboard.
     */
    public function rayCategories(Request $request)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $query = RayCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'created_at' => optional($category->created_at)->format('Y-m-d'),
        ])->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Ray categories', 'success');
    }

    /**
     * Single ray category details.
     */
    public function rayCategoryDetails($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $category = RayCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'created_at' => optional($category->created_at)->format('Y-m-d'),
        ], 'Ray category details', 'success');
    }

    /**
     * Create a new ray category.
     */
    public function createRayCategory(Request $request)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ray_categories', 'name')
                    ->where(fn ($q) => $q
                        ->where('organization_id', $authUser->organization_id)
                        ->where('organization_type', $authUser->organization_type)),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $category = RayCategory::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'organization_id' => $authUser->organization_id,
            'organization_type' => $authUser->organization_type,
        ]);

        return $this->returnJSON(['id' => $category->id], 'Ray category created', 'success');
    }

    /**
     * Update an existing ray category.
     */
    public function updateRayCategory(Request $request, $id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $category = RayCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ray_categories', 'name')
                    ->ignore($category->id)
                    ->where(fn ($q) => $q
                        ->where('organization_id', $authUser->organization_id)
                        ->where('organization_type', $authUser->organization_type)),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $category->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return $this->returnJSON(['id' => $category->id], 'Ray category updated', 'success');
    }

    /**
     * Delete a ray category.
     */
    public function deleteRayCategory($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();

        $category = RayCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $category->delete();

        return $this->returnJSON(['id' => (int) $id], 'Ray category deleted', 'success');
    }

    /**
     * Rays list for radiology dashboard.
     */
    public function rays(Request $request)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();

        $query = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->with('patient:id,name')
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('report', 'like', '%' . $search . '%')
                        ->orWhere('date', 'like', '%' . $search . '%')
                        ->orWhereHas('patient', fn ($patientQ) => $patientQ->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = collect($paginated->items())->map(function ($ray) {
            return [
                'id' => $ray->id,
                'patient_id' => $ray->patient_id,
                'patient_name' => $ray->patient?->name,
                'reservation_id' => $ray->reservation_id,
                'date' => $ray->date,
                'payment' => $ray->payment,
                'cost' => $ray->cost,
                'report' => $ray->report,
                'images_count' => $ray->getMedia('ray_images')->count(),
                'status' => $ray->deleted_at ? 'inactive' : 'active',
            ];
        })->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Rays', 'success');
    }

    public function rayDetails($id)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->with('patient:id,name')
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $ray->id,
            'patient_id' => $ray->patient_id,
            'patient_name' => $ray->patient?->name,
            'reservation_id' => $ray->reservation_id,
            'date' => $ray->date,
            'payment' => $ray->payment,
            'cost' => $ray->cost,
            'report' => $ray->report,
            'images' => $ray->getMedia('ray_images')->map(fn ($m) => $m->getUrl())->values()->all(),
        ], 'Ray details', 'success');
    }

    public function createRay(Request $request)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'date' => 'required|date',
            'payment' => 'required|in:paid,not_paid',
            'cost' => 'nullable|numeric|min:0',
            'report' => 'nullable|string|max:5000',
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240',
        ]);

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)->create([
            'patient_id' => (int) $validated['patient_id'],
            'reservation_id' => isset($validated['reservation_id']) ? (int) $validated['reservation_id'] : null,
            'organization_id' => $user->organization_id,
            'organization_type' => $user->organization_type,
            'date' => $validated['date'],
            'payment' => $validated['payment'],
            'cost' => isset($validated['cost']) ? (string) $validated['cost'] : null,
            'report' => $validated['report'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $ray->addMedia($image)->toMediaCollection('ray_images');
            }
        }

        return $this->returnJSON(['id' => $ray->id], 'Ray created', 'success');
    }

    public function updateRay(Request $request, $id)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->findOrFail($id);

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'date' => 'required|date',
            'payment' => 'required|in:paid,not_paid',
            'cost' => 'nullable|numeric|min:0',
            'report' => 'nullable|string|max:5000',
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240',
        ]);

        $ray->update([
            'patient_id' => (int) $validated['patient_id'],
            'reservation_id' => isset($validated['reservation_id']) ? (int) $validated['reservation_id'] : null,
            'date' => $validated['date'],
            'payment' => $validated['payment'],
            'cost' => isset($validated['cost']) ? (string) $validated['cost'] : null,
            'report' => $validated['report'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            $ray->clearMediaCollection('ray_images');
            foreach ($request->file('images') as $image) {
                $ray->addMedia($image)->toMediaCollection('ray_images');
            }
        }

        return $this->returnJSON(['id' => $ray->id], 'Ray updated', 'success');
    }

    public function deleteRay($id)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->findOrFail($id);
        $ray->delete();

        return $this->returnJSON(['id' => (int) $id], 'Ray deleted', 'success');
    }

    /**
     * Users list for radiology center dashboard.
     */
    public function users(Request $request)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $query = User::query()
            ->withTrashed()
            ->with('roles')
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('job_title', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = collect($paginated->items())->map(function ($u) use ($authUser) {
            $scopedRole = $u->roles->first(function ($r) use ($authUser) {
                return (int) ($r->team_id ?? 0) === (int) $authUser->organization_id;
            }) ?? $u->roles->first();

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone ?? null,
                'job_title' => $u->job_title ?? null,
                'role' => $scopedRole?->name ?? 'staff',
                'role_id' => $scopedRole?->id,
                'status' => $u->deleted_at ? 'inactive' : 'active',
            ];
        })->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Users', 'success');
    }

    public function userDetails($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $user = User::withTrashed()
            ->with(['roles:id,name,guard_name,team_id', 'permissions:id,name'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $role = $user->roles->first(function ($r) use ($authUser) {
            return (int) ($r->team_id ?? 0) === (int) $authUser->organization_id;
        }) ?? $user->roles->first();

        return $this->returnJSON([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'job_title' => $user->job_title ?? null,
            'role_id' => $role?->id,
            'role_name' => $role?->name,
            'permission_ids' => $user->permissions->pluck('id')->map(fn ($v) => (int) $v)->values(),
            'status' => $user->deleted_at ? 'inactive' : 'active',
        ], 'User details', 'success');
    }

    public function createUser(Request $request)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'phone' => 'nullable|string|max:30',
            'job_title' => 'nullable|string|max:100',
            'role_id' => 'required|integer|exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|integer|exists:permissions,id',
        ]);

        $role = Role::query()
            ->where('team_id', $authUser->organization_id)
            ->findOrFail((int) $validated['role_id']);

        $permissionNames = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? 'user',
            'organization_type' => $authUser->organization_type,
            'organization_id' => $authUser->organization_id,
        ]);
        $user->syncRoles([$role]);
        $user->syncPermissions($permissionNames);

        return $this->returnJSON(['id' => $user->id], 'User created', 'success');
    }

    public function updateUser(Request $request, $id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $user = User::withTrashed()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|max:255',
            'phone' => 'nullable|string|max:30',
            'job_title' => 'nullable|string|max:100',
            'role_id' => 'required|integer|exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|integer|exists:permissions,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $role = Role::query()
            ->where('team_id', $authUser->organization_id)
            ->findOrFail((int) $validated['role_id']);

        $permissionNames = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? $user->job_title ?? 'user',
        ];
        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$role]);
        $user->syncPermissions($permissionNames);
        if (($validated['status'] ?? null) === 'inactive' && !$user->trashed()) {
            $user->delete();
        }
        if (($validated['status'] ?? null) === 'active' && $user->trashed()) {
            $user->restore();
        }

        return $this->returnJSON(['id' => $user->id], 'User updated', 'success');
    }

    public function deleteUser($id)
    {
        $this->ensureRadiologyAuth();
        $authUser = request()->user();
        if ((int) $authUser->id === (int) $id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.'],
            ]);
        }

        $user = User::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);
        $user->delete();

        return $this->returnJSON(['id' => (int) $id], 'User deleted', 'success');
    }

    /**
     * Notifications feed for radiology dashboard.
     */
    public function notifications(Request $request)
    {
        $this->ensureRadiologyAuth();
        $user = request()->user();
        $perPage = (int) $request->get('per_page', 20);

        $paginated = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $data = collect($paginated->items())->map(function ($notification) {
            $payload = is_array($notification->data) ? $notification->data : [];
            $module = $payload['module'] ?? $notification->module ?? 'general';

            return [
                'id' => (string) $notification->id,
                'type' => $payload['type'] ?? $notification->event ?? $notification->type,
                'module' => (string) $module,
                'event' => $payload['event'] ?? $notification->event,
                'title' => $payload['title'] ?? 'Notification',
                'message' => $payload['message'] ?? $payload['body'] ?? $payload['content'] ?? '',
                'priority' => $payload['priority'] ?? 'medium',
                'action_url' => $payload['action_url'] ?? $notification->action_url ?? null,
                'is_read' => !is_null($notification->read_at),
                'read_at' => optional($notification->read_at)->toDateTimeString(),
                'created_at' => optional($notification->created_at)->toDateTimeString(),
            ];
        })->values();

        return $this->returnJSON([
            'data' => $data,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Notifications', 'success');
    }

    private function ensureRadiologyAuth(): void
    {
        if (!request()->user()) {
            abort(401, 'Unauthenticated');
        }
    }

    private function assignPatientToCurrentRadiology(int $patientId): bool
    {
        $authUser = request()->user();
        $organizationId = $authUser->organization_id;

        $existing = DB::table('patient_organization')
            ->where('patient_id', $patientId)
            ->where('organization_id', $organizationId)
            ->where('organization_type', RadiologyCenter::class)
            ->first();

        if ($existing) {
            if ((int) $existing->assigned === 1 && $existing->deleted_at === null) {
                return false;
            }

            DB::table('patient_organization')
                ->where('id', $existing->id)
                ->update([
                    'assigned' => 1,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);

            return true;
        }

        DB::table('patient_organization')->insert([
            'patient_id' => $patientId,
            'organization_id' => $organizationId,
            'organization_type' => RadiologyCenter::class,
            'doctor_id' => null,
            'assigned' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    private function extractPatientCode(string $raw): ?string
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        if (preg_match('/code=([0-9]+)/i', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/([0-9]{6,})/', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    private function pagination($paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }
}
