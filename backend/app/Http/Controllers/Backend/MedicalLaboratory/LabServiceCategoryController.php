<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\MedicalLaboratory;
use App\Models\LabServiceCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LabServiceCategoryController extends Controller
{
    //

    public function index()
    {

        $serviceCategories = LabServiceCategory::get();

        return view(
            'backend.dashboards.medicalLaboratory.pages.serviceCategory.index',
            compact('serviceCategories')
        );
    }

    public function data()
    {
        $serviceCategories = LabServiceCategory::get();

        return DataTables::of($serviceCategories)
        ->addColumn('is_active', function ($row) {
            return $row->is_active
                ? '<span class="badge bg-success text-white">' . __("Active") . '</span>'
                : '<span class="badge bg-danger text-white">' . __("Inactive") . '</span>';
        })
            ->addColumn('actions', function ($row) {
                return '
                     <button class="btn btn-sm btn-info edit-category"
            data-id="' . $row->id . '"
            data-name="' . $row->category_name . '"
            data-active="' . $row->is_active . '">
             <i class="fa fa-edit"></i>
        </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteServiceCategory(' . $row->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['actions','is_active'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required',
            'is_active' => 'required',
        ]);

        LabServiceCategory::create([
            'category_name' => $request->category_name,
            'is_active' => $request->is_active,
            'organization_id' => auth()->user()->organization_id,
            'organization_type' => MedicalLaboratory::class,
        ]);



        return response()->json(['success' => 'Service category added successfully!']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required',
            'is_active' => 'required',
        ]);

        try {

            $serviceCatgory = LabServiceCategory::findOrFail($id);
            $serviceCatgory->category_name = $request->category_name;
            $serviceCatgory->is_active = $request->is_active;
            $serviceCatgory->save();

            return response()->json(['success' => 'Service category updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }

    public function destroy($id)
    {
        $category = LabServiceCategory::findOrFail($id);
        $category->delete();

        return response()->json(['success' => true, 'message' => __('Category deleted successfully.')]);
    }
}
