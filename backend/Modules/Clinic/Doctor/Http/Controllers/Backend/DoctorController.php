<?php

namespace Modules\Clinic\Doctor\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\Doctor\Http\Requests\Backend\StoreDoctorRequest;
use Modules\Clinic\Doctor\Http\Requests\Backend\UpdateDoctorRequest;
use App\Http\Traits\AuthorizeCheck;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Specialty;
use Modules\Clinic\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DoctorController extends Controller
{
    //
    use AuthorizeCheck;

    public function index()
    {
        $specializations = Specialty::all();
        return view('backend.dashboards.clinic.pages.doctors.index', compact('specializations'));
    }

    public function data()
    {
        $doctors = Doctor::all();

        return DataTables::of($doctors)
            ->addColumn('name', function ($doctor) {
                return $doctor->user->name;
            })
            ->addColumn('email', function ($doctor) {
                return $doctor->user->email;
            })
            ->addColumn('phone', function ($doctor) {
                return $doctor->phone;
            })
            ->addColumn('action', function ($doctor) {
                return '<button class="btn btn-warning btn-sm" onclick="editDoctor(' . $doctor->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteDoctor(' . $doctor->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

public function store(StoreDoctorRequest $request)
{
    $validatedData = $request->validated();

    DB::beginTransaction();

    try {
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'job_title'=>'doctor',
            'organization_type' => Clinic::class,
            'organization_id' => auth()->user()->organization_id,
        ]);

        $user->assignRole('clinic-doctor');

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'clinic_id' => auth()->user()->organization_id,
            'phone' => $validatedData['phone'],
            'certifications' => $validatedData['certifications'],
            'specialty_id' => $validatedData['specialty_id'],
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Doctor created successfully!',
            'data' => [
                'user' => $user,
                'doctor' => $doctor,
            ],
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();

        // Log the error for debugging
        \Log::error('Doctor creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Doctor not created!',
            'error' => $e->getMessage(), // return real error message
        ], 500);
    }
}


    public function edit($id)
    {
        $this->authorizeCheck('edit-doctor');

        $doctor = Doctor::findOrFail($id);
        $user = User::findOrFail($doctor->user_id);

        return response()->json([
            'id' => $doctor->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $doctor->phone,
            'certifications' => $doctor->certifications,
            'specialty_id' => $doctor->specialty_id,
        ]);
    }

    public function update(UpdateDoctorRequest $request, $id)
    {
        $this->authorizeCheck('edit-doctor');

        $validatedData = $request->validated();

        $doctor = Doctor::findOrFail($id);
        $doctor->update([
            'phone' => $validatedData['phone'],
            'certifications' => $validatedData['certifications'],
            'specialty_id' => $validatedData['specialty_id'],
        ]);

        $user = User::findOrFail($doctor->user_id);
        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']) ?? $user->password,
        ]);


        return response()->json(['success' => 'Doctor updated successfully!']);
    }

    public function list()
    {
        $doctors = Doctor::with('user')->get();
        return response()->json(['success' => true, 'doctors' => $doctors]);
    }

    public function destroy($id)
    {
        $this->authorizeCheck('delete-doctor');

        try {
            $doctor = Doctor::findOrFail($id);
            $user = User::findOrFail($doctor->user_id);
            $doctor->delete();
            $user->delete();
            return response()->json(['success' => 'Doctor deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }
}
