<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiHelperTrait;
use App\Models\MedicalAnalysis;

class MedicalAnalysisController extends Controller
{
    //
    use ApiHelperTrait;

    public function index()
    {
        $medical_analyses = MedicalAnalysis::with('reservation', 'labServiceOptions')->patient()->get();

        return $this->returnJSON($medical_analyses, 'Medical Analyses', true);
    }

    public function show($id)
    {
        $medical_analysis = MedicalAnalysis::with('reservation', 'labServiceOptions')->patient()->findOrFail($id);

        return $this->returnJSON($medical_analysis, 'Medical Analysis', true);
    }
}
