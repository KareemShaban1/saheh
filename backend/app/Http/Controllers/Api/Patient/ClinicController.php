<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Clinic\ClinicCollection;
use App\Http\Resources\Clinic\ClinicResource;
use App\Models\Clinic;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    //
    use ApiHelperTrait;
    public function index(Request $request)

    {
        $patient = Patient::with('clinics')->find(auth()->user()->id);

        $clinicsCollection = (new ClinicCollection($patient->clinics))->withFullData(!($request->full_data == 'false'));


        return $this->returnJSON($clinicsCollection, 'All Clinics', true);
    }

    public function show(Request $request, $id)
    {
        $clinic = Clinic::with(['reviews' => function ($query) {
            $query->with(['patient', 'doctor'])->latest();
        }])->findOrFail($id);

        $clinicResource = (new ClinicResource($clinic))->withFullData(!($request->full_data == 'false'));

        return $this->returnJSON($clinicResource, 'Show Clinic', true);
    }
}
