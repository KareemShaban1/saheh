<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiHelperTrait;
use Modules\Clinic\ChronicDisease\Models\ChronicDisease;
use Illuminate\Http\Request;

class ChronicDiseaseController extends Controller
{
    //

    use ApiHelperTrait;

    public function index()
    {
        $chronic_diseases = ChronicDisease::with('reservation')->patient()->get();

        return $this->returnJSON($chronic_diseases, 'Chronic Diseases', true);
    }

    public function show(Request $request, $id)
    {
        $chronic_disease = ChronicDisease::with('reservation')->patient()->findOrFail($id);

        return $this->returnJSON($chronic_disease, 'Chronic Disease', true);
    }
}
