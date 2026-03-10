<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\LabService;
use App\Models\LabServiceOption;
use App\Models\LabServiceCategory;
use Illuminate\Http\Request;

class LabServiceOptionController extends Controller
{
    //

    public function getOptions($id)
    {
        // Check if the category exists (optional but recommended)
        $categoryExists = LabServiceCategory::where('id', $id)->exists();
    
        if (! $categoryExists) {
            return response()->json([
                'status' => false,
                'message' => 'Service category not found.',
                'data' => []
            ], 404);
        }
    
        // Get related lab services
        $labServices = LabService::where('lab_service_category_id', $id)->get();
    
        if ($labServices->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No lab services found for this category.',
                'data' => []
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Options retrieved successfully.',
            'data' => $labServices
        ]);
    }
    
}
