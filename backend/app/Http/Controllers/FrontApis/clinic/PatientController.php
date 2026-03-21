<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Shared\Prescription;
use App\Models\Shared\Drug;
use App\Models\Shared\GlassesDistance;
use App\Models\Shared\ReservationTooth;
use App\Models\Shared\ToothRecord;
use App\Models\Shared\Clinic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
}
