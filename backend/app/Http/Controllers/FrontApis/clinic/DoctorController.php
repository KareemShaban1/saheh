<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use Modules\Clinic\Doctor\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Clinic;
use Modules\Clinic\Specialty\Models\Specialty;
use Modules\Clinic\User\Models\User;
use App\Http\Controllers\BaseFrontApiController;
use App\Models\Service;
use App\Models\ModuleService;

class DoctorController extends BaseFrontApiController
{
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
    //
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

}
