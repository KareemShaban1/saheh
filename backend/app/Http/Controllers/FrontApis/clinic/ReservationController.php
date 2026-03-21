<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\Prescription\Models\Prescription;
use Modules\Clinic\Prescription\Models\Drug;
use App\Models\Shared\Patient;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Shared\Specialty;
use Modules\Clinic\User\Models\User;
use App\Http\Controllers\BaseFrontApiController;
use App\Models\Service;
use App\Models\ModuleService;
use App\Models\Settings;
use App\Models\PrescriptionDrug;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use App\Models\ToothRecord;
use App\Models\ReservationTooth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Notifications\MakeAppointmentNotification;


class ReservationController extends BaseFrontApiController
{
    //
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

        $query = Reservation::with(['patient:id,name,phone', 'doctor:id,user_id', 'doctor.user:id,name', 'payments'])
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
            $paymentSummary = $this->paymentSummaryFromCollection($r->payments ?? collect());
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
                'parent_id' => $r->parent_id,
                'type' => $r->type ?? 'reservation',
                'status' => $r->status ?? $r->acceptance ?? $r->res_status ?? 'pending',
                'acceptance' => $r->acceptance ?? 'pending',
                'payment' => $r->payment ?? $paymentSummary['status'],
                'remaining' => $paymentSummary['remaining'],
                'paid_amount' => $paymentSummary['paid_amount'],
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
            'payments',
        ])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $paymentSummary = $this->paymentSummaryFromCollection($reservation->payments ?? collect());

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
            'parent_id' => $reservation->parent_id,
            'type' => $reservation->type ?? 'reservation',
            'reservation_mode' => $reservation->reservation_number ? 'numbers' : 'slots',
            'status' => $reservation->status ?? 'waiting',
            'acceptance' => $reservation->acceptance ?? 'pending',
            'payment' => $reservation->payment ?? $paymentSummary['status'],
            'remaining' => $paymentSummary['remaining'],
            'paid_amount' => $paymentSummary['paid_amount'],
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
            'payment_history' => $this->formatPayments($reservation->payments ?? collect(), Reservation::class, (int) $reservation->id),
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
        if ($request->has('payments') && is_string($request->input('payments'))) {
            $decodedPayments = json_decode((string) $request->input('payments'), true);
            if (is_array($decodedPayments)) {
                $request->merge(['payments' => $decodedPayments]);
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
            'payment' => 'nullable|in:paid,not_paid,partially_paid',
            'month' => 'nullable|string|max:2',
            'first_diagnosis' => 'nullable|string|max:5000',
            'final_diagnosis' => 'nullable|string|max:5000',
            'services' => 'nullable|array',
            'services.*.service_fee_id' => 'required_with:services|exists:services,id',
            'services.*.fee' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:1000',
            'payments' => 'nullable|array',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.remaining' => 'required_with:payments|numeric|min:0',
            'payments.*.payment_way' => 'nullable|string|max:100',
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

        $reservation = Reservation::create([
            'patient_id' => $validated['patient_id'],
            'clinic_id' => $clinicId,
            'doctor_id' => $validated['doctor_id'],
            'reservation_number' => $mode === 'numbers' ? $selectedValue : null,
            'date' => $validated['date'],
            'status' => $validated['status'] ?? 'waiting',
            'acceptance' => $validated['acceptance'],
            'payment' => $validated['payment'] ?? $this->paymentStatusFromRows($validated['payments'] ?? []),
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
        $reservation->update(['cost' => (string) $sum]);
        $this->syncPayments($reservation, $validated['payments'] ?? []);

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
        if ($request->has('payments') && is_string($request->input('payments'))) {
            $decodedPayments = json_decode((string) $request->input('payments'), true);
            if (is_array($decodedPayments)) {
                $request->merge(['payments' => $decodedPayments]);
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
            'payment' => 'nullable|in:paid,not_paid,partially_paid',
            'month' => 'nullable|string|max:2',
            'first_diagnosis' => 'nullable|string|max:5000',
            'final_diagnosis' => 'nullable|string|max:5000',
            'services' => 'nullable|array',
            'services.*.service_fee_id' => 'required_with:services|exists:services,id',
            'services.*.fee' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:1000',
            'payments' => 'nullable|array',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.remaining' => 'required_with:payments|numeric|min:0',
            'payments.*.payment_way' => 'nullable|string|max:100',
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

        $reservation->update([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'date' => $validated['date'],
            'status' => $validated['status'] ?? $reservation->status ?? 'waiting',
            'acceptance' => $validated['acceptance'],
            'payment' => $validated['payment'] ?? $this->paymentStatusFromRows($validated['payments'] ?? []),
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
        $reservation->update(['cost' => (string) $sum]);
        $this->syncPayments($reservation, $validated['payments'] ?? []);

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

        $drugs = collect();
        if ($prescription) {
            $drugs = PrescriptionDrug::query()
                ->with('drug')
                ->where('prescription_id', $prescription->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->drug->name ?? '',
                    'type' => $d->type ?? '',
                    'dose' => $d->dose ?? '',
                    'frequency' => $d->frequency ?? '',
                    'period' => $d->period ?? '',
                    'notes' => $d->notes,
                    'drug_id' => $d->drug_id,
                ])
                ->values();
        }

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

            if (isset($drug['selected_drug_id']) && $drug['selected_drug_id'] !== '' && !is_numeric($drug['selected_drug_id'])) {
                throw ValidationException::withMessages([
                    "drugs.$index.selected_drug_id" => ['Selected drug id must be numeric.'],
                ]);
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

            PrescriptionDrug::query()->where('prescription_id', $prescription->id)->delete();

            foreach ($decodedDrugs as $index => $drug) {
                $selectedDrugId = isset($drug['selected_drug_id']) && $drug['selected_drug_id'] !== ''
                    ? (int) $drug['selected_drug_id']
                    : null;

                $drugModel = null;
                if ($selectedDrugId) {
                    $drugModel = Drug::query()
                        ->where('clinic_id', $reservation->clinic_id)
                        ->find($selectedDrugId);
                }

                if (!$drugModel) {
                    if ($selectedDrugId) {
                        throw ValidationException::withMessages([
                            "drugs.$index.selected_drug_id" => ['Selected drug not found in this clinic.'],
                        ]);
                    }

                    $drugModel = Drug::query()->create([
                        'name' => trim((string) $drug['name']),
                        'type' => trim((string) $drug['type']),
                        'dose' => trim((string) $drug['dose']),
                        'frequency' => trim((string) $drug['frequency']),
                        'period' => trim((string) $drug['period']),
                        'notes' => isset($drug['notes']) ? trim((string) $drug['notes']) : null,
                        'clinic_id' => $reservation->clinic_id,
                        'doctor_id' => $reservation->doctor_id,
                    ]);
                }

                PrescriptionDrug::query()->create([
                    'prescription_id' => $prescription->id,
                    'drug_id' => $drugModel->id,
                    'dose' => trim((string) $drug['dose']),
                    'type' => trim((string) $drug['type']),
                    'frequency' => trim((string) $drug['frequency']),
                    'period' => trim((string) $drug['period']),
                    'notes' => isset($drug['notes']) ? trim((string) $drug['notes']) : null,
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
                $paymentSummary = $this->paymentSummaryFromCollection($ray->payments ?? collect());
                return [
                    'id' => $ray->id,
                    'reservation_id' => $ray->reservation_id,
                    'patient_id' => $ray->patient_id,
                    'date' => $ray->date,
                    'report' => $ray->report,
                    'payment' => $paymentSummary['status'],
                    'remaining' => $paymentSummary['remaining'],
                    'paid_amount' => $paymentSummary['paid_amount'],
                    'payment_history' => $this->formatPayments($ray->payments ?? collect(), \App\Models\Ray::class, (int) $ray->id),
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

        if ($request->has('payments') && is_string($request->input('payments'))) {
            $decodedPayments = json_decode((string) $request->input('payments'), true);
            if (is_array($decodedPayments)) {
                $request->merge(['payments' => $decodedPayments]);
            }
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'report' => 'nullable|string|max:5000',
            'payments' => 'nullable|array',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.remaining' => 'required_with:payments|numeric|min:0',
            'payments.*.payment_way' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240',
        ]);

        $ray = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class)->create([
            'patient_id' => $reservation->patient_id,
            'reservation_id' => $reservation->id,
            'organization_id' => $clinicId,
            'organization_type' => Clinic::class,
            'date' => $validated['date'],
            'report' => $validated['report'] ?? null,
        ]);

        $this->syncPayments($ray, $validated['payments'] ?? []);

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

    public function createSession(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $selectedReservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);
        $parentReservation = $selectedReservation->parent_id
            ? Reservation::query()->where('clinic_id', $clinicId)->findOrFail($selectedReservation->parent_id)
            : $selectedReservation;

        if ($request->has('payments') && is_string($request->input('payments'))) {
            $decodedPayments = json_decode((string) $request->input('payments'), true);
            if (is_array($decodedPayments)) {
                $request->merge(['payments' => $decodedPayments]);
            }
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reservation_number' => 'nullable|string|max:20',
            'slot' => 'nullable|string|max:20',
            'status' => 'nullable|in:waiting,entered,finished,cancelled',
            'acceptance' => 'nullable|in:pending,approved,not_approved',
            'payment' => 'nullable|in:paid,not_paid,partially_paid',
            'month' => 'nullable|string|max:2',
            'first_diagnosis' => 'nullable|string|max:5000',
            'final_diagnosis' => 'nullable|string|max:5000',
            'payments' => 'nullable|array',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.remaining' => 'required_with:payments|numeric|min:0',
            'payments.*.payment_way' => 'nullable|string|max:100',
        ]);

        $mode = $this->resolveReservationMode((int) $clinicId);
        $selectedValue = $mode === 'numbers'
            ? (string) ($validated['reservation_number'] ?? '')
            : (string) ($validated['slot'] ?? '');

        $this->ensureReservationValueAvailable(
            (int) $clinicId,
            (int) $parentReservation->doctor_id,
            (string) $validated['date'],
            $mode,
            $selectedValue,
        );

        $session = Reservation::create([
            'patient_id' => $parentReservation->patient_id,
            'clinic_id' => $parentReservation->clinic_id,
            'doctor_id' => $parentReservation->doctor_id,
            'parent_id' => $parentReservation->id,
            'type' => 'session',
            'cost' => $parentReservation->cost,
            'reservation_number' => $mode === 'numbers' ? $selectedValue : null,
            'slot' => $mode === 'slots' ? $selectedValue : null,
            'date' => $validated['date'],
            'status' => $validated['status'] ?? 'waiting',
            'acceptance' => $validated['acceptance'] ?? 'approved',
            'payment' => $validated['payment'] ?? $this->paymentStatusFromRows($validated['payments'] ?? []),
            'month' => $validated['month'] ?? Carbon::parse($validated['date'])->format('m'),
            'first_diagnosis' => $validated['first_diagnosis'] ?? null,
            'final_diagnosis' => $validated['final_diagnosis'] ?? null,
        ]);

        $this->syncPayments($session, $validated['payments'] ?? []);

        return $this->returnJSON(['id' => $session->id], 'Session created', 'success');
    }

    public function sessionContext($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;
        $selectedReservation = Reservation::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);
        $parentId = $selectedReservation->parent_id ?: $selectedReservation->id;

        $parentReservation = Reservation::query()
            ->with([
                'patient:id,name',
                'doctor:id,user_id',
                'doctor.user:id,name',
                'services',
                'payments',
                'sessions.payments',
            ])
            ->where('clinic_id', $clinicId)
            ->findOrFail($parentId);

        $totalCost = (float) ($parentReservation->cost ?? 0);
        if ($totalCost <= 0) {
            $totalCost = (float) $parentReservation->services->sum(fn ($service) => (float) ($service->fee ?? 0));
        }

        $orderedSessions = $parentReservation->sessions
            ->sortBy(fn ($session) => sprintf('%s|%010d', (string) ($session->date ?? ''), (int) ($session->id ?? 0)))
            ->values();

        $chainPayments = collect($parentReservation->payments);
        foreach ($orderedSessions as $session) {
            $chainPayments = $chainPayments->concat($session->payments ?? collect());
        }

        // New session starts from the latest recorded remaining in the chain
        // (latest payment among parent reservation and its previous sessions).
        $summary = $this->paymentSummaryFromCollection($chainPayments);
        if ($chainPayments->isEmpty()) {
            $summary = $this->reservationFinancialSummary($parentReservation, $totalCost);
        }

        $paymentHistory = $chainPayments
            ->sortByDesc(fn ($payment) => sprintf('%s|%010d', (string) ($payment->payment_date ?? ''), (int) ($payment->id ?? 0)))
            ->values()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'module_id' => $payment->payable_id,
                'module_type' => class_basename((string) ($payment->payable_type ?? Reservation::class)),
                'date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d H:i:s') : '',
                'amount' => (float) ($payment->amount ?? 0),
                'remaining' => (float) ($payment->remaining ?? 0),
                'payment_way' => $payment->payment_way ?? null,
            ])
            ->values();

        $previousSessions = $orderedSessions->map(function ($session) use ($totalCost) {
            $sessionSummary = $this->reservationFinancialSummary($session, $totalCost);

            return [
                'id' => $session->id,
                'date' => $session->date,
                'reservation_number' => $session->reservation_number,
                'slot' => $session->slot,
                'status' => $session->status ?? 'waiting',
                'payment' => $session->payment ?? $sessionSummary['status'],
                'remaining' => $sessionSummary['remaining'],
                'paid_amount' => $sessionSummary['paid_amount'],
            ];
        })->values();

        return $this->returnJSON([
            'reservation' => [
                'id' => $parentReservation->id,
                'patient_name' => $parentReservation->patient?->name ?? 'N/A',
                'doctor_name' => $parentReservation->doctor?->user?->name ?? 'N/A',
                'date' => $parentReservation->date,
                'reservation_number' => $parentReservation->reservation_number,
                'slot' => $parentReservation->slot,
                'cost' => $totalCost,
                'payment' => $parentReservation->payment ?? $summary['status'],
            ],
            'previous_sessions' => $previousSessions,
            'payment_history' => $paymentHistory,
            'remaining' => (float) $summary['remaining'],
            'paid_amount' => (float) ($summary['paid_amount'] ?? 0),
            'payment' => $summary['status'],
            'can_add_payment' => (float) $summary['remaining'] > 0,
        ], 'Session context', 'success');
    }

    private function syncPayments($module, array $rows): void
    {
        $module->payments()->delete();

        $normalized = collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => [
                'payment_date' => !empty($row['date']) ? \Illuminate\Support\Carbon::parse((string) $row['date'])->format('Y-m-d H:i:s') : '',
                'amount' => (float) ($row['amount'] ?? 0),
                'remaining' => (float) ($row['remaining'] ?? 0),
                'payment_way' => isset($row['payment_way']) ? trim((string) $row['payment_way']) : null,
            ])
            ->filter(fn ($row) => $row['payment_date'] !== '')
            ->values()
            ->all();

        if (!empty($normalized)) {
            $module->payments()->createMany($normalized);
        }
    }

    private function paymentSummaryFromCollection($payments): array
    {
        $payments = collect($payments)->filter(fn ($p) => !is_null($p));
        $paidAmount = (float) $payments->sum(fn ($p) => (float) data_get($p, 'amount', 0));
        $latestPayment = $payments->sortByDesc(function ($p) {
            $createdAt = (string) data_get($p, 'created_at', '');
            $paymentDate = (string) data_get($p, 'payment_date', data_get($p, 'date', ''));
            $id = (int) data_get($p, 'id', 0);

            // Prefer creation order first, then payment date, then id.
            return sprintf('%s|%s|%010d', $createdAt, $paymentDate, $id);
        })->first();
        $latestRemaining = data_get($latestPayment, 'remaining');
        $remaining = (float) ($latestRemaining ?? 0);

        $status = 'not_paid';
        if ($remaining <= 0) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partially_paid';
        }

        return [
            'paid_amount' => $paidAmount,
            'remaining' => $remaining,
            'status' => $status,
        ];
    }

    private function paymentStatusFromRows(array $rows): string
    {
        if (empty($rows)) {
            return 'not_paid';
        }

        $paidAmount = (float) collect($rows)->sum(fn ($row) => (float) ($row['amount'] ?? 0));
        $latestRemaining = collect($rows)->last()['remaining'] ?? null;
        $remaining = (float) ($latestRemaining ?? 0);

        if ($remaining <= 0) {
            return 'paid';
        }
        if ($paidAmount > 0) {
            return 'partially_paid';
        }

        return 'not_paid';
    }

    private function reservationFinancialSummary(Reservation $reservation, float $fallbackCost = 0): array
    {
        $cost = (float) ($reservation->cost ?? 0);
        if ($cost <= 0) {
            $cost = max(0, $fallbackCost);
        }

        $payments = collect($reservation->payments ?? []);
        if ($payments->isEmpty()) {
            return [
                'paid_amount' => 0.0,
                'remaining' => $cost,
                'status' => $cost <= 0 ? 'paid' : 'not_paid',
            ];
        }

        return $this->paymentSummaryFromCollection($payments);
    }

    private function formatPayments($payments, string $moduleType, int $moduleId)
    {
        return collect($payments)->map(fn ($p) => [
            'id' => $p->id,
            'module_id' => $moduleId,
            'module_type' => class_basename($moduleType),
            'date' => $p->payment_date ? \Illuminate\Support\Carbon::parse($p->payment_date)->format('Y-m-d H:i:s') : '',
            'amount' => (float) ($p->amount ?? 0),
            'remaining' => (float) ($p->remaining ?? 0),
            'payment_way' => $p->payment_way,
        ])->values();
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
