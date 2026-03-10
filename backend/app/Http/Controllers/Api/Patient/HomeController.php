<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Clinic\ClinicCollection;
use App\Http\Resources\MedicalLaboratory\MedicalLaboratoryCollection;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\RadiologyCenter\RadiologyCenterCollection;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiHelperTrait;

    public function index(Request $request)
    {
        $patient = Patient::with(
            'clinics',
            'medicalLaboratories',
            'radiologyCenters',
            'doctors.user',
            'doctors.clinic',
            // 'doctors.specialty',
            'doctors.reviews',
        )
            ->find(auth('patient_api')->user()->id);

        if (! $patient) {
            return $this->returnJSON(null, 'Patient not found', false, 404);
        }

        $withFullData = ! ($request->full_data == 'false');

        $doctors = $patient->doctors
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'doctor_name' => $doctor->user?->name,
                    'clinic_id' => $doctor->clinic_id,
                    'clinic_name' => $doctor->clinic?->name,
                    // 'speciality_id' => $doctor->specialty_id,
                    // 'speciality_name' => $doctor->specialty?->name,
                    'reviews' => $doctor->reviews?->toArray() ?? [],
                ];
            })
            ->values()
            ->all();

        $data = [
            'patient' => (new PatientResource($patient))->withFullData($withFullData),
            'doctors' => $doctors,
            'clinics' => (new ClinicCollection($patient->clinics))->withFullData($withFullData),
            'medicalLaboratories' => (new MedicalLaboratoryCollection($patient->medicalLaboratories))->withFullData($withFullData),
            'radiologyCenters' => (new RadiologyCenterCollection($patient->radiologyCenters))->withFullData($withFullData),
        ];

        return $this->returnJSON($data, 'All Patient Data', true);
    }
}