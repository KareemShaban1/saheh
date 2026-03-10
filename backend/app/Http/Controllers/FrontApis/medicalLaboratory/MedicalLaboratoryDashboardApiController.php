<?php

namespace App\Http\Controllers\FrontApis\medicalLaboratory;

use App\Http\Controllers\Controller;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shared\Patient;
use App\Models\Shared\Event;
use App\Models\MedicalAnalysis;
use App\Models\LabService;
use App\Models\LabServiceCategory;
use App\Models\LabServiceOption;
use App\Models\MedicalLaboratory;
use Modules\Clinic\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Front API: Medical Laboratory dashboard pages (JSON for React frontend)
 */
class MedicalLaboratoryDashboardApiController extends Controller
{
    use ApiHelperTrait;

    /**
     * Dashboard overview
     */
    public function dashboard()
    {
        $this->ensureLabAuth();
        $user = request()->user();
        $orgId = $user->organization_id ?? null;
        $orgType = $user->organization_type ?? null;
        $patients_count = Patient::query()->medicalLaboratory()->count();
        $users_count = User::query()
            ->where('organization_id', $orgId)
            ->where('organization_type', $orgType)
            ->count();
        $medicalAnalysis = MedicalAnalysis::all()->count();
        $eventsQuery = Event::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class);
        if ($orgId && $orgType) {
            $eventsQuery->where('organization_id', $orgId)->where('organization_type', $orgType);
        }
        $events_today = (clone $eventsQuery)->whereDate('date', Carbon::now('Egypt')->format('Y-m-d'))->count();
        $recentEvents = (clone $eventsQuery)
            ->latest('id')
            ->take(8)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'patient' => $e->title,
                'test' => $e->title,
                'time' => optional($e->created_at)->format('h:i A') ?: '—',
                'status' => 'pending',
                'date' => $e->date,
            ]);
        $data = [
            'stats' => [
                ['label' => 'Patients', 'value' => (string) $patients_count],
                ['label' => 'Users', 'value' => (string) $users_count],
                ['label' => 'Medical Analyses', 'value' => (string) $medicalAnalysis],
                ['label' => "Today's Events", 'value' => (string) $events_today],
            ],
            'reservations' => $recentEvents,
        ];
        return $this->returnJSON($data, 'Dashboard data', 'success');
    }

    /**
     * Financial module data (summary + monthly trend).
     */
    public function financial(Request $request)
    {
        $this->ensureLabAuth();
        $authUser = request()->user();
        $orgId = $authUser->organization_id ?? null;
        $orgType = $authUser->organization_type ?? null;

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
        $base = MedicalAnalysis::withoutGlobalScope(\App\Models\Scopes\MedicalLaboratoryScope::class)
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
        $this->ensureLabAuth();
        $user = request()->user();
        $query = Event::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class);
        if ($user->organization_id && $user->organization_type) {
            $query->where('organization_id', $user->organization_id)->where('organization_type', $user->organization_type);
        }
        $query->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where('title', 'like', '%' . $search . '%');
        }
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($e) => [
            'id' => $e->id,
            'patient' => $e->title,
            'test' => $e->title,
            'date' => $e->date,
            'time' => optional($e->created_at)->format('h:i A') ?: '—',
            'status' => 'pending',
            'payment' => 'unpaid',
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Events', 'success');
    }

    /**
     * Notifications feed for lab dashboard.
     */
    public function notifications(Request $request)
    {
        $this->ensureLabAuth();
        $user = request()->user();
        $perPage = (int) $request->get('per_page', 20);

        $paginated = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($notification) {
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
            'pagination' => $this->pagination($paginated),
        ], 'Notifications', 'success');
    }

    /**
     * Patients list for lab dashboard.
     */
    public function patients(Request $request)
    {
        $this->ensureLabAuth();

        $query = Patient::query()
            ->medicalLaboratory()
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
        $data = $paginated->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'patient_code' => $p->patient_code,
            'name' => $p->name,
            'phone' => $p->phone ?? null,
            'email' => $p->email ?? null,
            'age' => $p->age ?? null,
            'gender' => $p->gender ?? null,
            'blood_type' => $p->blood_group ?? null,
            'status' => $p->deleted_at ? 'inactive' : 'active',
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Patients', 'success');
    }

    /**
     * Create patient and assign to current medical laboratory.
     */
    public function createPatient(Request $request)
    {
        $this->ensureLabAuth();

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

        $patient = DB::transaction(function () use ($validated) {
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

            $this->assignPatientToCurrentLab((int) $patient->id);

            return $patient;
        });

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
        ], 'Patient created and assigned', 'success');
    }

    /**
     * Assign an existing patient to current medical laboratory by patient code or QR value.
     */
    public function assignPatientByCode(Request $request)
    {
        $this->ensureLabAuth();

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

        $assignedNewly = $this->assignPatientToCurrentLab((int) $patient->id);

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
            'assigned_newly' => $assignedNewly,
        ], $assignedNewly ? 'Patient assigned successfully' : 'Patient already assigned', 'success');
    }

    /**
     * Patient profile/history with previous medical analyses in current lab.
     */
    public function patientHistory($id)
    {
        $this->ensureLabAuth();
        $authUser = request()->user();

        $patient = Patient::query()
            ->medicalLaboratory()
            ->where('id', $id)
            ->firstOrFail();

        $analyses = MedicalAnalysis::query()
            ->with(['labServiceOptions.labService:id,name'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->where('patient_id', $patient->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'reservation_id' => $analysis->reservation_id,
                    'date' => $analysis->date,
                    'doctor_name' => $analysis->doctor_name,
                    'payment' => $analysis->payment,
                    'cost' => (string) ($analysis->cost ?? 0),
                    'report' => $analysis->report,
                    'services' => $analysis->labServiceOptions->map(fn ($option) => [
                        'id' => $option->id,
                        'lab_service_id' => $option->lab_service_id,
                        'name' => $option->name ?? $option->labService?->name,
                        'value' => $option->value,
                        'unit' => $option->unit,
                        'normal_range' => $option->normal_range,
                        'price' => (string) ($option->price ?? 0),
                        'images' => $option->getMedia('service_fee_images')->map(fn ($m) => $m->getUrl())->values(),
                    ])->values(),
                    'created_at' => optional($analysis->created_at)->format('Y-m-d H:i'),
                ];
            })
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
            'medical_analyses' => $analyses,
        ], 'Patient history profile', 'success');
    }

    /**
     * Unassign patient from current medical laboratory.
     */
    public function unassignPatient($id)
    {
        $this->ensureLabAuth();
        $authUser = request()->user();

        DB::table('patient_organization')
            ->where('patient_id', (int) $id)
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', MedicalLaboratory::class)
            ->update([
                'assigned' => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->returnJSON(['id' => (int) $id], 'Patient unassigned', 'success');
    }

    /**
     * Users list for lab dashboard.
     */
    public function users(Request $request)
    {
        $this->ensureLabAuth();

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
                        ->orWhere('job_title', 'like', '%' . $search . '%')
                        ->orWhereHas('roles', fn ($rolesQuery) => $rolesQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(function ($u) use ($authUser) {
            $scopedRole = $u->roles->first(function ($r) use ($authUser) {
                return (int) ($r->team_id ?? 0) === (int) $authUser->organization_id
                    && (string) $r->guard_name === $this->labGuardName();
            }) ?? $u->roles->first();

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone ?? null,
                'job_title' => $u->job_title ?? null,
                'role' => $scopedRole?->name ?? 'staff',
                'role_id' => $scopedRole?->id,
                'roles' => $u->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values(),
                'permissions_count' => $u->getAllPermissions()->count(),
                'status' => $u->deleted_at ? 'inactive' : 'active',
                'created_at' => optional($u->created_at)->format('Y-m-d'),
            ];
        });

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Users', 'success');
    }

    /**
     * Single lab user details with role and direct permissions.
     */
    public function userDetails($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
        $user = User::withTrashed()
            ->with(['roles:id,name,guard_name,team_id', 'permissions:id,name'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $role = $user->roles->first(function ($r) use ($authUser) {
            return (int) ($r->team_id ?? 0) === (int) $authUser->organization_id
                && (string) $r->guard_name === $this->labGuardName();
        }) ?? $user->roles->first();
        $directPermissions = $user->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values();
        $effectivePermissions = $user->getAllPermissions()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values();

        return $this->returnJSON([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'job_title' => $user->job_title ?? null,
            'role_id' => $role?->id,
            'role_name' => $role?->name,
            'permission_ids' => $user->permissions->pluck('id')->map(fn ($v) => (int) $v)->values(),
            'permissions' => $directPermissions,
            'effective_permissions' => $effectivePermissions,
        ], 'User details', 'success');
    }

    /**
     * Create lab user with role and direct permissions.
     */
    public function createUser(Request $request)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
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

        $role = $this->resolveLabRole((int) $validated['role_id'], (int) $authUser->organization_id);
        $permissionNames = Permission::query()
            ->where('guard_name', $this->labGuardName())
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
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

    /**
     * Update lab user with role and direct permissions.
     */
    public function updateUser(Request $request, $id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
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
        ]);

        $role = $this->resolveLabRole((int) $validated['role_id'], (int) $authUser->organization_id);
        $permissionNames = Permission::query()
            ->where('guard_name', $this->labGuardName())
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
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

        return $this->returnJSON(['id' => $user->id], 'User updated', 'success');
    }

    /**
     * Soft-delete (deactivate) lab user.
     */
    public function deactivateUser($id)
    {
        $this->ensureLabAuth();

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

        if (!$user->trashed()) {
            $user->delete();
        }

        return $this->returnJSON(['id' => $user->id], 'User deactivated', 'success');
    }

    /**
     * Restore (activate) previously deactivated lab user.
     */
    public function restoreUser($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $user = User::withTrashed()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
        }

        return $this->returnJSON(['id' => $user->id], 'User activated', 'success');
    }

    /**
     * Roles list for medical laboratory.
     */
    public function roles()
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $roles = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->withCount('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions_count' => (int) $r->permissions_count,
                'users_count' => (int) $r->users_count,
            ]);

        return $this->returnJSON($roles, 'Roles', 'success');
    }

    /**
     * Permissions list for lab role forms.
     */
    public function permissions()
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $permissionIds = DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('roles.guard_name', $this->labGuardName())
            ->where('roles.team_id', $orgId)
            ->pluck('role_has_permissions.permission_id')
            ->unique()
            ->values();

        $permissions = Permission::query()
            ->where('guard_name', $this->labGuardName())
            ->whereIn('id', $permissionIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]);

        return $this->returnJSON($permissions, 'Permissions', 'success');
    }

    /**
     * Single role details including selected permissions.
     */
    public function roleDetails($id)
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $role = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->with('permissions:id,name')
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => $role->permissions->count(),
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($v) => (int) $v)->values(),
            'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ], 'Role details', 'success');
    }

    /**
     * Create role with selected permissions.
     */
    public function createRole(Request $request)
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $exists = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->where('name', trim($validated['name']))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Role name already exists for this laboratory.'],
            ]);
        }

        $role = Role::create([
            'name' => trim($validated['name']),
            'guard_name' => $this->labGuardName(),
            'team_id' => $orgId,
        ]);

        $permissions = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role created', 'success');
    }

    /**
     * Update role and selected permissions.
     */
    public function updateRole(Request $request, $id)
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $role = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $exists = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->where('name', trim($validated['name']))
            ->where('id', '!=', $role->id)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Role name already exists for this laboratory.'],
            ]);
        }

        $role->update(['name' => trim($validated['name'])]);

        $permissions = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role updated', 'success');
    }

    /**
     * Delete role.
     */
    public function deleteRole($id)
    {
        $this->ensureLabAuth();
        $orgId = request()->user()->organization_id;

        $role = Role::query()
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $orgId)
            ->findOrFail($id);

        $role->delete();

        return $this->returnJSON(['id' => (int) $id], 'Role deleted', 'success');
    }

    /**
     * Lab service categories list for dashboard pages.
     */
    public function serviceCategories(Request $request)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $query = LabServiceCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where('category_name', 'like', '%' . $search . '%');
            })
            ->withCount('labServices')
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($c) => [
            'id' => $c->id,
            'category_name' => $c->category_name,
            'is_active' => (bool) $c->is_active,
            'status' => $c->is_active ? 'active' : 'inactive',
            'services_count' => (int) $c->lab_services_count,
            'created_at' => optional($c->created_at)->format('Y-m-d'),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Lab service categories', 'success');
    }

    /**
     * Single lab service category details.
     */
    public function serviceCategoryDetails($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $category = LabServiceCategory::query()
            ->withCount('labServices')
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $category->id,
            'category_name' => $category->category_name,
            'is_active' => (bool) $category->is_active,
            'status' => $category->is_active ? 'active' : 'inactive',
            'services_count' => (int) $category->lab_services_count,
            'created_at' => optional($category->created_at)->format('Y-m-d'),
        ], 'Lab service category details', 'success');
    }

    /**
     * Create a new lab service category.
     */
    public function createServiceCategory(Request $request)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $authUser = request()->user();
        $category = LabServiceCategory::create([
            'category_name' => $validated['category_name'],
            'is_active' => (bool) $validated['is_active'],
            'organization_id' => $authUser->organization_id,
            'organization_type' => $authUser->organization_type,
        ]);

        return $this->returnJSON(['id' => $category->id], 'Lab service category created', 'success');
    }

    /**
     * Update an existing lab service category.
     */
    public function updateServiceCategory(Request $request, $id)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $authUser = request()->user();
        $category = LabServiceCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $category->update([
            'category_name' => $validated['category_name'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        return $this->returnJSON(['id' => $category->id], 'Lab service category updated', 'success');
    }

    /**
     * Delete a lab service category.
     */
    public function deleteServiceCategory($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $category = LabServiceCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $category->delete();

        return $this->returnJSON(['id' => (int) $id], 'Lab service category deleted', 'success');
    }

    /**
     * Lab services list for dashboard pages.
     */
    public function services(Request $request)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $query = LabService::query()
            ->with('category:id,category_name')
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->when($request->filled('category_id'), fn ($q) => $q->where('lab_service_category_id', $request->category_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('unit', 'like', '%' . $search . '%')
                        ->orWhere('normal_range', 'like', '%' . $search . '%')
                        ->orWhereHas('category', fn ($catQuery) => $catQuery->where('category_name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'price' => (string) ($s->price ?? 0),
            'unit' => $s->unit,
            'normal_range' => $s->normal_range,
            'notes' => $s->notes,
            'lab_service_category_id' => $s->lab_service_category_id,
            'category_name' => $s->category?->category_name,
            'created_at' => optional($s->created_at)->format('Y-m-d'),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Lab services', 'success');
    }

    /**
     * Single lab service details.
     */
    public function serviceDetails($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $service = LabService::query()
            ->with('category:id,category_name')
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $service->id,
            'name' => $service->name,
            'price' => (string) ($service->price ?? 0),
            'unit' => $service->unit,
            'normal_range' => $service->normal_range,
            'notes' => $service->notes,
            'lab_service_category_id' => $service->lab_service_category_id,
            'category_name' => $service->category?->category_name,
            'created_at' => optional($service->created_at)->format('Y-m-d'),
        ], 'Lab service details', 'success');
    }

    /**
     * Create a new lab service.
     */
    public function createService(Request $request)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'lab_service_category_id' => 'required|integer|exists:lab_service_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:100',
            'normal_range' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $authUser = request()->user();
        $category = LabServiceCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($validated['lab_service_category_id']);

        $service = LabService::create([
            'lab_service_category_id' => $category->id,
            'name' => $validated['name'],
            'price' => (float) $validated['price'],
            'unit' => $validated['unit'],
            'normal_range' => $validated['normal_range'],
            'notes' => $validated['notes'] ?? null,
            'organization_id' => $authUser->organization_id,
            'organization_type' => $authUser->organization_type,
        ]);

        return $this->returnJSON(['id' => $service->id], 'Lab service created', 'success');
    }

    /**
     * Update an existing lab service.
     */
    public function updateService(Request $request, $id)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'lab_service_category_id' => 'required|integer|exists:lab_service_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:100',
            'normal_range' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $authUser = request()->user();
        $category = LabServiceCategory::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($validated['lab_service_category_id']);

        $service = LabService::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $service->update([
            'lab_service_category_id' => $category->id,
            'name' => $validated['name'],
            'price' => (float) $validated['price'],
            'unit' => $validated['unit'],
            'normal_range' => $validated['normal_range'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->returnJSON(['id' => $service->id], 'Lab service updated', 'success');
    }

    /**
     * Delete a lab service.
     */
    public function deleteService($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $service = LabService::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $service->delete();

        return $this->returnJSON(['id' => (int) $id], 'Lab service deleted', 'success');
    }

    /**
     * Medical analyses list for dashboard pages.
     */
    public function medicalAnalyses(Request $request)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $query = MedicalAnalysis::query()
            ->with(['patient:id,name', 'labServiceOptions'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $request->date))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment', $request->payment))
            ->when($request->filled('doctor_name') || $request->filled('doctor'), function ($q) use ($request) {
                $doctor = trim((string) ($request->doctor_name ?? $request->doctor));
                if ($doctor !== '') {
                    $q->where('doctor_name', 'like', '%' . $doctor . '%');
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('doctor_name', 'like', '%' . $search . '%')
                        ->orWhere('date', 'like', '%' . $search . '%')
                        ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($a) => [
            'id' => $a->id,
            'patient_id' => $a->patient_id,
            'patient_name' => $a->patient?->name ?? 'N/A',
            'date' => $a->date,
            'doctor_name' => $a->doctor_name,
            'payment' => $a->payment,
            'cost' => (string) ($a->cost ?? 0),
            'report' => $a->report,
            'services_count' => $a->labServiceOptions->count(),
            'created_at' => optional($a->created_at)->format('Y-m-d'),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Medical analyses', 'success');
    }

    /**
     * Single medical analysis details with selected services/options.
     */
    public function medicalAnalysisDetails($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $analysis = MedicalAnalysis::query()
            ->with(['patient:id,name', 'labServiceOptions.labService:id,lab_service_category_id,name'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $services = $analysis->labServiceOptions->map(fn ($option) => [
            'id' => $option->id,
            'option_id' => $option->id,
            'lab_service_id' => $option->lab_service_id,
            'lab_service_category_id' => $option->lab_service_category_id,
            'name' => $option->name,
            'price' => (string) ($option->price ?? 0),
            'value' => $option->value,
            'unit' => $option->unit,
            'normal_range' => $option->normal_range,
            'images' => $option->getMedia('service_fee_images')->map(fn ($media) => $media->getUrl())->values(),
        ])->values();

        return $this->returnJSON([
            'id' => $analysis->id,
            'patient_id' => $analysis->patient_id,
            'patient_name' => $analysis->patient?->name ?? 'N/A',
            'reservation_id' => $analysis->reservation_id,
            'date' => $analysis->date,
            'doctor_name' => $analysis->doctor_name,
            'payment' => $analysis->payment,
            'cost' => (string) ($analysis->cost ?? 0),
            'report' => $analysis->report,
            'services' => $services,
            'created_at' => optional($analysis->created_at)->format('Y-m-d'),
        ], 'Medical analysis details', 'success');
    }

    /**
     * Create a medical analysis with selected services values.
     */
    public function createMedicalAnalysis(Request $request)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'date' => 'required|date',
            'doctor_name' => 'nullable|string|max:255',
            'payment' => 'required|in:paid,not_paid',
            'report' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*.lab_service_id' => 'required|integer|exists:lab_services,id',
            'services.*.value' => 'nullable|string|max:255',
            'services.*.images' => 'nullable|array',
            'services.*.images.*' => 'nullable|file|max:10240',
        ]);

        $authUser = request()->user();

        $analysis = DB::transaction(function () use ($validated, $authUser, $request) {
            $analysis = MedicalAnalysis::create([
                'patient_id' => $validated['patient_id'],
                'reservation_id' => $validated['reservation_id'] ?? null,
                'date' => $validated['date'],
                'doctor_name' => $validated['doctor_name'] ?? null,
                'payment' => $validated['payment'],
                'report' => $validated['report'] ?? null,
                'cost' => '0',
                'organization_id' => $authUser->organization_id,
                'organization_type' => $authUser->organization_type,
            ]);

            $total = 0;
            foreach (($validated['services'] ?? []) as $index => $row) {
                $service = LabService::query()
                    ->where('organization_id', $authUser->organization_id)
                    ->where('organization_type', $authUser->organization_type)
                    ->findOrFail($row['lab_service_id']);

                $price = (float) ($service->price ?? 0);
                $total += $price;

                $option = LabServiceOption::create([
                    'lab_service_id' => $service->id,
                    'lab_service_category_id' => $service->lab_service_category_id,
                    'module_id' => $analysis->id,
                    'module_type' => MedicalAnalysis::class,
                    'name' => $service->name,
                    'price' => $price,
                    'value' => $row['value'] ?? null,
                    'unit' => $service->unit,
                    'normal_range' => $service->normal_range,
                ]);

                if ($request->hasFile("services.$index.images")) {
                    foreach ($request->file("services.$index.images") as $image) {
                        $option->addMedia($image)->toMediaCollection('service_fee_images');
                    }
                }
            }

            $analysis->update(['cost' => (string) $total]);

            return $analysis;
        });

        return $this->returnJSON(['id' => $analysis->id], 'Medical analysis created', 'success');
    }

    /**
     * Update medical analysis and replace selected service rows.
     */
    public function updateMedicalAnalysis(Request $request, $id)
    {
        $this->ensureLabAuth();

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'date' => 'required|date',
            'doctor_name' => 'nullable|string|max:255',
            'payment' => 'required|in:paid,not_paid',
            'report' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*.option_id' => 'nullable|integer|exists:lab_service_options,id',
            'services.*.lab_service_id' => 'required|integer|exists:lab_services,id',
            'services.*.value' => 'nullable|string|max:255',
            'services.*.images' => 'nullable|array',
            'services.*.images.*' => 'nullable|file|max:10240',
        ]);

        $authUser = request()->user();

        $analysis = DB::transaction(function () use ($validated, $authUser, $id, $request) {
            $analysis = MedicalAnalysis::query()
                ->where('organization_id', $authUser->organization_id)
                ->where('organization_type', $authUser->organization_type)
                ->findOrFail($id);

            $analysis->update([
                'patient_id' => $validated['patient_id'],
                'reservation_id' => $validated['reservation_id'] ?? null,
                'date' => $validated['date'],
                'doctor_name' => $validated['doctor_name'] ?? null,
                'payment' => $validated['payment'],
                'report' => $validated['report'] ?? null,
                'cost' => '0',
            ]);

            $existingOptions = LabServiceOption::query()
                ->where('module_id', $analysis->id)
                ->where('module_type', MedicalAnalysis::class)
                ->get()
                ->keyBy('id');

            $total = 0;
            $submittedOptionIds = [];
            foreach (($validated['services'] ?? []) as $index => $row) {
                $service = LabService::query()
                    ->where('organization_id', $authUser->organization_id)
                    ->where('organization_type', $authUser->organization_type)
                    ->findOrFail($row['lab_service_id']);

                $price = (float) ($service->price ?? 0);
                $total += $price;

                $optionId = isset($row['option_id']) ? (int) $row['option_id'] : null;
                $option = $optionId && $existingOptions->has($optionId)
                    ? $existingOptions->get($optionId)
                    : new LabServiceOption([
                        'module_id' => $analysis->id,
                        'module_type' => MedicalAnalysis::class,
                    ]);

                $option->fill([
                    'lab_service_id' => $service->id,
                    'lab_service_category_id' => $service->lab_service_category_id,
                    'name' => $service->name,
                    'price' => $price,
                    'value' => $row['value'] ?? null,
                    'unit' => $service->unit,
                    'normal_range' => $service->normal_range,
                ]);
                $option->save();

                $submittedOptionIds[] = $option->id;

                if ($request->hasFile("services.$index.images")) {
                    foreach ($request->file("services.$index.images") as $image) {
                        $option->addMedia($image)->toMediaCollection('service_fee_images');
                    }
                }
            }

            LabServiceOption::query()
                ->where('module_id', $analysis->id)
                ->where('module_type', MedicalAnalysis::class)
                ->when(!empty($submittedOptionIds), fn ($q) => $q->whereNotIn('id', $submittedOptionIds))
                ->when(empty($submittedOptionIds), fn ($q) => $q)
                ->delete();

            $analysis->update(['cost' => (string) $total]);

            return $analysis;
        });

        return $this->returnJSON(['id' => $analysis->id], 'Medical analysis updated', 'success');
    }

    /**
     * Delete medical analysis.
     */
    public function deleteMedicalAnalysis($id)
    {
        $this->ensureLabAuth();

        $authUser = request()->user();
        $analysis = MedicalAnalysis::query()
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $analysis->delete();

        return $this->returnJSON(['id' => (int) $id], 'Medical analysis deleted', 'success');
    }

    private function pagination($paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ];
    }

    private function resolveLabRole(int $roleId, int $organizationId): Role
    {
        $role = Role::query()
            ->where('id', $roleId)
            ->where('guard_name', $this->labGuardName())
            ->where('team_id', $organizationId)
            ->first();

        if (!$role) {
            throw ValidationException::withMessages([
                'role_id' => ['Selected role is invalid for this laboratory.'],
            ]);
        }

        return $role;
    }

    private function labGuardName(): string
    {
        return 'medical_laboratory';
    }

    private function ensureLabAuth(): void
    {
        if (!request()->user()) {
            abort(401, 'Unauthenticated');
        }
    }

    private function assignPatientToCurrentLab(int $patientId): bool
    {
        $authUser = request()->user();
        $organizationId = $authUser->organization_id;

        $existing = DB::table('patient_organization')
            ->where('patient_id', $patientId)
            ->where('organization_id', $organizationId)
            ->where('organization_type', MedicalLaboratory::class)
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
            'organization_type' => MedicalLaboratory::class,
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
}
