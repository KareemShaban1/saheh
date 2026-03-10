<?php

namespace App\Http\Controllers\Backend\RadiologyCenter;

use App\Http\Controllers\Controller;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{
    public function index()
    {

        $Services = Service::get();

        return view('backend.dashboards.radiologyCenter.pages.Services.index', compact('Services'));
    }

    public function data()
    {
        $Services = Service::get();

        return DataTables::of($Services)

            ->addColumn('actions', function ($Service) {
                return '<button class="btn btn-warning btn-sm" onclick="editService(' . $Service->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteService(' . $Service->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['roles', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required',
            'fee' => 'required',
            'notes' => 'required',
        ]);

        $Service = Service::create([
            'service_name' => $request->service_name,
            'fee' => $request->fee,
            'notes' => $request->notes,
            'organization_id' => auth()->user()->organization_id,
            'organization_type' => RadiologyCenter::class,
        ]);



        return response()->json(['success' => 'Service fee added successfully!']);
    }

    public function edit($id)
    {
        $Service = Service::findOrFail($id);
        return response()->json([
            'id' => $Service->id,
            'service_name' => $Service->service_name,
            'fee' => $Service->fee,
            'notes' => $Service->notes
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'service_name' => 'required',
            'fee' => 'required',
            'notes' => 'required',
        ]);

        try {

            $Service = Service::findOrFail($id);
            $Service->service_name = $request->service_name;
            $Service->fee = $request->fee;
            $Service->notes = $request->notes;
            $Service->save();

            return response()->json(['success' => 'Service fee updated successfully!']);
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
