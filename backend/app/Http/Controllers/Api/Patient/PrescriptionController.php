<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiHelperTrait;
use Modules\Clinic\Prescription\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    //
    use ApiHelperTrait;

    public function index()
    {
        $prescriptions = Prescription::with('reservation')->patient()->get();

        return $this->returnJSON($prescriptions, 'Prescriptions', true);
    }

    public function show($id)
    {
        $prescription = Prescription::with('reservation')->patient()->findOrFail($id);

        return $this->returnJSON($prescription, 'Prescription', true);
    }

}
