<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\Prescription\Models\Prescription;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use Modules\Clinic\ChronicDisease\Models\ChronicDisease;
use App\Models\ToothRecord;
use App\Models\ReservationTooth;
use App\Models\Shared\Clinic;
use App\Models\PrescriptionDrug;
use App\Models\MedicalAnalysis;
use App\Models\Ray;
use App\Models\Payment;
use App\Models\ModuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\BaseFrontApiController;
class PatientController extends BaseFrontApiController
{
    //
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

        $prescriptionsByReservation = Prescription::query()
            ->whereIn('reservation_id', $reservationIds)
            ->orderBy('id')
            ->get()
            ->groupBy('reservation_id');

        $allPrescriptionIds = $prescriptionsByReservation->flatten()->pluck('id')->values();

        $prescriptionDrugsByPrescription = PrescriptionDrug::query()
            ->whereIn('prescription_id', $allPrescriptionIds)
            ->with(['drug' => fn ($q) => $q->select('id', 'name')])
            ->orderBy('id')
            ->get()
            ->groupBy('prescription_id');

        $chronicByReservation = ChronicDisease::query()
            ->whereIn('reservation_id', $reservationIds)
            ->where('clinic_id', $clinicId)
            ->orderBy('id')
            ->get()
            ->groupBy('reservation_id');

        $analysesByReservation = MedicalAnalysis::withoutGlobalScopes()
            ->whereIn('reservation_id', $reservationIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('reservation_id');

        $raysByReservation = Ray::withoutGlobalScopes()
            ->whereIn('reservation_id', $reservationIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('reservation_id');

        $paymentsByReservation = Payment::query()
            ->where('payable_type', Reservation::class)
            ->whereIn('payable_id', $reservationIds)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('payable_id');

        $servicesByReservation = ModuleService::query()
            ->where('module_type', Reservation::class)
            ->whereIn('module_id', $reservationIds)
            ->with(['service' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'service_name', 'price')])
            ->orderBy('id')
            ->get()
            ->groupBy('module_id');

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

        $reservationHistory = $reservations->map(function ($reservation) use (
            $prescriptionsByReservation,
            $prescriptionDrugsByPrescription,
            $chronicByReservation,
            $analysesByReservation,
            $raysByReservation,
            $paymentsByReservation,
            $servicesByReservation,
            $glassesByReservation,
            $teethByReservation
        ) {
            $rxList = $prescriptionsByReservation->get($reservation->id, collect());
            $prescriptionsPayload = $rxList
                ->map(function ($prescription) use ($prescriptionDrugsByPrescription) {
                    $rows = $prescriptionDrugsByPrescription->get($prescription->id, collect());

                    return [
                        'id' => $prescription->id,
                        'title' => $prescription->title,
                        'notes' => $prescription->notes,
                        'images' => $prescription->images ?? [],
                        'drugs' => $rows
                            ->map(fn ($row) => [
                                'id' => $row->id,
                                'drug_id' => $row->drug_id,
                                'name' => $row->drug?->name,
                                'type' => $row->type,
                                'dose' => $row->dose,
                                'frequency' => $row->frequency,
                                'period' => $row->period,
                                'notes' => $row->notes,
                            ])
                            ->values(),
                    ];
                })
                ->values();
            $firstPrescription = $prescriptionsPayload->first();

            $glasses = $glassesByReservation->get($reservation->id, collect());
            $teethRows = $teethByReservation->get($reservation->id, collect());
            $chronicRows = $chronicByReservation->get($reservation->id, collect());
            $analysisRows = $analysesByReservation->get($reservation->id, collect());
            $rayRows = $raysByReservation->get($reservation->id, collect());
            $paymentRows = $paymentsByReservation->get($reservation->id, collect());
            $serviceRows = $servicesByReservation->get($reservation->id, collect());

            return [
                'id' => $reservation->id,
                'type' => $reservation->type,
                'parent_id' => $reservation->parent_id,
                'date' => $reservation->date,
                'time' => $reservation->time ?? null,
                'slot' => $reservation->slot,
                'month' => $reservation->month,
                'reservation_number' => $reservation->reservation_number,
                'first_diagnosis' => $reservation->first_diagnosis,
                'final_diagnosis' => $reservation->final_diagnosis,
                'cost' => $reservation->cost,
                'status' => $reservation->status,
                'acceptance' => $reservation->acceptance,
                'payment' => $reservation->payment,
                'attachments' => $reservation->images ?? [],
                'doctor_name' => $reservation->doctor?->user?->name ?? null,
                'prescriptions' => $prescriptionsPayload,
                'prescription' => $firstPrescription,
                'chronic_diseases' => $chronicRows
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'measure' => $c->measure,
                        'date' => $c->date,
                        'notes' => $c->notes,
                    ])
                    ->values(),
                'medical_analyses' => $analysisRows
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'date' => $a->date,
                        'report' => $a->report,
                        'payment' => $a->payment,
                        'cost' => $a->cost,
                        'doctor_name' => $a->doctor_name,
                        'images' => $a->images ?? [],
                    ])
                    ->values(),
                'rays' => $rayRows
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'date' => $r->date,
                        'report' => $r->report,
                        'payment' => $r->payment,
                        'cost' => $r->cost,
                        'images' => $r->images ?? [],
                    ])
                    ->values(),
                'payments' => $paymentRows
                    ->map(fn ($pay) => [
                        'id' => $pay->id,
                        'payment_date' => optional($pay->payment_date)->format('Y-m-d H:i'),
                        'amount' => $pay->amount,
                        'remaining' => $pay->remaining,
                        'payment_way' => $pay->payment_way,
                    ])
                    ->values(),
                'services' => $serviceRows
                    ->map(fn ($ms) => [
                        'id' => $ms->id,
                        'fee' => $ms->fee,
                        'notes' => $ms->notes,
                        'service_name' => $ms->service?->service_name,
                        'service_price' => $ms->service?->price,
                        'images' => $ms->images ?? [],
                    ])
                    ->values(),
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
                'whatsapp_number' => $patient->whatsapp_number ?? null,
                'email' => $patient->email,
                'address' => $patient->address,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
                'height' => $patient->height ?? null,
                'weight' => $patient->weight ?? null,
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
}
