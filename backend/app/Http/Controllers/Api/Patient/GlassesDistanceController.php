<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiHelperTrait;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use Illuminate\Http\Request;

class GlassesDistanceController extends Controller
{
    //
    use ApiHelperTrait;
    public function index()
    {
        $glasses_distances = GlassesDistance::with('reservation')->patient()->get();

        return $this->returnJSON($glasses_distances, 'Glasses Distances', true);


    }

    public function show(Request $request, $id)
    {
        $glasses_distance = GlassesDistance::with('reservation')->patient()->findOrFail($id);

        return $this->returnJSON($glasses_distance, 'Glasses Distance', true);

    }
}
