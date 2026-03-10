<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Traits\ApiHelperTrait;
use App\Models\Ray;
use Illuminate\Http\Request;

class RayController extends Controller
{
    //
    use ApiHelperTrait;

    public function index()
    {
        $rays = Ray::with('Services')->patient()->get();

        return $this->returnJSON($rays, 'All Rays', true);
    }

    public function show($id)
    {
        $ray = Ray::with('Services')->patient()->findOrFail($id);

        return $this->returnJSON($ray, 'Show Ray', true);
    }
}
