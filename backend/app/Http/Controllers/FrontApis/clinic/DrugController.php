<?php

namespace App\Http\Controllers\FrontApis\clinic;

use App\Http\Controllers\BaseFrontApiController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\Prescription\Models\Drug;

class DrugController extends BaseFrontApiController
{
    public function drugs(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $rows = Drug::query()
            ->with(['doctor.user:id,name'])
            ->where('clinic_id', $clinicId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($drug) => $this->formatDrug($drug))
            ->values();

        return $this->returnJSON(['data' => $rows], 'Drugs', 'success');
    }

    public function drugDetails($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $drug = Drug::query()
            ->with(['doctor.user:id,name'])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        return $this->returnJSON($this->formatDrug($drug), 'Drug details', 'success');
    }

    public function createDrug(Request $request)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $validated = $request->validate([
            'doctor_id' => 'required|integer|exists:doctors,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'dose' => 'required|string|max:255',
            'frequency' => 'required|string|max:255',
            'period' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $doctorExistsInClinic = Doctor::query()
            ->where('id', (int) $validated['doctor_id'])
            ->where('clinic_id', $clinicId)
            ->exists();
        if (!$doctorExistsInClinic) {
            throw ValidationException::withMessages([
                'doctor_id' => ['Selected doctor does not belong to this clinic.'],
            ]);
        }

        $drug = Drug::create([
            'clinic_id' => (int) $clinicId,
            'doctor_id' => (int) $validated['doctor_id'],
            'name' => trim((string) $validated['name']),
            'type' => trim((string) $validated['type']),
            'dose' => trim((string) $validated['dose']),
            'frequency' => trim((string) $validated['frequency']),
            'period' => trim((string) $validated['period']),
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        $drug->load(['doctor.user:id,name']);

        return $this->returnJSON($this->formatDrug($drug), 'Drug created', 'success');
    }

    public function updateDrug(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $drug = Drug::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'required|integer|exists:doctors,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'dose' => 'required|string|max:255',
            'frequency' => 'required|string|max:255',
            'period' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $doctorExistsInClinic = Doctor::query()
            ->where('id', (int) $validated['doctor_id'])
            ->where('clinic_id', $clinicId)
            ->exists();
        if (!$doctorExistsInClinic) {
            throw ValidationException::withMessages([
                'doctor_id' => ['Selected doctor does not belong to this clinic.'],
            ]);
        }

        $drug->update([
            'doctor_id' => (int) $validated['doctor_id'],
            'name' => trim((string) $validated['name']),
            'type' => trim((string) $validated['type']),
            'dose' => trim((string) $validated['dose']),
            'frequency' => trim((string) $validated['frequency']),
            'period' => trim((string) $validated['period']),
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        $drug->load(['doctor.user:id,name']);

        return $this->returnJSON($this->formatDrug($drug), 'Drug updated', 'success');
    }

    public function deleteDrug($id)
    {
        $this->ensureClinicAuth();
        $clinicId = request()->user()->organization->id ?? null;

        $drug = Drug::query()
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        $drug->delete();

        return $this->returnJSON(['id' => $id], 'Drug deleted', 'success');
    }

    private function formatDrug(Drug $drug): array
    {
        return [
            'id' => $drug->id,
            'clinic_id' => $drug->clinic_id,
            'doctor_id' => $drug->doctor_id,
            'doctor_name' => $drug->doctor?->user?->name ?? null,
            'name' => $drug->name,
            'type' => $drug->type,
            'dose' => $drug->dose,
            'frequency' => $drug->frequency,
            'period' => $drug->period,
            'notes' => $drug->notes,
            'created_at' => optional($drug->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}

