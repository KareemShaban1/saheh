<?php

namespace Modules\Clinic\Patient\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\Patient\Http\Requests\Backend\StorePatientRequest;
use Modules\Clinic\Patient\Http\Requests\Backend\UpdatePatientRequest;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Settings;
use App\Traits\WhatsAppNotificationTrait;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use App\Http\Traits\AuthorizeCheck;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class PatientController extends Controller
{
    use AuthorizeCheck, WhatsAppNotificationTrait;

    protected $patient;
    protected $reservation;
    protected $settings;

    public function __construct(Patient $patient, Reservation $reservation, Settings $settings)
    {
        $this->patient = $patient;
        $this->reservation = $reservation;
        $this->settings = $settings;
    }

    // function to show all patients
    public function index()
    {
        $this->authorizeCheck('view-patients');

        return view('backend.dashboards.clinic.pages.patients.index');
    }

    public function data()
    {
        $this->authorizeCheck('view-patients');

        $patients = Patient::visibleTo(Auth::user())->get();


        return DataTables::of($patients)
            ->addColumn('number_of_reservations', function ($patient) {
                return $patient->reservations->count();
            })
            ->addColumn('doctor', function ($patient) {
                return $patient->doctors
                    ->map(function ($doctor) {
                        return '<span class="badge bg-primary text-white" style="font-size: 13px;">' . e($doctor->user->name) . '</span>';
                    })
                    ->implode(' ');
            })
            ->addColumn('add_reservation', function ($patient) {
                return '<a href="' . route('clinic.reservations.add', $patient->id) . '"
                            class="btn btn-info btn-sm">
                            ' . trans('backend/patients_trans.Add_Reservation') . '
                        </a>';
            })
            ->addColumn('patient_card', function ($patient) {
                return '<a href="' . route('clinic.patients.patient_pdf', $patient->id) . '"
                            class="btn btn-primary btn-sm">
                            ' . trans('backend/patients_trans.Show_Patient_Card') . '
                        </a>';
            })
            ->addColumn('action', function ($patient) {

                $toothRecordUrl = route('clinic.tooth-record.show', $patient->id);

                $editUrl = route('clinic.patients.edit', $patient->id);
                // $deleteUrl = route('clinic.patients.destroy', $patient->id);
                $viewUrl = route('clinic.patients.show', $patient->id);
                // $assignUrl = route('clinic.patients.assignPatient', $patient->id);
                $unassignUrl = route('clinic.patients.unassignPatient', $patient->id);

                $actions = '
                <a href="' . $toothRecordUrl . '" class="btn btn-danger btn-sm">
                        <i class="fa fa-stethoscope"></i>
                    </a>
                    <a href="' . $viewUrl . '" class="btn btn-primary btn-sm">
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="' . $editUrl . '" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit"></i>
                    </a>
                    ';

                if ($patient->reservations->count() == 0) {
                    $actions .= '
                        <form action="' . $unassignUrl . '" method="POST" style="display:inline;">
                            ' . csrf_field() . '
                            ' . method_field('POST') . '
                            <button type="submit" class="btn btn-secondary btn-sm"
                                    onclick="return confirm(\'Are you sure you want to unassign this item?\')">
                                <i class="fa fa-link-slash"></i>
                            </button>
                        </form>';
                }

                return $actions;
            })
            ->rawColumns(['add_reservation', 'add_online_reservation', 'patient_card', 'doctor', 'action'])
            ->make(true);
    }


    // show user data based on id
    public function show($id)
    {
        $this->authorizeCheck('view-patients');

        // get patient with his reservations based on id
        $patient = $this->patient->withCount('reservations')->findOrFail($id);


        return view('backend.dashboards.clinic.pages.patients.show', compact('patient'));
    }

    public function patientPdf($id)
    {
        $this->authorizeCheck('view-patients');

        $patient = $this->patient->find($id);

        // get settings of the app from settings table
        $collection = $this->settings->select('key', 'value')->get();
        $settings = $collection->pluck('value', 'key');

        $data = [
            'patient' =>  $patient,
            'settings' => $settings
        ];

        $pdf = PDF::loadView(
            'backend.dashboards.clinic.pages.patients.patient_card',
            $data,
            [],
            [
                // 'format' => 'A5-L',
                'format' => [190, 100] // W - H
            ]
        );
        return $pdf->stream($patient->name . '.pdf');
    }

    public function add()
    {
        $this->authorizeCheck('add-patient');

        $doctors = Doctor::all();

        return view('backend.dashboards.clinic.pages.patients.add', compact('doctors'));
    }


    public function store(StorePatientRequest $request)
    {

        $this->authorizeCheck('add-patient');

        try {


            $data = $request->validated();

            $data['password'] = Hash::make($request->password);

            $patient = Patient::create($data);

            foreach ($request->doctor_ids as $doctor_id) {
                $patient->doctors()->attach($doctor_id, [
                    'organization_type' => Clinic::class,
                    'organization_id' => auth()->user()->organization->id,
                    'assigned' => true,
                ]);
            }

            if ($this->isWhatsAppEnabled()) {
                $this->sendPatientCredentialsWhatsApp($patient, $request->password);
            }


            return redirect()->route('clinic.patients.index')
            ->with('toast_success', 'Patient added Successfully');
        } catch (\Exception $e) {

            // dd($e);
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }


    public function edit($id)
    {
        $this->authorizeCheck('edit-patient');

        $patient = Patient::with('doctors')->findOrFail($id);
        $allDoctors = Doctor::with('user')->get();
        $selectedDoctorIds = $patient->doctors->pluck('id')->toArray();

        return view(
            'backend.dashboards.clinic.pages.patients.edit',
            compact('patient', 'allDoctors', 'selectedDoctorIds')
        );
    }


    public function update(UpdatePatientRequest $request, $id)
    {

        $this->authorizeCheck('edit-patient');

        try {
            $request->validated();

            $data = $request->all();

            $patient = $this->patient->findOrFail($id);

            $patient->update($data);

            if ($request->has('doctor_ids') && is_array($request->doctor_ids) && count($request->doctor_ids) > 0) {

                foreach ($request->doctor_ids as $doctor_id) {
                    $exists = DB::table('patient_organization')
                        ->where('patient_id', $id)
                        ->where('organization_type', Clinic::class)
                        ->where('organization_id', auth()->user()->organization->id)
                        ->where('doctor_id', $doctor_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('patient_organization')->insert([
                            'patient_id'         => $id,
                            'doctor_id'          => $doctor_id,
                            'organization_type'  => Clinic::class,
                            'organization_id'    => auth()->user()->organization->id,
                            'assigned'           => true,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }

                // Optional: Unassign doctors that are no longer selected
                DB::table('patient_organization')
                    ->where('patient_id', $id)
                    ->where('organization_type', Clinic::class)
                    ->where('organization_id', auth()->user()->organization->id)
                    ->whereNotIn('doctor_id', $request->doctor_ids)
                    ->delete();

            } else {
                // No doctor IDs passed → Unassign all doctors
                DB::table('patient_organization')
                    ->where('patient_id', $id)
                    ->where('organization_type', Clinic::class)
                    ->where('organization_id', auth()->user()->organization->id)
                    ->update([
                        'doctor_id' => null,
                        'assigned'  => true,
                        'updated_at'=> now(),
                    ]);
            }


            return redirect()->route('clinic.patients.index')
            ->with('toast_success', 'Patient updated Successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function destroy($id)
    {
        $this->authorizeCheck('delete-patient');

        $patient = $this->patient->findOrFail($id);

        $patient->delete();

        return redirect()->route('clinic.patients.index');
    }



    public function trash()
    {
        $this->authorizeCheck('delete-patient');

        $patients = $this->patient->onlyTrashed()->get();
        return view('backend.dashboards.clinic.pages.patients.trash', compact('patients'));
    }

    public function trashData()
    {
        $patients = $this->patient->onlyTrashed()->get();
        return DataTables::of($patients)
            ->addColumn('action', function ($patient) {
                $restoreUrl = route('backend.patients.restore', $patient->id);
                $forceDeleteUrl = route('backend.patients.forceDelete', $patient->id);

                $actions = '
                <a href="' . $restoreUrl . '" class="btn btn-info btn-sm">
                    <i class="fa fa-edit"></i>
                </a>
                <form action="' . $forceDeleteUrl . '" method="POST" style="display:inline;">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm(\'Are you sure you want to delete this item?\')">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>';
                return $actions;
            })

            ->rawColumns(['restore', 'force_delete'])
            ->make(true);
    }



    public function restore($id)
    {
        $this->authorizeCheck('restore-patient');

        $patients = $this->patient->onlyTrashed()->findOrFail($id);

        $patients->restore();

        return redirect()->route('clinic.patients.index');
    }


    public function forceDelete($id)
    {

        $this->authorizeCheck('force-delete-patient');

        $patients = $this->patient->onlyTrashed()->findOrFail($id);

        $patients->forceDelete();

        return redirect()->route('clinic.patients.index');
    }

    public function add_patient_code()
    {
        return view('backend.dashboards.clinic.pages.patients.add_patient_code');
    }


    public function search(Request $request)
    {
        $patient = Patient::where('patient_code', $request->code)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.'
            ]);
        }

        // Load only assigned medical laboratories
        $assignedLabs = $patient->clinics()
            ->wherePivot('assigned', 1)
            ->get();

        foreach ($assignedLabs as $medicalLab) {
            if ($medicalLab->id == auth()->user()->organization->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient already assigned to your clinic.'
                ]);
            }
        }

        // Patient found and not assigned to this lab
        return response()->json([
            'success' => true,
            'patient' => $patient
        ]);
    }



    public function assignPatient(Request $request)
    {

        DB::table('patient_organization')->insert([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'organization_id' => auth()->user()->organization->id,
            'organization_type' => Clinic::class,
            'assigned' => 1,
        ]);


        return response()->json(['message' => 'Patient assigned successfully']);
    }

    public function unassignPatient($patient_id)
    {
        DB::table('patient_organization')
            ->where('patient_id', $patient_id)
            ->update([
                'assigned' => 0,
            ]);


        return redirect()->back()->with('toast_success', 'Patient unassigned successfully');
    }
}
