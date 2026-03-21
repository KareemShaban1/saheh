<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\User\Models\User;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Modules\Clinic\User\Models\UserDoctor;
use App\Models\Shared\PatientReview;
use Modules\Clinic\Announcement\Models\Announcement;
use App\Models\Service;
use App\Models\ModuleService;
use App\Models\Settings;
use App\Models\Clinic;
use App\Models\Specialty;
use App\Models\Governorate;
use App\Models\City;
use App\Models\Area;
use App\Models\Shared\OrganizationInventory;
use App\Models\Shared\InventoryMovement;
use Modules\Clinic\Chat\Models\Chat;
use Modules\Clinic\Prescription\Models\Prescription;
use Modules\Clinic\Prescription\Models\Drug;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use App\Models\Ray;
use App\Models\ToothRecord;
use App\Models\ReservationTooth;
use App\Notifications\MakeAppointmentNotification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
use App\Http\Controllers\BaseFrontApiController;
/**
 * Front API: Clinic dashboard pages (JSON for React frontend)
 */
class ClinicDashboardApiController extends BaseFrontApiController
{

    /**
     * Dashboard overview (stats, recent reservations, etc.)
     */
    public function dashboard()
    {
        $this->ensureClinicAuth();
        $current_date = Carbon::now('Egypt')->format('Y-m-d');
        $current_month = Carbon::now('Egypt')->format('m');
        $clinic_id = request()->user()->organization->id ?? null;
        if (!$clinic_id) {
            return $this->returnJSON(['stats' => [], 'reservations' => [], 'recent' => []], 'No organization', 'success');
        }

        $stats = [
            ['label' => "Today's Reservations", 'value' => (string) Reservation::where('clinic_id', $clinic_id)->where('date', $current_date)->count(), 'change' => '+0%', 'color' => 'text-primary'],
            ['label' => 'Total Patients', 'value' => (string) Patient::query()->clinic()->count(), 'change' => '+0%', 'color' => 'text-secondary'],
            ['label' => 'Revenue (Month)', 'value' => 'EGP ' . Reservation::where('clinic_id', $clinic_id)->where('month', $current_month)->where('payment', 'paid')->sum('cost'), 'change' => '+0%', 'color' => 'text-success'],
            ['label' => "Today's Payments", 'value' => 'EGP ' . Reservation::where('clinic_id', $clinic_id)->where('date', $current_date)->sum('cost'), 'change' => '-0%', 'color' => 'text-accent'],
        ];

        $reservations = Reservation::with(['patient:id,name', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->where('clinic_id', $clinic_id)
            ->where('date', $current_date)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'patient_name' => $r->patient->name ?? 'N/A',
                    'doctor_name' => $r->doctor?->user?->name ?? 'N/A',
                    'time' => $r->slot ?? $r->time ?? '—',
                    'status' => $r->acceptance ?? $r->res_status ?? 'pending',
                    'date' => $r->date,
                ];
            });

        return $this->returnJSON(compact('stats', 'reservations'), 'Dashboard data', 'success');
    }

    /**
     * Financial module data (summary + monthly trend).
     */
    public function financial(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization_id ?? null;
        if (!$clinicId) {
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
        $base = Reservation::withoutGlobalScope(\App\Models\Scopes\ClinicScope::class)
            ->where('clinic_id', $clinicId);

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
     * Specialties list for doctor forms.
     */
    public function specialties()
    {
        $this->ensureClinicAuth();

        $specialties = Specialty::query()
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'name_en' => $item->name_en,
                'name_ar' => $item->name_ar,
                'name' => $item->name_en ?: $item->name_ar,
            ]);

        return $this->returnJSON($specialties, 'Specialties', 'success');
    }





    /**
     * Reservation number settings page data
     */
    public function reservationNumbers(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization_id ?? null;
        $query = ReservationNumber::with(['doctor.user'])
            ->where('clinic_id', $clinicId)
            ->orderBy('reservation_date', 'desc')
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'doctor_id' => $n->doctor_id,
            'doctor_name' => $n->doctor?->user?->name ?? 'N/A',
            'reservation_date' => $n->reservation_date,
            'num_of_reservations' => (int) $n->num_of_reservations,
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Reservation numbers', 'success');
    }

    /**
     * Reservation slot settings page data
     */
    public function reservationSlots(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization_id ?? null;
        $query = ReservationSlots::with(['doctor.user'])
            ->where('clinic_id', $clinicId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'doctor_id' => $s->doctor_id,
            'doctor_name' => $s->doctor?->user?->name ?? 'N/A',
            'date' => $s->date,
            'start_time' => $s->start_time,
            'end_time' => $s->end_time,
            'duration' => (int) $s->duration,
            'total_reservations' => (int) ($s->total_reservations ?? 0),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Reservation slots', 'success');
    }

    /**
     * Reviews page data
     */
    public function reviews(Request $request)
    {
        $this->ensureClinicAuth();
        $user = request()->user();
        $query = PatientReview::with(['patient:id,name'])
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $isActive = $request->status === 'published' ? 1 : ($request->status === 'hidden' ? 0 : null);
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }
        }

        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'patient' => $r->patient?->name ?? 'N/A',
            'rating' => (int) ($r->rating ?? 0),
            'comment' => $r->comment ?? '',
            'status' => ($r->is_active ? 'published' : 'hidden'),
            'date' => optional($r->created_at)->format('Y-m-d'),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Reviews', 'success');
    }

    /**
     * Announcements page data
     */
    public function announcements(Request $request)
    {
        $this->ensureClinicAuth();
        $user = request()->user();
        $query = Announcement::query()
            ->where('organization_id', $user->organization_id)
            ->where('organization_type', $user->organization_type)
            ->orderBy('id', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'content' => $a->body,
            'audience' => $a->type ?? 'all',
            'channel' => ($a->send_notification ? 'sms' : 'in-app'),
            'createdAt' => optional($a->created_at)->format('Y-m-d'),
            'status' => $a->is_active ? 'sent' : 'draft',
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Announcements', 'success');
    }

    /**
     * Notifications feed for clinic dashboard.
     */
    public function notifications(Request $request)
    {
        $this->ensureClinicAuth();
        $user = request()->user();
        $perPage = (int) $request->get('per_page', 20);

        $paginated = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($notification) {
            $payload = is_array($notification->data) ? $notification->data : [];
            $module = $payload['module']
                ?? $notification->module
                ?? 'general';

            return [
                'id' => (string) $notification->id,
                'type' => $payload['type']
                    ?? $notification->event
                    ?? $notification->type,
                'module' => (string) $module,
                'event' => $payload['event'] ?? $notification->event,
                'title' => $payload['title'] ?? 'Notification',
                'message' => $payload['message']
                    ?? $payload['body']
                    ?? $payload['content']
                    ?? '',
                'priority' => $payload['priority'] ?? 'medium',
                'action_url' => $payload['action_url']
                    ?? $notification->action_url
                    ?? null,
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
     * Clinic users page data
     */
    public function users(Request $request)
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $orgType = request()->user()->organization_type;
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);

        $query = User::with('roles')
            ->where('organization_id', $orgId)
            ->where('organization_type', $orgType)
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

        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone ?? null,
            'job_title' => $u->job_title,
            'role' => $u->roles->first()?->name ?? 'staff',
            'role_id' => $u->roles->first()?->id,
            'roles' => $u->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values(),
            'permissions_count' => $u->getAllPermissions()->count(),
            'status' => $u->deleted_at ? 'inactive' : 'active',
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Users', 'success');
    }

    /**
     * Single clinic user details with role and permissions.
     */
    public function userDetails($id)
    {
        $this->ensureClinicAuth();

        $authUser = request()->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
        $user = User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->where('organization_id', $authUser->organization_id)
            ->where('organization_type', $authUser->organization_type)
            ->findOrFail($id);

        $role = $user->roles->first();
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
     * Create clinic user with role and direct permissions.
     */
    public function createUser(Request $request)
    {
        $this->ensureClinicAuth();

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

        $role = $this->resolveClinicRole((int) $validated['role_id'], (int) $authUser->organization_id);
        $permissionNames = Permission::query()
            ->where('guard_name', 'web')
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
     * Update clinic user with role and direct permissions.
     */
    public function updateUser(Request $request, $id)
    {
        $this->ensureClinicAuth();

        $authUser = request()->user();
        $user = User::query()
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

        $role = $this->resolveClinicRole((int) $validated['role_id'], (int) $authUser->organization_id);
        $permissionNames = Permission::query()
            ->where('guard_name', 'web')
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
     * Soft-delete (deactivate) clinic user.
     */
    public function deactivateUser($id)
    {
        $this->ensureClinicAuth();

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
     * Restore (activate) previously deactivated clinic user.
     */
    public function restoreUser($id)
    {
        $this->ensureClinicAuth();

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
     * Clinic settings/details for dashboard profile page.
     */
    public function clinicSettings()
    {
        $this->ensureClinicAuth();

        $authUser = request()->user();
        $clinic = Clinic::query()->findOrFail((int) $authUser->organization_id);
        $logoPath = trim((string) ($clinic->logo ?? ''));
        $logoUrl = null;
        if ($logoPath !== '') {
            $logoUrl = Str::startsWith($logoPath, ['http://', 'https://']) ? $logoPath : asset($logoPath);
        }

        $governorates = Governorate::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($item) => ['id' => (int) $item->id, 'name' => $item->name])
            ->values();

        $cities = City::query()
            ->orderBy('name')
            ->get(['id', 'name', 'governorate_id'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'governorate_id' => (int) $item->governorate_id,
            ])
            ->values();

        $areas = Area::query()
            ->orderBy('name')
            ->get(['id', 'name', 'city_id', 'governorate_id'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'city_id' => (int) $item->city_id,
                'governorate_id' => (int) $item->governorate_id,
            ])
            ->values();

        $specialties = Specialty::query()
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name_en ?: $item->name_ar,
                'name_en' => $item->name_en,
                'name_ar' => $item->name_ar,
            ])
            ->values();

        return $this->returnJSON([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'email' => $clinic->email,
            'phone' => $clinic->phone,
            'address' => $clinic->address,
            'description' => $clinic->description,
            'website' => $clinic->website,
            'logo' => $clinic->logo,
            'logo_url' => $logoUrl,
            'governorate_id' => $clinic->governorate_id ? (int) $clinic->governorate_id : null,
            'city_id' => $clinic->city_id ? (int) $clinic->city_id : null,
            'area_id' => $clinic->area_id ? (int) $clinic->area_id : null,
            'specialty_id' => $clinic->specialty_id ? (int) $clinic->specialty_id : null,
            'governorates' => $governorates,
            'cities' => $cities,
            'areas' => $areas,
            'specialties' => $specialties,
        ], 'Clinic settings', 'success');
    }

    /**
     * Update clinic settings/details (including logo upload).
     */
    public function updateClinicSettings(Request $request)
    {
        $this->ensureClinicAuth();

        $authUser = request()->user();
        $clinic = Clinic::query()->findOrFail((int) $authUser->organization_id);

        foreach (['governorate_id', 'city_id', 'area_id', 'specialty_id'] as $field) {
            if ($request->has($field) && trim((string) $request->input($field)) === '') {
                $request->merge([$field => null]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clinics,email,' . $clinic->id,
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:5000',
            'website' => 'nullable|url|max:255',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'logo' => 'nullable|image|max:4096',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'governorate_id' => $validated['governorate_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $directory = public_path('uploads/clinic-logos');
            if (!is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }

            $filename = 'clinic-' . $clinic->id . '-' . time() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->move($directory, $filename);
            $newLogoPath = 'uploads/clinic-logos/' . $filename;

            $oldLogo = trim((string) ($clinic->logo ?? ''));
            if ($oldLogo !== '' && !Str::startsWith($oldLogo, ['http://', 'https://'])) {
                $oldFullPath = public_path($oldLogo);
                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }

            $payload['logo'] = $newLogoPath;
        }

        $clinic->update($payload);
        $clinic->refresh();

        $logoPath = trim((string) ($clinic->logo ?? ''));
        $logoUrl = null;
        if ($logoPath !== '') {
            $logoUrl = Str::startsWith($logoPath, ['http://', 'https://']) ? $logoPath : asset($logoPath);
        }

        return $this->returnJSON([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'email' => $clinic->email,
            'phone' => $clinic->phone,
            'address' => $clinic->address,
            'description' => $clinic->description,
            'website' => $clinic->website,
            'logo' => $clinic->logo,
            'logo_url' => $logoUrl,
            'governorate_id' => $clinic->governorate_id ? (int) $clinic->governorate_id : null,
            'city_id' => $clinic->city_id ? (int) $clinic->city_id : null,
            'area_id' => $clinic->area_id ? (int) $clinic->area_id : null,
            'specialty_id' => $clinic->specialty_id ? (int) $clinic->specialty_id : null,
        ], 'Clinic settings updated', 'success');
    }

    /**
     * Modules page data (derived from configured service types)
     */
    public function modules(Request $request)
    {
        $this->ensureClinicAuth();
        $services = Service::query()->get(['id', 'type']);
        $grouped = $services->groupBy(fn ($s) => $s->type ?? 'general');
        $data = $grouped->map(function ($rows, $type) {
            $key = strtolower(str_replace(' ', '_', (string) $type));
            return [
                'id' => $key,
                'name' => ucfirst((string) $type),
                'key' => $key,
                'description' => 'Configured module based on clinic services',
                'billing' => 'Included',
                'status' => $rows->count() > 0 ? 'enabled' : 'disabled',
                'services_count' => $rows->count(),
            ];
        })->values();

        return $this->returnJSON($data, 'Modules', 'success');
    }

    /**
     * Inventory categories page data
     */
    public function inventoryCategories(Request $request)
    {
        $this->ensureClinicAuth();
        $query = OrganizationInventory::query()->fromSameOrganization()->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'code' => strtoupper(substr(preg_replace('/\s+/', '', (string) $c->name), 0, 3)),
            'type' => $c->unit ?? 'item',
            'description' => $c->description,
            'status' => 'active',
            'quantity' => (float) ($c->quantity ?? 0),
            'price' => (float) ($c->price ?? 0),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Inventory categories', 'success');
    }

    /**
     * Inventory movements page data
     */
    public function inventoryMovements(Request $request)
    {
        $this->ensureClinicAuth();
        $query = InventoryMovement::with('inventory')
            ->whereHas('inventory', function ($q) {
                $q->fromSameOrganization();
            })
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($m) => [
            'id' => $m->id,
            'date' => optional($m->movement_date)->format('Y-m-d H:i'),
            'item' => $m->inventory?->name ?? 'N/A',
            'category' => $m->inventory?->name ?? 'N/A',
            'type' => $m->type,
            'quantity' => (float) $m->quantity,
            'reference' => null,
            'note' => $m->notes,
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Inventory movements', 'success');
    }

    /**
     * Chat page data
     */
    public function chats(Request $request)
    {
        $this->ensureClinicAuth();
        $userId = request()->user()->id;
        $chats = Chat::with(['patient:id,name', 'messages' => function ($q) {
            $q->latest('id');
        }])
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $conversations = $chats->map(function ($chat) {
            $last = $chat->messages->first();
            return [
                'id' => (string) $chat->id,
                'patient' => $chat->patient?->name ?? 'N/A',
                'channel' => 'in-app',
                'lastMessage' => $last?->message ?? '',
                'updatedAt' => optional($last?->created_at ?? $chat->updated_at)->format('Y-m-d H:i'),
                'unread' => 0,
            ];
        });

        return $this->returnJSON($conversations, 'Chats', 'success');
    }

    private function resolveClinicRole(int $roleId, int $organizationId): Role
    {
        $role = Role::query()
            ->where('id', $roleId)
            ->where('guard_name', 'web')
            ->where(function ($query) use ($organizationId) {
                $query->where('team_id', $organizationId)
                    ->orWhereNull('team_id');
            })
            ->first();

        if (!$role) {
            throw ValidationException::withMessages([
                'role_id' => ['Selected role is invalid for this clinic.'],
            ]);
        }

        return $role;
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
