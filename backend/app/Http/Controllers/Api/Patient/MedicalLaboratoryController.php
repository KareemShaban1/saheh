<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\MedicalLaboratory\MedicalLaboratoryCollection;
use App\Http\Resources\MedicalLaboratory\MedicalLaboratoryResource;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;

class MedicalLaboratoryController extends Controller
{
    //
    use ApiHelperTrait;
    public function index(Request $request)

    {
        $patient = Patient::with('medicalLaboratories')->find(auth()->user()->id);

        $medicalLabCollection = (new MedicalLaboratoryCollection($patient->medicalLaboratories))->withFullData(!($request->full_data == 'false'));


        return $this->returnJSON($medicalLabCollection, 'All Medical Laboratories', true);
    }

    public function show(Request $request, $id)
    {
        $medicalLaboratory = MedicalLaboratory::with(['reviews' => function($query) {
            $query->with(['patient', 'doctor'])->latest();
        }])->findOrFail($id);

        $medicalLaboratoryResource = (new MedicalLaboratoryResource($medicalLaboratory))->withFullData(!($request->full_data == 'false'));

        return $this->returnJSON($medicalLaboratoryResource, 'Show Medical Laboratory', true);
    }


}
