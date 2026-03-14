<?php

namespace App\Http\Controllers\FrontApis\clinic;

use App\Http\Controllers\Controller;
use App\Traits\ApiHelperTrait;
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

/**
 * Front API: Clinic dashboard pages (JSON for React frontend)
 */
class ClinicDashboardApiController extends Controller
{
    use ApiHelperTrait;

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
     * Reservations list (data for table)
     */
    public function reservationsData(Request $request)
    {
        $this->ensureClinicAuth();
        $clinic_id = request()->user()->organization->id ?? null;
        if (!$clinic_id) {
            return $this->returnJSON(['data' => [], 'pagination' => []], 'No organization', 'success');
        }

        $query = Reservation::with(['patient:id,name,phone', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->where('clinic_id', $clinic_id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('acceptance', $request->status);
        }

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($r) {
            return [
                'id' => $r->id,
                'patient_id' => $r->patient_id,
                'doctor_id' => $r->doctor_id,
                'patient_name' => $r->patient->name ?? 'N/A',
                'patient_phone' => $r->patient->phone ?? null,
                'doctor_name' => $r->doctor?->user?->name ?? 'N/A',
                'date' => $r->date,
                'time' => $r->slot ?? $r->time ?? $r->start_time ?? null,
                'reservation_number' => $r->reservation_number,
                'slot' => $r->slot,
                'status' => $r->status ?? $r->acceptance ?? $r->res_status ?? 'pending',
                'acceptance' => $r->acceptance ?? 'pending',
                'payment' => $r->payment ?? null,
                'month' => $r->month ?? null,
            ];
        });

        return $this->returnJSON([
            'data' => $data,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Reservations', 'success');
    }

    /**
     * Doctors list for clinic
     */
    public function doctors()
    {
        $this->ensureClinicAuth();
        $doctorsQuery = Doctor::with('ServicesWithoutScope');
        $doctors = $doctorsQuery
            ->with(['user:id,name,email', 'specialty:id,name_en,name_ar'])
            ->get(['id', 'user_id', 'phone', 'certifications', 'specialty_id'])
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->user?->name ?? 'N/A',
                    'email' => $d->user?->email ?? null,
                    'phone' => $d->phone ?? null,
                    'certifications' => $d->certifications ?? null,
                    'specialty_id' => $d->specialty_id,
                    'specialty_name' => $d->specialty?->name_en ?? $d->specialty?->name_ar ?? null,
                ];
            });
        return $this->returnJSON($doctors, 'Doctors', 'success');
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
     * Single doctor details.
     */
    public function doctorDetails($id)
    {
        $this->ensureClinicAuth();

        $doctor = Doctor::query()
            ->with(['user:id,name,email', 'specialty:id,name_en,name_ar'])
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $doctor->id,
            'name' => $doctor->user?->name ?? 'N/A',
            'email' => $doctor->user?->email ?? null,
            'phone' => $doctor->phone ?? null,
            'certifications' => $doctor->certifications ?? null,
            'specialty_id' => $doctor->specialty_id,
            'specialty_name' => $doctor->specialty?->name_en ?? $doctor->specialty?->name_ar ?? null,
        ], 'Doctor details', 'success');
    }

    /**
     * Create doctor and linked user record.
     */
    public function createDoctor(Request $request)
    {
        $this->ensureClinicAuth();

        $authUser = request()->user();
        $clinicId = $authUser->organization->id ?? null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'phone' => 'required|string|max:30',
            'certifications' => 'required|string|max:1000',
            'specialty_id' => 'required|exists:specialties,id',
        ]);

        $doctor = DB::transaction(function () use ($validated, $authUser, $clinicId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'job_title' => 'doctor',
                'organization_type' => Clinic::class,
                'organization_id' => $authUser->organization_id,
            ]);

            $user->assignRole('clinic-doctor');

            return Doctor::create([
                'user_id' => $user->id,
                'clinic_id' => $clinicId,
                'phone' => $validated['phone'],
                'certifications' => $validated['certifications'],
                'specialty_id' => $validated['specialty_id'],
            ]);
        });

        $doctor->load(['user:id,name,email', 'specialty:id,name_en,name_ar']);

        return $this->returnJSON([
            'id' => $doctor->id,
            'name' => $doctor->user?->name ?? 'N/A',
            'email' => $doctor->user?->email ?? null,
            'phone' => $doctor->phone ?? null,
            'certifications' => $doctor->certifications ?? null,
            'specialty_id' => $doctor->specialty_id,
            'specialty_name' => $doctor->specialty?->name_en ?? $doctor->specialty?->name_ar ?? null,
        ], 'Doctor created', 'success');
    }

    /**
     * Update doctor and linked user record.
     */
    public function updateDoctor(Request $request, $id)
    {
        $this->ensureClinicAuth();

        $doctor = Doctor::query()->with('user:id,name,email,password')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $doctor->user_id,
            'password' => 'nullable|string|min:6|max:255',
            'phone' => 'required|string|max:30',
            'certifications' => 'required|string|max:1000',
            'specialty_id' => 'required|exists:specialties,id',
        ]);

        DB::transaction(function () use ($doctor, $validated) {
            $doctor->update([
                'phone' => $validated['phone'],
                'certifications' => $validated['certifications'],
                'specialty_id' => $validated['specialty_id'],
            ]);

            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $doctor->user()->update($userData);
        });

        $doctor->refresh()->load(['user:id,name,email', 'specialty:id,name_en,name_ar']);

        return $this->returnJSON([
            'id' => $doctor->id,
            'name' => $doctor->user?->name ?? 'N/A',
            'email' => $doctor->user?->email ?? null,
            'phone' => $doctor->phone ?? null,
            'certifications' => $doctor->certifications ?? null,
            'specialty_id' => $doctor->specialty_id,
            'specialty_name' => $doctor->specialty?->name_en ?? $doctor->specialty?->name_ar ?? null,
        ], 'Doctor updated', 'success');
    }

    /**
     * Single reservation details (for edit modal)
     */
    public function reservationDetails($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::with([
            'patient:id,name',
            'doctor:id,user_id',
            'doctor.user:id,name',
            'Services.service:id,service_name',
        ])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $payload = [
            'id' => $reservation->id,
            'patient_id' => $reservation->patient_id,
            'doctor_id' => $reservation->doctor_id,
            'patient_name' => $reservation->patient?->name ?? 'N/A',
            'doctor_name' => $reservation->doctor?->user?->name ?? 'N/A',
            'date' => $reservation->date,
            'time' => $reservation->slot ?? null,
            'reservation_number' => $reservation->reservation_number,
            'slot' => $reservation->slot,
            'reservation_mode' => $reservation->reservation_number ? 'numbers' : 'slots',
            'status' => $reservation->status ?? 'waiting',
            'acceptance' => $reservation->acceptance ?? 'pending',
            'payment' => $reservation->payment ?? 'not_paid',
            'cost' => (float) ($reservation->cost ?? 0),
            'month' => $reservation->month ?? null,
            'first_diagnosis' => $reservation->first_diagnosis,
            'final_diagnosis' => $reservation->final_diagnosis,
            'voice_records' => $reservation->getMedia('reservation_voice_records')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'file_name' => $media->file_name,
                'size' => $media->size,
            ])->values(),
            'services' => $reservation->Services->map(fn ($sf) => [
                'id' => $sf->id,
                'service_fee_id' => $sf->service_fee_id,
                'service_name' => $sf->service?->service_name ?? 'N/A',
                'fee' => (float) ($sf->fee ?? 0),
                'notes' => $sf->notes,
            ])->values(),
        ];

        return $this->returnJSON($payload, 'Reservation details', 'success');
    }

    /**
     * Reservation options based on clinic setting (numbers or slots).
     */
    public function reservationOptions(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        if (!$clinicId) {
            return $this->returnJSON(['mode' => 'numbers', 'available_values' => []], 'No organization', 'success');
        }

        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
        ]);

        $mode = $this->resolveReservationMode($clinicId);
        $doctorId = (int) $validated['doctor_id'];
        $date = $validated['date'];
        $reservationId = isset($validated['reservation_id']) ? (int) $validated['reservation_id'] : null;

        $availableValues = [];
        $allValues = [];
        $reservedValues = [];
        if ($mode === 'numbers') {
            $count = (int) (ReservationNumber::query()
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('reservation_date', $date)
                ->value('num_of_reservations') ?? 0);

            $takenValues = Reservation::query()
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('date', $date)
                ->when($reservationId, fn ($q) => $q->where('id', '!=', $reservationId))
                ->whereNotNull('reservation_number')
                ->pluck('reservation_number')
                ->map(fn ($v) => (string) $v)
                ->toArray();
            $reservedValues = $takenValues;

            for ($i = 1; $i <= $count; $i++) {
                $value = (string) $i;
                $allValues[] = $value;
                if (!in_array($value, $takenValues, true)) {
                    $availableValues[] = $value;
                }
            }
        } else {
            $slotConfig = ReservationSlots::query()
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('date', $date)
                ->first();

            $allSlots = $this->generateSlots(
                (string) ($slotConfig->start_time ?? ''),
                (string) ($slotConfig->end_time ?? ''),
                (int) ($slotConfig->duration ?? 0),
            );

            $takenValues = Reservation::query()
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('date', $date)
                ->when($reservationId, fn ($q) => $q->where('id', '!=', $reservationId))
                ->whereNotNull('slot')
                ->pluck('slot')
                ->map(fn ($v) => substr((string) $v, 0, 5))
                ->toArray();
            $reservedValues = $takenValues;

            foreach ($allSlots as $slot) {
                $allValues[] = $slot;
                if (!in_array($slot, $takenValues, true)) {
                    $availableValues[] = $slot;
                }
            }
        }

        return $this->returnJSON([
            'mode' => $mode,
            'all_values' => $allValues,
            'reserved_values' => $reservedValues,
            'available_values' => $availableValues,
        ], 'Reservation options', 'success');
    }

    /**
     * Create reservation from front dashboard modal
     */
    public function createReservation(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        if ($request->has('services') && is_string($request->input('services'))) {
            $decodedServices = json_decode((string) $request->input('services'), true);
            if (is_array($decodedServices)) {
                $request->merge(['services' => $decodedServices]);
            }
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'time' => 'nullable|string|max:20',
            'reservation_number' => 'nullable|string|max:20',
            'slot' => 'nullable|string|max:20',
            'status' => 'nullable|in:waiting,entered,finished,cancelled',
            'acceptance' => 'required|in:pending,approved,not_approved',
            'payment' => 'required|in:paid,not_paid,unpaid',
            'month' => 'nullable|string|max:2',
            'first_diagnosis' => 'nullable|string|max:5000',
            'final_diagnosis' => 'nullable|string|max:5000',
            'services' => 'nullable|array',
            'services.*.service_fee_id' => 'required_with:services|exists:services,id',
            'services.*.fee' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:1000',
            'voice_records' => 'nullable|array',
            'voice_records.*' => 'file|mimes:mp3,wav,m4a,aac,ogg,webm|max:20480',
        ]);

        $mode = $this->resolveReservationMode((int) $clinicId);
        $selectedValue = $mode === 'numbers'
            ? (string) ($validated['reservation_number'] ?? '')
            : (string) ($validated['slot'] ?? $validated['time'] ?? '');
        $this->ensureReservationValueAvailable(
            (int) $clinicId,
            (int) $validated['doctor_id'],
            (string) $validated['date'],
            $mode,
            $selectedValue,
        );

        $payment = in_array($validated['payment'], ['not_paid', 'unpaid'], true) ? 'not_paid' : 'paid';
        $reservation = Reservation::create([
            'patient_id' => $validated['patient_id'],
            'clinic_id' => $clinicId,
            'doctor_id' => $validated['doctor_id'],
            'reservation_number' => $mode === 'numbers' ? $selectedValue : null,
            'cost' => 0,
            'payment' => $payment,
            'date' => $validated['date'],
            'status' => $validated['status'] ?? 'waiting',
            'acceptance' => $validated['acceptance'],
            'month' => $validated['month'] ?? Carbon::parse($validated['date'])->format('m'),
            'slot' => $mode === 'slots' ? $selectedValue : null,
            'first_diagnosis' => $validated['first_diagnosis'] ?? null,
            'final_diagnosis' => $validated['final_diagnosis'] ?? null,
        ]);

        $sum = 0;
        foreach (($validated['services'] ?? []) as $row) {
            $fee = (float) ($row['fee'] ?? 0);
            ModuleService::create([
                'module_id' => $reservation->id,
                'module_type' => Reservation::class,
                'service_fee_id' => $row['service_fee_id'],
                'fee' => $fee,
                'notes' => $row['notes'] ?? null,
            ]);
            $sum += $fee;
        }
        $reservation->update(['cost' => $sum]);

        if ($request->hasFile('voice_records')) {
            foreach ($request->file('voice_records') as $voiceFile) {
                $reservation->addMedia($voiceFile)->toMediaCollection('reservation_voice_records');
            }
        }

        // Notify clinic dashboard users about newly created reservation.
        $clinicUsers = User::query()
            ->where('organization_id', $clinicId)
            ->where('organization_type', Clinic::class)
            ->where('id', '!=', (int) request()->user()->id)
            ->get();
        foreach ($clinicUsers as $clinicUser) {
            $clinicUser->notify(new MakeAppointmentNotification($reservation));
        }

        return $this->returnJSON(['id' => $reservation->id], 'Reservation created', 'success');
    }

    /**
     * Update reservation from front dashboard modal
     */
    public function updateReservation(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::where('clinic_id', $clinicId)->findOrFail($id);

        if ($request->has('services') && is_string($request->input('services'))) {
            $decodedServices = json_decode((string) $request->input('services'), true);
            if (is_array($decodedServices)) {
                $request->merge(['services' => $decodedServices]);
            }
        }

        if ($request->has('remove_voice_record_ids') && is_string($request->input('remove_voice_record_ids'))) {
            $decodedVoiceIds = json_decode((string) $request->input('remove_voice_record_ids'), true);
            if (is_array($decodedVoiceIds)) {
                $request->merge(['remove_voice_record_ids' => $decodedVoiceIds]);
            }
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'time' => 'nullable|string|max:20',
            'reservation_number' => 'nullable|string|max:20',
            'slot' => 'nullable|string|max:20',
            'status' => 'nullable|in:waiting,entered,finished,cancelled',
            'acceptance' => 'required|in:pending,approved,not_approved',
            'payment' => 'required|in:paid,not_paid,unpaid',
            'month' => 'nullable|string|max:2',
            'first_diagnosis' => 'nullable|string|max:5000',
            'final_diagnosis' => 'nullable|string|max:5000',
            'services' => 'nullable|array',
            'services.*.service_fee_id' => 'required_with:services|exists:services,id',
            'services.*.fee' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:1000',
            'voice_records' => 'nullable|array',
            'voice_records.*' => 'file|mimes:mp3,wav,m4a,aac,ogg,webm|max:20480',
            'remove_voice_record_ids' => 'nullable|array',
            'remove_voice_record_ids.*' => 'integer',
        ]);

        $mode = $this->resolveReservationMode((int) $clinicId);
        $selectedValue = $mode === 'numbers'
            ? (string) ($validated['reservation_number'] ?? '')
            : (string) ($validated['slot'] ?? $validated['time'] ?? '');
        $this->ensureReservationValueAvailable(
            (int) $clinicId,
            (int) $validated['doctor_id'],
            (string) $validated['date'],
            $mode,
            $selectedValue,
            (int) $reservation->id,
        );

        $payment = in_array($validated['payment'], ['not_paid', 'unpaid'], true) ? 'not_paid' : 'paid';
        $reservation->update([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'payment' => $payment,
            'date' => $validated['date'],
            'status' => $validated['status'] ?? $reservation->status ?? 'waiting',
            'acceptance' => $validated['acceptance'],
            'month' => $validated['month'] ?? Carbon::parse($validated['date'])->format('m'),
            'reservation_number' => $mode === 'numbers' ? $selectedValue : null,
            'slot' => $mode === 'slots' ? $selectedValue : null,
            'first_diagnosis' => $validated['first_diagnosis'] ?? null,
            'final_diagnosis' => $validated['final_diagnosis'] ?? null,
        ]);

        ModuleService::where('module_id', $reservation->id)
            ->where('module_type', Reservation::class)
            ->delete();

        $sum = 0;
        foreach (($validated['services'] ?? []) as $row) {
            $fee = (float) ($row['fee'] ?? 0);
            ModuleService::create([
                'module_id' => $reservation->id,
                'module_type' => Reservation::class,
                'service_fee_id' => $row['service_fee_id'],
                'fee' => $fee,
                'notes' => $row['notes'] ?? null,
            ]);
            $sum += $fee;
        }
        $reservation->update(['cost' => $sum]);

        $removeIds = collect($validated['remove_voice_record_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->values();
        if ($removeIds->isNotEmpty()) {
            $reservation->media()
                ->where('collection_name', 'reservation_voice_records')
                ->whereIn('id', $removeIds->all())
                ->get()
                ->each(fn (Media $media) => $media->delete());
        }

        if ($request->hasFile('voice_records')) {
            foreach ($request->file('voice_records') as $voiceFile) {
                $reservation->addMedia($voiceFile)->toMediaCollection('reservation_voice_records');
            }
        }

        return $this->returnJSON(['id' => $reservation->id], 'Reservation updated', 'success');
    }

    /**
     * Get prescription + drugs for a reservation.
     */
    public function reservationPrescription($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $prescription = Prescription::query()
            ->where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        $drugs = Drug::query()
            ->where('reservation_id', $reservation->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'type' => $d->type,
                'dose' => $d->dose,
                'frequency' => $d->frequency,
                'period' => $d->period,
                'notes' => $d->notes,
            ])
            ->values();

        return $this->returnJSON([
            'reservation_id' => $reservation->id,
            'patient_id' => $reservation->patient_id,
            'doctor_id' => $reservation->doctor_id,
            'title' => $prescription?->title,
            'notes' => $prescription?->notes,
            'images' => $prescription?->images ?? [],
            'drugs' => $drugs,
        ], 'Reservation prescription', 'success');
    }

    /**
     * Create/update reservation prescription with drugs and images.
     */
    public function saveReservationPrescription(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'drugs' => 'required',
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240',
        ]);

        $decodedDrugs = json_decode((string) $request->input('drugs'), true);
        if (!is_array($decodedDrugs)) {
            throw ValidationException::withMessages([
                'drugs' => ['Invalid drugs payload.'],
            ]);
        }

        foreach ($decodedDrugs as $index => $drug) {
            if (!is_array($drug)) {
                throw ValidationException::withMessages([
                    "drugs.$index" => ['Invalid drug row.'],
                ]);
            }

            foreach (['name', 'type', 'dose', 'frequency', 'period'] as $field) {
                $value = trim((string) ($drug[$field] ?? ''));
                if ($value === '') {
                    throw ValidationException::withMessages([
                        "drugs.$index.$field" => [ucfirst($field) . ' is required.'],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($request, $reservation, $decodedDrugs) {
            $prescription = Prescription::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'patient_id' => $reservation->patient_id,
                    'title' => $request->input('title'),
                    'notes' => $request->input('notes'),
                ],
            );

            if ($request->hasFile('images')) {
                $prescription->clearMediaCollection('prescription_images');
                foreach ($request->file('images') as $image) {
                    $prescription->addMedia($image)->toMediaCollection('prescription_images');
                }
            }

            Drug::query()->where('reservation_id', $reservation->id)->delete();
            foreach ($decodedDrugs as $drug) {
                Drug::create([
                    'name' => trim((string) $drug['name']),
                    'type' => trim((string) $drug['type']),
                    'dose' => trim((string) $drug['dose']),
                    'frequency' => trim((string) $drug['frequency']),
                    'period' => trim((string) $drug['period']),
                    'notes' => isset($drug['notes']) ? trim((string) $drug['notes']) : null,
                    'reservation_id' => $reservation->id,
                    'patient_id' => $reservation->patient_id,
                    'clinic_id' => $reservation->clinic_id,
                    'doctor_id' => $reservation->doctor_id,
                ]);
            }
        });

        return $this->returnJSON(['id' => (int) $id], 'Prescription saved', 'success');
    }

    /**
     * Get rays for a reservation.
     */
    public function reservationRays($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $rows = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)
            ->where('organization_type', Clinic::class)
            ->where('organization_id', $clinicId)
            ->where('reservation_id', $reservation->id)
            ->latest('id')
            ->get()
            ->map(function ($ray) {
                return [
                    'id' => $ray->id,
                    'reservation_id' => $ray->reservation_id,
                    'patient_id' => $ray->patient_id,
                    'date' => $ray->date,
                    'report' => $ray->report,
                    'payment' => $ray->payment,
                    'cost' => $ray->cost,
                    'images' => $ray->getMedia('ray_images')->map(fn ($m) => $m->getUrl())->values()->all(),
                    'created_at' => optional($ray->created_at)->format('Y-m-d H:i'),
                ];
            })
            ->values();

        return $this->returnJSON($rows, 'Reservation rays', 'success');
    }

    /**
     * Create a ray for a reservation with media.
     */
    public function createReservationRay(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'payment' => 'required|in:paid,not_paid',
            'report' => 'nullable|string|max:5000',
            'cost' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240',
        ]);

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)->create([
            'patient_id' => $reservation->patient_id,
            'reservation_id' => $reservation->id,
            'organization_id' => $clinicId,
            'organization_type' => Clinic::class,
            'date' => $validated['date'],
            'payment' => $validated['payment'],
            'report' => $validated['report'] ?? null,
            'cost' => isset($validated['cost']) ? (string) $validated['cost'] : null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $ray->addMedia($image)->toMediaCollection('ray_images');
            }
        }

        return $this->returnJSON(['id' => $ray->id], 'Reservation ray saved', 'success');
    }

    /**
     * Get glasses distance records for a reservation.
     */
    public function reservationGlassesDistances($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $rows = GlassesDistance::query()
            ->where('clinic_id', $clinicId)
            ->where('reservation_id', $reservation->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'reservation_id' => $g->reservation_id,
                'patient_id' => $g->patient_id,
                'SPH_R_D' => $g->SPH_R_D,
                'CYL_R_D' => $g->CYL_R_D,
                'AX_R_D' => $g->AX_R_D,
                'SPH_L_D' => $g->SPH_L_D,
                'CYL_L_D' => $g->CYL_L_D,
                'AX_L_D' => $g->AX_L_D,
                'SPH_R_N' => $g->SPH_R_N,
                'CYL_R_N' => $g->CYL_R_N,
                'AX_R_N' => $g->AX_R_N,
                'SPH_L_N' => $g->SPH_L_N,
                'CYL_L_N' => $g->CYL_L_N,
                'AX_L_N' => $g->AX_L_N,
                'created_at' => optional($g->created_at)->format('Y-m-d H:i'),
            ])
            ->values();

        return $this->returnJSON($rows, 'Reservation glasses distances', 'success');
    }

    /**
     * Add glasses distance record for a reservation.
     */
    public function createReservationGlassesDistance(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $validated = $request->validate([
            'SPH_R_D' => 'nullable|string|max:255',
            'CYL_R_D' => 'nullable|string|max:255',
            'AX_R_D' => 'nullable|string|max:255',
            'SPH_L_D' => 'nullable|string|max:255',
            'CYL_L_D' => 'nullable|string|max:255',
            'AX_L_D' => 'nullable|string|max:255',
            'SPH_R_N' => 'nullable|string|max:255',
            'CYL_R_N' => 'nullable|string|max:255',
            'AX_R_N' => 'nullable|string|max:255',
            'SPH_L_N' => 'nullable|string|max:255',
            'CYL_L_N' => 'nullable|string|max:255',
            'AX_L_N' => 'nullable|string|max:255',
        ]);

        $row = GlassesDistance::create([
            ...$validated,
            'patient_id' => $reservation->patient_id,
            'reservation_id' => $reservation->id,
            'clinic_id' => $reservation->clinic_id,
        ]);

        return $this->returnJSON(['id' => $row->id], 'Glasses distance saved', 'success');
    }

    /**
     * Get reservation teeth plan and notes.
     */
    public function reservationTeeth($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $rows = ReservationTooth::query()
            ->where('clinic_id', $clinicId)
            ->where('reservation_id', $reservation->id)
            ->orderBy('tooth_number')
            ->get();

        $first = $rows->first();
        return $this->returnJSON([
            'reservation_id' => $reservation->id,
            'patient_id' => $reservation->patient_id,
            'general_note' => $first?->general_note,
            'next_session_plan' => $first?->next_session_plan,
            'teeth' => $rows->map(fn ($row) => [
                'id' => $row->id,
                'tooth_number' => $row->tooth_number,
                'tooth_note' => $row->tooth_note,
            ])->values(),
        ], 'Reservation teeth data', 'success');
    }

    /**
     * Save reservation teeth selections and notes.
     */
    public function saveReservationTeeth(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $reservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $validated = $request->validate([
            'general_note' => 'nullable|string|max:5000',
            'next_session_plan' => 'nullable|string|max:5000',
            'teeth' => 'required|array|min:1',
            'teeth.*.tooth_number' => 'required|integer|between:1,32',
            'teeth.*.tooth_note' => 'nullable|string|max:2000',
        ]);

        $teeth = collect($validated['teeth'])
            ->map(fn ($row) => [
                'tooth_number' => (int) ($row['tooth_number'] ?? 0),
                'tooth_note' => isset($row['tooth_note']) ? trim((string) $row['tooth_note']) : null,
            ])
            ->filter(fn ($row) => $row['tooth_number'] >= 1 && $row['tooth_number'] <= 32)
            ->unique('tooth_number')
            ->values();

        if ($teeth->isEmpty()) {
            throw ValidationException::withMessages([
                'teeth' => ['Please select at least one tooth.'],
            ]);
        }

        DB::transaction(function () use ($reservation, $validated, $teeth) {
            ReservationTooth::query()->where('reservation_id', $reservation->id)->delete();

            foreach ($teeth as $row) {
                ReservationTooth::query()->create([
                    'reservation_id' => $reservation->id,
                    'patient_id' => $reservation->patient_id,
                    'clinic_id' => $reservation->clinic_id,
                    'tooth_number' => $row['tooth_number'],
                    'tooth_note' => $row['tooth_note'],
                    'general_note' => $validated['general_note'] ?? null,
                    'next_session_plan' => $validated['next_session_plan'] ?? null,
                ]);
            }
        });

        return $this->returnJSON(['reservation_id' => $reservation->id], 'Reservation teeth saved', 'success');
    }

    /**
     * Doctor service fees for reservation modal
     */
    public function doctorServices($doctorId)
    {
        $this->ensureClinicAuth();
        $fees = Service::query()
            ->where('doctor_id', $doctorId)
            ->orderBy('id', 'desc')
            ->get(['id', 'service_name', 'price', 'notes'])
            ->map(fn ($f) => [
                'id' => $f->id,
                'service_name' => $f->service_name,
                'fee' => (float) ($f->price ?? 0),
                'price' => (float) ($f->price ?? 0),
                'notes' => $f->notes,
            ]);

        return $this->returnJSON($fees, 'Doctor service fees', 'success');
    }

    /**
     * Patients list for clinic
     */
    public function patients(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $query = Patient::query()
            ->clinic()
            ->with([
                'clinicDoctors' => fn ($q) => $q
                    ->wherePivot('organization_id', $clinicId)
                    ->with('user:id,name'),
            ])
            ->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(function ($p) {
            $assignedDoctors = $p->clinicDoctors
                ->map(fn ($doctor) => [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name ?? null,
                ])
                ->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'address' => $p->address ?? null,
                'email' => $p->email ?? null,
                'phone' => $p->phone ?? null,
                'whatsapp_number' => $p->whatsapp_number ?? null,
                'age' => $p->age ?? null,
                'gender' => $p->gender ?? null,
                'blood_group' => $p->blood_group ?? null,
                'height' => $p->height ?? null,
                'weight' => $p->weight ?? null,
                'doctor_id' => $assignedDoctors->first()['id'] ?? null,
                'doctor_name' => $assignedDoctors->first()['name'] ?? null,
                'assigned_doctors' => $assignedDoctors,
                'assigned_doctor_names' => $assignedDoctors
                    ->pluck('name')
                    ->filter()
                    ->values()
                    ->all(),
            ];
        });
        return $this->returnJSON([
            'data' => $data,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Patients', 'success');
    }

    /**
     * Single patient details for edit page.
     */
    public function patientDetails($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $patient = Patient::query()
            ->clinic()
            ->where('id', $id)
            ->firstOrFail();

        $doctors = $patient->clinicDoctors()
            ->wherePivot('organization_id', $clinicId)
            ->with('user:id,name')
            ->get();
        $assignedDoctors = $doctors
            ->map(fn ($doctor) => [
                'id' => $doctor->id,
                'name' => $doctor->user?->name ?? null,
            ])
            ->values();

        return $this->returnJSON([
            'id' => $patient->id,
            'name' => $patient->name,
            'address' => $patient->address ?? null,
            'email' => $patient->email ?? null,
            'phone' => $patient->phone ?? null,
            'whatsapp_number' => $patient->whatsapp_number ?? null,
            'age' => $patient->age ?? null,
            'gender' => $patient->gender ?? null,
            'blood_group' => $patient->blood_group ?? null,
            'height' => $patient->height ?? null,
            'weight' => $patient->weight ?? null,
            'doctor_id' => $assignedDoctors->first()['id'] ?? null,
            'doctor_name' => $assignedDoctors->first()['name'] ?? null,
            'doctor_ids' => $assignedDoctors->pluck('id')->values(),
            'assigned_doctors' => $assignedDoctors,
            'assigned_doctor_names' => $assignedDoctors->pluck('name')->filter()->values(),
        ], 'Patient details', 'success');
    }

    /**
     * Patient profile/history with clinic-related modules.
     */
    public function patientHistory($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $patient = Patient::query()->clinic()->where('id', $id)->firstOrFail();

        $reservations = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->with(['doctor:id,user_id', 'doctor.user:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $reservationIds = $reservations->pluck('id')->values();

        $prescriptions = Prescription::query()
            ->whereIn('reservation_id', $reservationIds)
            ->get()
            ->keyBy('reservation_id');

        $drugsByReservation = Drug::query()
            ->whereIn('reservation_id', $reservationIds)
            ->orderBy('id')
            ->get()
            ->groupBy('reservation_id');

        $glassesByReservation = GlassesDistance::query()
            ->where('clinic_id', $clinicId)
            ->whereIn('reservation_id', $reservationIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('reservation_id');

        $teethByReservation = ReservationTooth::query()
            ->where('clinic_id', $clinicId)
            ->whereIn('reservation_id', $reservationIds)
            ->orderBy('tooth_number')
            ->get()
            ->groupBy('reservation_id');

        $patientLevelGlasses = GlassesDistance::query()
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->whereNull('reservation_id')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'SPH_R_D' => $g->SPH_R_D,
                'CYL_R_D' => $g->CYL_R_D,
                'AX_R_D' => $g->AX_R_D,
                'SPH_L_D' => $g->SPH_L_D,
                'CYL_L_D' => $g->CYL_L_D,
                'AX_L_D' => $g->AX_L_D,
                'SPH_R_N' => $g->SPH_R_N,
                'CYL_R_N' => $g->CYL_R_N,
                'AX_R_N' => $g->AX_R_N,
                'SPH_L_N' => $g->SPH_L_N,
                'CYL_L_N' => $g->CYL_L_N,
                'AX_L_N' => $g->AX_L_N,
                'created_at' => optional($g->created_at)->format('Y-m-d H:i'),
            ])
            ->values();

        $legacyToothRecords = ToothRecord::query()
            ->where('patient_id', $patient->id)
            ->where('organization_type', Clinic::class)
            ->where('organization_id', $clinicId)
            ->orderBy('tooth_number')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'tooth_number' => $row->tooth_number,
                'status' => $row->status,
                'notes' => $row->notes,
            ])
            ->values();

        $reservationHistory = $reservations->map(function ($reservation) use ($prescriptions, $drugsByReservation, $glassesByReservation, $teethByReservation) {
            $prescription = $prescriptions->get($reservation->id);
            $glasses = $glassesByReservation->get($reservation->id, collect());
            $teethRows = $teethByReservation->get($reservation->id, collect());

            return [
                'id' => $reservation->id,
                'date' => $reservation->date,
                'time' => $reservation->time,
                'slot' => $reservation->slot,
                'reservation_number' => $reservation->reservation_number,
                'status' => $reservation->status,
                'acceptance' => $reservation->acceptance,
                'payment' => $reservation->payment,
                'doctor_name' => $reservation->doctor?->user?->name ?? null,
                'prescription' => $prescription ? [
                    'id' => $prescription->id,
                    'title' => $prescription->title,
                    'notes' => $prescription->notes,
                    'images' => $prescription->images ?? [],
                    'drugs' => ($drugsByReservation->get($reservation->id, collect()))
                        ->map(fn ($drug) => [
                            'id' => $drug->id,
                            'name' => $drug->name,
                            'type' => $drug->type,
                            'dose' => $drug->dose,
                            'frequency' => $drug->frequency,
                            'period' => $drug->period,
                            'notes' => $drug->notes,
                        ])
                        ->values(),
                ] : null,
                'glasses_distances' => $glasses
                    ->map(fn ($g) => [
                        'id' => $g->id,
                        'SPH_R_D' => $g->SPH_R_D,
                        'CYL_R_D' => $g->CYL_R_D,
                        'AX_R_D' => $g->AX_R_D,
                        'SPH_L_D' => $g->SPH_L_D,
                        'CYL_L_D' => $g->CYL_L_D,
                        'AX_L_D' => $g->AX_L_D,
                        'SPH_R_N' => $g->SPH_R_N,
                        'CYL_R_N' => $g->CYL_R_N,
                        'AX_R_N' => $g->AX_R_N,
                        'SPH_L_N' => $g->SPH_L_N,
                        'CYL_L_N' => $g->CYL_L_N,
                        'AX_L_N' => $g->AX_L_N,
                        'created_at' => optional($g->created_at)->format('Y-m-d H:i'),
                    ])
                    ->values(),
                'teeth' => [
                    'general_note' => $teethRows->first()?->general_note,
                    'next_session_plan' => $teethRows->first()?->next_session_plan,
                    'items' => $teethRows
                        ->map(fn ($row) => [
                            'id' => $row->id,
                            'tooth_number' => $row->tooth_number,
                            'tooth_note' => $row->tooth_note,
                        ])
                        ->values(),
                ],
            ];
        })->values();

        return $this->returnJSON([
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'address' => $patient->address,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
            ],
            'reservations' => $reservationHistory,
            'patient_level_glasses_distances' => $patientLevelGlasses,
            'patient_level_tooth_records' => $legacyToothRecords,
        ], 'Patient history profile', 'success');
    }

    /**
     * Create patient and assign related doctor in pivot.
     */
    public function createPatient(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $validated = $request->validate([
            'doctor_ids' => 'nullable|array|min:1|required_without:doctor_id',
            'doctor_ids.*' => 'required|integer|exists:doctors,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id|required_without:doctor_ids',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'email' => 'nullable|email|max:255|unique:patients,email',
            'password' => 'nullable|string|min:6|max:255',
            'phone' => 'required|string|max:20|unique:patients,phone',
            'whatsapp_number' => 'nullable|string|max:20',
            'age' => 'nullable|string|max:10',
            'gender' => 'required|in:male,female',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'height' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
        ]);
        $doctorIds = collect($validated['doctor_ids'] ?? [($validated['doctor_id'] ?? null)])
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($doctorIds->isEmpty()) {
            throw ValidationException::withMessages([
                'doctor_ids' => ['At least one doctor is required.'],
            ]);
        }

        $patient = DB::transaction(function () use ($validated, $clinicId, $doctorIds) {
            $patient = Patient::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'email' => $validated['email'] ?? null,
                'password' => !empty($validated['password']) ? Hash::make($validated['password']) : null,
                'phone' => $validated['phone'],
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'age' => $validated['age'] ?? null,
                'gender' => $validated['gender'],
                'blood_group' => $validated['blood_group'] ?? null,
                'height' => $validated['height'] ?? null,
                'weight' => $validated['weight'] ?? null,
            ]);

            foreach ($doctorIds as $doctorId) {
                DB::table('patient_organization')->insert([
                    'patient_id' => $patient->id,
                    'organization_id' => $clinicId,
                    'organization_type' => Clinic::class,
                    'doctor_id' => $doctorId,
                    'assigned' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $patient;
        });

        return $this->returnJSON(['id' => $patient->id], 'Patient created', 'success');
    }

    /**
     * Update patient and related doctor assignment.
     */
    public function updatePatient(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $patient = Patient::query()->clinic()->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'doctor_ids' => 'nullable|array|min:1|required_without:doctor_id',
            'doctor_ids.*' => 'required|integer|exists:doctors,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id|required_without:doctor_ids',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'email' => 'nullable|email|max:255|unique:patients,email,' . $patient->id,
            'password' => 'nullable|string|min:6|max:255',
            'phone' => 'required|string|max:20|unique:patients,phone,' . $patient->id,
            'whatsapp_number' => 'nullable|string|max:20',
            'age' => 'nullable|string|max:10',
            'gender' => 'required|in:male,female',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'height' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
        ]);
        $doctorIds = collect($validated['doctor_ids'] ?? [($validated['doctor_id'] ?? null)])
            ->filter(fn ($doctorId) => !is_null($doctorId) && $doctorId !== '')
            ->map(fn ($doctorId) => (int) $doctorId)
            ->unique()
            ->values();
        if ($doctorIds->isEmpty()) {
            throw ValidationException::withMessages([
                'doctor_ids' => ['At least one doctor is required.'],
            ]);
        }

        DB::transaction(function () use ($patient, $validated, $clinicId, $doctorIds) {
            $patientData = [
                'name' => $validated['name'],
                'address' => $validated['address'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'],
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'age' => $validated['age'] ?? null,
                'gender' => $validated['gender'],
                'blood_group' => $validated['blood_group'] ?? null,
                'height' => $validated['height'] ?? null,
                'weight' => $validated['weight'] ?? null,
            ];
            if (!empty($validated['password'])) {
                $patientData['password'] = Hash::make($validated['password']);
            }
            $patient->update($patientData);

            foreach ($doctorIds as $doctorId) {
                DB::table('patient_organization')->updateOrInsert(
                    [
                        'patient_id' => $patient->id,
                        'organization_id' => $clinicId,
                        'organization_type' => Clinic::class,
                        'doctor_id' => $doctorId,
                    ],
                    [
                        'assigned' => true,
                        'deleted_at' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            DB::table('patient_organization')
                ->where('patient_id', $patient->id)
                ->where('organization_id', $clinicId)
                ->where('organization_type', Clinic::class)
                ->whereNotIn('doctor_id', $doctorIds->all())
                ->update([
                    'assigned' => false,
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return $this->returnJSON(['id' => $patient->id], 'Patient updated', 'success');
    }

    /**
     * Assign existing patient to current clinic by patient code or QR value.
     */
    public function assignPatientByCode(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $validated = $request->validate([
            'patient_code' => 'nullable|string|max:255|required_without:qr_value',
            'qr_value' => 'nullable|string|max:2000|required_without:patient_code',
            'doctor_ids' => 'nullable|array|min:1|required_without:doctor_id',
            'doctor_ids.*' => 'required|integer|exists:doctors,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id|required_without:doctor_ids',
        ]);

        $doctorIds = collect($validated['doctor_ids'] ?? [($validated['doctor_id'] ?? null)])
            ->filter(fn ($doctorId) => !is_null($doctorId) && $doctorId !== '')
            ->map(fn ($doctorId) => (int) $doctorId)
            ->unique()
            ->values();
        if ($doctorIds->isEmpty()) {
            throw ValidationException::withMessages([
                'doctor_ids' => ['At least one doctor is required.'],
            ]);
        }

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

        DB::transaction(function () use ($clinicId, $patient, $doctorIds) {
            foreach ($doctorIds as $doctorId) {
                DB::table('patient_organization')->updateOrInsert(
                    [
                        'patient_id' => $patient->id,
                        'organization_id' => $clinicId,
                        'organization_type' => Clinic::class,
                        'doctor_id' => $doctorId,
                    ],
                    [
                        'assigned' => true,
                        'deleted_at' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        });

        return $this->returnJSON([
            'id' => $patient->id,
            'patient_code' => $patient->patient_code,
            'assigned_doctor_ids' => $doctorIds->all(),
        ], 'Patient assigned to clinic successfully', 'success');
    }

    /**
     * Get glasses distance records for a patient in current clinic.
     */
    public function patientGlassesDistances($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $patient = Patient::query()->clinic()->where('id', $id)->firstOrFail();

        $rows = GlassesDistance::query()
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'reservation_id' => $g->reservation_id,
                'patient_id' => $g->patient_id,
                'SPH_R_D' => $g->SPH_R_D,
                'CYL_R_D' => $g->CYL_R_D,
                'AX_R_D' => $g->AX_R_D,
                'SPH_L_D' => $g->SPH_L_D,
                'CYL_L_D' => $g->CYL_L_D,
                'AX_L_D' => $g->AX_L_D,
                'SPH_R_N' => $g->SPH_R_N,
                'CYL_R_N' => $g->CYL_R_N,
                'AX_R_N' => $g->AX_R_N,
                'SPH_L_N' => $g->SPH_L_N,
                'CYL_L_N' => $g->CYL_L_N,
                'AX_L_N' => $g->AX_L_N,
                'created_at' => optional($g->created_at)->format('Y-m-d H:i'),
            ])
            ->values();

        return $this->returnJSON($rows, 'Patient glasses distances', 'success');
    }

    /**
     * Add glasses distance record for a patient in current clinic.
     */
    public function createPatientGlassesDistance(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $patient = Patient::query()->clinic()->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'SPH_R_D' => 'nullable|string|max:255',
            'CYL_R_D' => 'nullable|string|max:255',
            'AX_R_D' => 'nullable|string|max:255',
            'SPH_L_D' => 'nullable|string|max:255',
            'CYL_L_D' => 'nullable|string|max:255',
            'AX_L_D' => 'nullable|string|max:255',
            'SPH_R_N' => 'nullable|string|max:255',
            'CYL_R_N' => 'nullable|string|max:255',
            'AX_R_N' => 'nullable|string|max:255',
            'SPH_L_N' => 'nullable|string|max:255',
            'CYL_L_N' => 'nullable|string|max:255',
            'AX_L_N' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['reservation_id'])) {
            Reservation::query()
                ->where('id', (int) $validated['reservation_id'])
                ->where('clinic_id', $clinicId)
                ->where('patient_id', $patient->id)
                ->firstOrFail();
        }

        $row = GlassesDistance::create([
            ...collect($validated)->except('reservation_id')->toArray(),
            'patient_id' => $patient->id,
            'reservation_id' => $validated['reservation_id'] ?? null,
            'clinic_id' => $clinicId,
        ]);

        return $this->returnJSON(['id' => $row->id], 'Glasses distance saved', 'success');
    }

    /**
     * Roles list for clinic (guard web)
     */
    public function roles()
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $roles = Role::where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
            ->withCount('permissions')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'permissions_count' => $r->permissions_count]);
        return $this->returnJSON($roles, 'Roles', 'success');
    }

    /**
     * Permissions list for clinic role forms.
     */
    public function permissions()
    {
        $this->ensureClinicAuth();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
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
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
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
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
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
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);
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
     * Services page data
     */
    public function services(Request $request)
    {
        $this->ensureClinicAuth();
        $query = Service::query()->with('doctor.user')->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->service_name,
            'category' => $s->type ?? 'General',
            'price' => (string) ($s->price ?? 0),
            'duration' => null,
            'status' => 'active',
            'doctor_id' => $s->doctor_id,
            'doctor_name' => $s->doctor?->user?->name ?? null,
            'notes' => $s->notes,
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Services', 'success');
    }

    /**
     * Single service details
     */
    public function serviceDetails($id)
    {
        $this->ensureClinicAuth();

        $service = Service::query()
            ->with('doctor.user:id,name')
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $service->id,
            'service_name' => $service->service_name,
            'doctor_id' => $service->doctor_id,
            'doctor_name' => $service->doctor?->user?->name,
            'price' => (float) ($service->price ?? 0),
            'type' => $service->type,
            'notes' => $service->notes,
        ], 'Service details', 'success');
    }

    /**
     * Create clinic service
     */
    public function createService(Request $request)
    {
        $this->ensureClinicAuth();

        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'doctor_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:main,sub',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['doctor_id'])) {
            $doctorExists = Doctor::query()->where('id', $validated['doctor_id'])->exists();
            if (!$doctorExists) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['Selected doctor is invalid for this clinic.'],
                ]);
            }
        }

        $service = Service::create([
            'service_name' => $validated['service_name'],
            'doctor_id' => $validated['doctor_id'] ?? null,
            'price' => $validated['price'],
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
            'organization_id' => request()->user()->organization_id,
            'organization_type' => request()->user()->organization_type,
        ]);

        return $this->returnJSON(['id' => $service->id], 'Service created', 'success');
    }

    /**
     * Update clinic service
     */
    public function updateService(Request $request, $id)
    {
        $this->ensureClinicAuth();

        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'doctor_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:main,sub',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['doctor_id'])) {
            $doctorExists = Doctor::query()->where('id', $validated['doctor_id'])->exists();
            if (!$doctorExists) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['Selected doctor is invalid for this clinic.'],
                ]);
            }
        }

        $service = Service::query()->findOrFail($id);
        $service->update([
            'service_name' => $validated['service_name'],
            'doctor_id' => $validated['doctor_id'] ?? null,
            'price' => $validated['price'],
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->returnJSON(['id' => $service->id], 'Service updated', 'success');
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

    private function ensureClinicAuth(): void
    {
        if (!request()->user()) {
            abort(401, 'Unauthenticated');
        }
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

    private function resolveReservationMode(int $clinicId): string
    {
        $setting = Settings::query()
            ->where('organization_id', $clinicId)
            ->where('organization_type', Clinic::class)
            ->where('type', 'clinic_reservations_settings')
            ->where('key', 'reservation_settings')
            ->value('value');

        return in_array($setting, ['number', 'numbers'], true) ? 'numbers' : 'slots';
    }

    private function ensureReservationValueAvailable(
        int $clinicId,
        int $doctorId,
        string $date,
        string $mode,
        string $value,
        ?int $excludeReservationId = null,
    ): void {
        if ($value === '') {
            throw ValidationException::withMessages([
                $mode === 'numbers' ? 'reservation_number' : 'slot' => [
                    $mode === 'numbers' ? 'Reservation number is required.' : 'Reservation slot is required.',
                ],
            ]);
        }

        $query = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->where('date', $date)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId));

        $exists = $mode === 'numbers'
            ? (clone $query)->where('reservation_number', $value)->exists()
            : (clone $query)->where('slot', $value)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                $mode === 'numbers' ? 'reservation_number' : 'slot' => [
                    $mode === 'numbers'
                        ? 'This reservation number is already taken for the selected doctor and date.'
                        : 'This slot is already taken for the selected doctor and date.',
                ],
            ]);
        }
    }

    private function generateSlots(string $startTime, string $endTime, int $durationMinutes): array
    {
        if ($startTime === '' || $endTime === '' || $durationMinutes <= 0) {
            return [];
        }

        $start = strtotime($startTime);
        $end = strtotime($endTime);
        if ($start === false || $end === false || $start >= $end) {
            return [];
        }

        $slots = [];
        while ($start + ($durationMinutes * 60) <= $end) {
            $slots[] = date('H:i', $start);
            $start += $durationMinutes * 60;
        }

        return $slots;
    }
}
