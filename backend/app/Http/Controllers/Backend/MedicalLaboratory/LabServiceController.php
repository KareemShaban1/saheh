<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\LabService;
use App\Models\MedicalLaboratory;
use App\Models\LabServiceCategory;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LabServiceController extends Controller
{
    public function index()
    {

        $labServices = LabService::get();
        $serviceCategories = LabServiceCategory::get();

        return view('backend.dashboards.medicalLaboratory.pages.labServices.index', 
        compact('labServices','serviceCategories'));
    }

    public function data()
    {
        $labServices = LabService::get();

        return DataTables::of($labServices)

            ->addColumn('category_name',function ($labService) {
                return $labService->category->category_name;
            })
            ->addColumn('actions', function ($labService) {
                return '
                    <button class="btn btn-warning btn-sm" onclick="editLabService(' . $labService->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteLabService(' . $labService->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })

            ->rawColumns(['roles', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {

        $request->validate([
            'lab_service_category_id' => 'required',
            'name' => 'required',
            'price' => 'required',
            'unit' => 'required',
            'normal_range' => 'required',
            'notes' => 'required',
        ]);

        $labService = LabService::create([
            'lab_service_category_id' => $request->lab_service_category_id,
            'name' => $request->name,
            'price' => $request->price,
            'unit' => $request->unit,
            'normal_range' => $request->normal_range,
            'notes' => $request->notes,
            'organization_id' => auth()->user()->organization_id,
            'organization_type' => MedicalLaboratory::class,
        ]);



        return response()->json(['success' => 'Service added successfully!']);
    }

    public function edit($id)
    {

        // 'lab_service_category_id',
        // 'name',
        // 'price',
        // 'unit',
        // 'normal_range',
        // 'organization_id',
        // 'organization_type',
        // 'notes'
        $labService = LabService::findOrFail($id);
        return response()->json([
            'id' => $labService->id,
            'lab_service_category_id'=>$labService->lab_service_category_id,
            'name' => $labService->name,
            'unit' => $labService->unit,
            'normal_range' => $labService->normal_range,
            'price' => $labService->price,
            'notes' => $labService->notes
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required',
            'price' => 'required',
            'unit' => 'required',
            'normal_range' => 'required',
            'notes' => 'required',
        ]);

        try {

            $labService = LabService::findOrFail($id);
            $labService->name = $request->name;
            $labService->unit = $request->unit;
            $labService->normal_range = $request->normal_range;
            $labService->price = $request->price;
            $labService->notes = $request->notes;
            $labService->save();

            return response()->json(['success' => 'Service updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $Service = Service::findOrFail($id);
            $Service->delete();
            return response()->json(['success' => 'Service fee deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }
}
