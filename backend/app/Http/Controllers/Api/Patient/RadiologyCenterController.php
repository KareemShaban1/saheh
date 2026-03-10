<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\RadiologyCenter\RadiologyCenterCollection;
use App\Http\Resources\RadiologyCenter\RadiologyCenterResource;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Models\RadiologyCenter;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;

class RadiologyCenterController extends Controller
{
    //
    use ApiHelperTrait;
    public function index(Request $request)

    {
        $patient = Patient::with('radiologyCenters')->find(auth()->user()->id);

        $radiologyCentersCollection = (new RadiologyCenterCollection($patient->radiologyCenters))->withFullData(!($request->full_data == 'false'));


        return $this->returnJSON($radiologyCentersCollection, 'All Radiology Centers', true);
    }

    public function show(Request $request,$id)
    {
        $radiologyCenter = RadiologyCenter::with(['reviews' => function($query) {
            $query->with(['patient', 'doctor'])->latest();
        }])->findOrFail($id);

        $radiologyCenterResource = (new RadiologyCenterResource($radiologyCenter))->withFullData(!($request->full_data == 'false'));

        return $this->returnJSON($radiologyCenterResource, 'Show Radiology Center', true);
    }


}
