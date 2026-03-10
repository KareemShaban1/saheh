<?php

namespace App\Http\Controllers\Backend\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Clinic\StoreServiceRequest;
use App\Http\Requests\Backend\Clinic\UpdateServiceRequest;
use App\Models\Service;
use App\Http\Traits\AuthorizeCheck;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Traits\Scopes\DoctorScopeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{
    use AuthorizeCheck, DoctorScopeTrait;

    public function index()
    {
        // $this->authorizeCheck('view-fees');

        $Services = Service::get();
        $doctors = Doctor::visibleTo(Auth::user())->get();


        return view(
            'backend.dashboards.clinic.pages.Services.index',
            compact('Services', 'doctors')
        );
    }

    public function data()
    {
        $query = Service::query();
        $query = $this->applyDoctorScope($query);
        $Services = $query->get();


        return DataTables::of($Services)

            ->addColumn('actions', function ($Service) {
                return '<button class="btn btn-warning btn-sm" onclick="editService(' . $Service->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteService(' . $Service->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->addColumn('doctor', function ($Service) {
                return $Service->doctor->user->name;
            })
            ->rawColumns(['roles', 'actions'])
            ->make(true);
    }

    public function store(StoreServiceRequest $request)
    {
        $this->authorizeCheck('add-fee');

        $validatedData = $request->validated();

        $Service = Service::create([
            'service_name' => $validatedData['service_name'],
            'fee' => $validatedData['fee'],
            'notes' => $$validatedData['notes'],
            'organization_id' => auth()->user()->organization_id,
            'organization_type' => auth()->user()->organization_type,
            'doctor_id' => $validatedData['doctor_id'],
        ]);


        return response()->json(['success' => 'Service fee added successfully!']);
    }

    public function edit($id)
    {
        $this->authorizeCheck('edit-fee');

        $Service = Service::findOrFail($id);
        return response()->json([
            'id' => $Service->id,
            'service_name' => $Service->service_name,
            'fee' => $Service->fee,
            'notes' => $Service->notes,
            'doctor_id' => $Service->doctor_id,
            'type' => $Service->type,
        ]);
    }

    public function update(UpdateServiceRequest $request, $id)
    {
        $this->authorizeCheck('edit-fee');

        $validatedData = $request->validated();

        try {

            $Service = Service::findOrFail($id);
            $Service->service_name = $validatedData['service_name'];
            $Service->fee = $validatedData['fee'];
            $Service->notes = $validatedData['notes'];
            $Service->doctor_id = $validatedData['doctor_id'];

            $Service->save();

            return response()->json(['toast_success' => 'Service fee updated successfully!']);
        } catch (\Exception $e) {

            return response()->json(['toast_error' => 'something went wrong!'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeCheck('delete-fee');

        try {
            $Service = Service::findOrFail($id);
            $Service->delete();
            return response()->json(['success' => 'Service fee deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }
}
