<?php

namespace Modules\Clinic\Reservation\Http\Controllers\Backend;

use Modules\Clinic\Reservation\Http\Requests\Backend\StoreReservationRequest;
use Modules\Clinic\Reservation\Http\Requests\Backend\UpdateReservationRequest;
use Modules\Clinic\ChronicDisease\Models\ChronicDisease;
use App\Models\SystemControl;
use App\Models\ModuleService;
use App\Models\Settings;
use Modules\Clinic\Prescription\Models\Drug;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use Modules\Clinic\Prescription\Models\Prescription;
use App\Models\Ray;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Http\Controllers\Controller;
use App\Http\Traits\AuthorizeCheck;
use App\Http\Traits\TimeSlotsTrait;
use App\Traits\Scopes\DoctorScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Yajra\DataTables\Facades\DataTables;

class ReservationController extends Controller
{
    use TimeSlotsTrait, AuthorizeCheck, DoctorScopeTrait;

    protected $reservation;
    protected $systemControl;
    protected $reservationNumber;
    protected $settings;
    protected $patient;
    protected $chronic_disease;
    protected $drug;
    protected $ray;
    protected $reservationSlots;

    public function __construct(
        Reservation $reservation,
        SystemControl $systemControl,
        ReservationNumber $reservationNumber,
        Settings $settings,
        Patient $patient,
        ChronicDisease $chronic_disease,
        Drug $drug,
        Ray $ray,
        ReservationSlots $reservationSlots
    ) {
        $this->reservation = $reservation;
        $this->systemControl = $systemControl;
        $this->reservationNumber = $reservationNumber;
        $this->settings = $settings;
        $this->patient = $patient;
        $this->chronic_disease = $chronic_disease;
        $this->drug = $drug;
        $this->ray = $ray;
        $this->$reservationSlots = $reservationSlots;
    }


    public function index()
    {
        $this->authorizeCheck('view-reservations');

        $reservations = $this->reservation->with('patient:id,name')->get();
        $clinic_type = $this->settings->where('key', 'clinic_type')->value('value');

        return view(
            'backend.dashboards.clinic.pages.reservations.index',
            compact('reservations',  'clinic_type')
        );
    }


    public function data(Request $request)
    {

        $query = $this->reservation->with('patient:id,name');
        $query = $this->applyDoctorScope($query);

        if ($request->has('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to = Carbon::parse($request->to_date)->endOfDay();
            $query->whereBetween('date', [$from, $to]);
        }


        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('patient_name', function ($reservation) {
                return $reservation->patient->name;
            })
            ->addColumn('doctor_name', function ($reservation) {
                return $reservation->doctor->user->name;
            })
            // ->addColumn('type', function ($reservation) {
            //     switch ($reservation->type) {
            //         case 'check':
            //             return trans('backend/reservations_trans.Check');
            //         case 'recheck':
            //             return trans('backend/reservations_trans.Recheck');
            //         case 'consultation':
            //             return trans('backend/reservations_trans.Consultation');
            //         default:
            //             return '-';
            //     }
            // })
            ->addColumn('payment', function ($reservation) {
                if ($reservation->payment == 'paid') {
                    return '<span class="badge badge-rounded badge-success p-2 m-2">' .
                        trans('backend/reservations_trans.Paid')
                        . '</span>';
                } elseif ($reservation->payment == 'not_paid') {
                    return '<span class="badge badge-rounded badge-danger p-2 m-2">' .
                        trans('backend/reservations_trans.Not_Paid')
                        . '</span>';
                }
            })
            ->addColumn('status', function ($reservation) {
                $status = '';

                // Determine the current status badge
                if ($reservation->status == 'waiting') {
                    $status = '<span class="badge badge-rounded badge-warning text-white p-2">' .
                        trans('backend/reservations_trans.Waiting') .
                        '</span>';
                } elseif ($reservation->status == 'entered') {
                    $status = '<span class="badge badge-rounded badge-success p-2">' .
                        trans('backend/reservations_trans.Entered') .
                        '</span>';
                } elseif ($reservation->status == 'finished') {
                    $status = '<span class="badge badge-rounded badge-info p-2">' .
                        trans('backend/reservations_trans.Finished') .
                        '</span>';
                } elseif ($reservation->status == 'cancelled') {
                    $status = '<span class="badge badge-rounded badge-danger p-2">' .
                        trans('backend/reservations_trans.Cancelled') .
                        '</span>';
                }

                // Create a <select> dropdown for changing status
                $dropdown = '<select class="form-control p-0 res-status-select" data-reservation-id="' . $reservation->id . '">
                                <option value="waiting" ' . ($reservation->status == 'waiting' ? 'selected' : '') . '>' . trans('backend/reservations_trans.Waiting') . '</option>
                                <option value="entered" ' . ($reservation->status == 'entered' ? 'selected' : '') . '>' . trans('backend/reservations_trans.Entered') . '</option>
                                <option value="finished" ' . ($reservation->status == 'finished' ? 'selected' : '') . '>' . trans('backend/reservations_trans.Finished') . '</option>
                                <option value="cancelled" ' . ($reservation->status == 'cancelled' ? 'selected' : '') . '>' . trans('backend/reservations_trans.Cancelled') . '</option>
                            </select>';

                return $status . $dropdown;
            })

            ->addColumn('chronic_disease_action', function ($reservation) {
                $chronicDiseaseExists = ChronicDisease::where('reservation_id', $reservation->id)->exists();

                $actions = [];

                // Always show Add
                $actions[] = '<a class="dropdown-item" href="' . route('clinic.chronic_diseases.add', $reservation->id) . '">
                    <i class="fas fa-plus-circle mr-1"></i> ' . trans('backend/reservations_trans.Add') . '</a>';

                if ($chronicDiseaseExists) {
                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.reservations.editChronicDisease', $reservation->id) . '">
                        <i class="fas fa-edit mr-1"></i> ' . trans('backend/reservations_trans.Edit') . '</a>';
                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.chronic_diseases.show', $reservation->id) . '">
                        <i class="fas fa-eye mr-1"></i> ' . trans('backend/reservations_trans.Show') . '</a>';
                }

                return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cogs"></i> ' . trans('backend/reservations_trans.Chronic_Diseases') . '
                        </button>
                        <div class="dropdown-menu">
                            ' . implode('', $actions) . '
                        </div>
                    </div>';
            })
            // ->addColumn('rays_action', function ($reservation) {
            //     $rayExists = Ray::where('reservation_id', $reservation->id)->exists();

            //     $actions = [];

            //     // Always show Add
            //     $actions[] = '<a class="dropdown-item" href="' . route('clinic.rays.add', $reservation->id) . '">
            //         <i class="fas fa-plus-circle mr-1"></i> ' . trans('backend/reservations_trans.Add') . '</a>';

            //     if ($rayExists) {
            //         $actions[] = '<a class="dropdown-item" href="' . route('clinic.rays.edit', $reservation->id) . '">
            //             <i class="fas fa-edit mr-1"></i> ' . trans('backend/reservations_trans.Edit') . '</a>';
            //         $actions[] = '<a class="dropdown-item" href="' . route('clinic.rays.show', $reservation->id) . '">
            //             <i class="fas fa-eye mr-1"></i> ' . trans('backend/reservations_trans.Show') . '</a>';
            //     }

            //     return '
            //         <div class="dropdown">
            //             <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
            //                 <i class="fas fa-cogs"></i> ' . trans('backend/reservations_trans.Rays') . '
            //             </button>
            //             <div class="dropdown-menu">
            //                 ' . implode('', $actions) . '
            //             </div>
            //         </div>';
            // })

            ->addColumn('prescription_action', function ($reservation) {
                $prescriptionExists = Prescription::where('reservation_id', $reservation->id)->exists();
                $hasDrugs = Drug::where('reservation_id', $reservation->id)->exists();

                $actions = [];

                // Always allow Add
                $actions[] = '<a class="dropdown-item" href="' . route('clinic.prescription.add', $reservation->id) . '">
                    <i class="fas fa-plus-circle mr-1"></i> ' . trans('backend/reservations_trans.Add') . '</a>';

                if ($prescriptionExists || $hasDrugs) {
                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.prescription.show', $reservation->id) . '">
                        <i class="fas fa-eye mr-1"></i> ' . trans('backend/reservations_trans.Show') . '</a>';

                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.prescription.edit', $reservation->id) . '">
                        <i class="fas fa-edit mr-1"></i> ' . trans('backend/reservations_trans.Edit') . '</a>';

                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.prescription.arabic_prescription_pdf', $reservation->id) . '">
                        <i class="fas fa-file-pdf mr-1"></i> ' . trans('backend/reservations_trans.Arabic_Prescription') . '</a>';

                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.prescription.english_prescription_pdf', $reservation->id) . '">
                        <i class="fas fa-file-pdf mr-1"></i> ' . trans('backend/reservations_trans.English_Prescription') . '</a>';
                }

                return '
                <div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-cogs"></i> ' . trans('backend/reservations_trans.Prescription') . '
                    </button>
                    <div class="dropdown-menu">
                        ' . implode('', $actions) . '
                    </div>
                </div>';
            })


            ->addColumn('glasses_distance_action', function ($reservation) {
                $glassesDistanceExists = GlassesDistance::where('reservation_id', $reservation->id)->exists();

                $actions = [];

                // Always show Add
                $actions[] = '<a class="dropdown-item" href="' . route('clinic.glasses_distance.add', $reservation->id) . '">
                    <i class="fas fa-plus-circle mr-1"></i> ' . trans('backend/reservations_trans.Add') . '</a>';

                if ($glassesDistanceExists) {
                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.glasses_distance.edit', $reservation->id) . '">
                        <i class="fas fa-edit mr-1"></i> ' . trans('backend/reservations_trans.Edit') . '</a>';

                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.glasses_distance.glasses_distance_pdf', $reservation->id) . '">
                        <i class="fas fa-file-pdf mr-1"></i> ' . trans('backend/reservations_trans.Glasses_Distance_PDF') . '</a>';
                }

                return '
                <div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-cogs"></i> ' . trans('backend/reservations_trans.Glasses_Distance') . '
                    </button>
                    <div class="dropdown-menu">
                        ' . implode('', $actions) . '
                    </div>
                </div>';
            })

            ->addColumn('number_slot', function ($reservation) {
                $display = '-';
                if ($reservation->reservation_number !== null && $reservation->reservation_number !== '') {
                    $display = (string) $reservation->reservation_number;
                } elseif ($reservation->slot !== null && $reservation->slot !== '') {
                    $display = $reservation->slot;
                }
                $swapBtn = '<button type="button" class="btn btn-info btn-sm ms-1 btn-swap-slot" data-reservation-id="' . $reservation->id . '" title="' . __('Swap number/slot') . '">
                    <i class="fa fa-exchange-alt"></i>
                </button>';
                return '<span class="number-slot-display">' . e($display) . '</span>' . $swapBtn;
            })

            ->addColumn('acceptance', function ($reservation) {

                if ($reservation->acceptance == 'approved') {
                    return '<span class="badge badge-rounded badge-success text-white p-2 m-2">' .
                        trans('backend/reservations_trans.Approved') .
                        '</span>';
                } elseif ($reservation->acceptance == 'not_approved' || $reservation->acceptance == 'pending') {

                    return '<span class="badge badge-rounded' . ($reservation->acceptance == 'pending' ? ' badge-warning' : ' badge-danger') . ' text-white p-2 m-2">' .
                        trans('backend/reservations_trans.' . $reservation->acceptance) .
                        '</span>' .
                        '<div class="res_control">' .
                        // '<a href="' . route("clinic.reservations_options.reservation_acceptance", [$reservation->id, "pending"]) . '" ' .
                        // 'class="btn btn-success btn-sm text-white">' .
                        // '<i class="fa-solid fa-check"></i>' .
                        // '</a>' .
                        '<a href="' . route("clinic.reservations_options.reservation_acceptance", [$reservation->id, "approved"]) . '" ' .
                        'class="btn btn-success btn-sm text-white">' .
                        '<i class="fa-solid fa-check"></i>' .
                        '</a>' .
                        '<a href="' . route('clinic.reservations_options.reservation_acceptance', [$reservation->id, 'not_approved']) . '" ' .
                        'class="btn btn-danger btn-sm text-white">' .
                        '<i class="fa-solid fa-xmark"></i>' .
                        '</a>' .
                        '</div>';
                }
            })

            ->addColumn('actions', function ($reservation) {
                // Define the actions HTML
                $actions = '<div class="res_control">
                                <a href="' . route('clinic.reservations.show', $reservation->id) . '"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="' . route('clinic.reservations.edit', $reservation->id) . '"
                                    class="btn btn-warning btn-sm">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="' . route('clinic.reservations.destroy', $reservation->id) . '"
                                    method="POST" style="display:inline">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>';

                return $actions;
            })
            ->rawColumns([
                'payment',
                'status',
                'number_slot',
                'acceptance',
                'actions',
                'ray_action',
                'analysis_action',
                'chronic_disease_action',
                'prescription_action',
                'glasses_distance_action',
                // 'rays_action'
            ])
            ->make(true);
    }




    public function todayReservations()
    {
        $this->authorizeCheck('view-reservations');

        $today = Carbon::today();
        $reservations = $this->reservation->whereDate('date', $today)->get();
        $reservation_settings = $this->systemControl->pluck('value', 'key');
        $clinic_type = $this->settings->where('key', 'clinic_type')->value('value');


        return view(
            'backend.dashboards.clinic.pages.reservations.today',
            compact('reservations', 'reservation_settings', 'today', 'clinic_type')
        );
    }


    public function todayReservationReport()
    {
        $this->authorizeCheck('view-reservations');

        $today = Carbon::today();
        $reservations = $this->reservation->where('date', $today)->get();
        $costSum = $reservations->sum('cost');

        $data = [
            'reservations' => $reservations,
            'cost_sum' => $costSum,
        ];

        $pdf = PDF::loadView('backend.dashboards.clinic.pages.reservations.today_reservation_report', $data);
        return $pdf->stream('Report.pdf');
    }

    // ...

    public function add($id)
    {
        $this->authorizeCheck('add-reservation');

        $settings = Settings::pluck('value', 'key');

        $patient = $this->patient->findOrFail($id);

        $currentDate = Carbon::now('Egypt')->addHour()->format('Y-m-d');

        if (Auth::user()->hasRole('clinic-admin')) {
            $doctors = Doctor::all();
        } else {
            $patientDoctorsIds = DB::table('patient_organization')
                ->where('patient_id', $id)->pluck('doctor_id')->toArray();

            $userDoctorsIds = DB::table('user_doctors')
                ->where('user_id', Auth::user()->id)
                ->pluck('doctor_id')->toArray();

            $doctorsIds = array_unique(array_merge($patientDoctorsIds, $userDoctorsIds));

            $doctors = Doctor::whereIn('id', $doctorsIds)->get();
        }

        return view(
            'backend.dashboards.clinic.pages.reservations.add',
            compact(
                'patient',
                'settings',
                'doctors'
            )
        );
    }

    public function store(StoreReservationRequest $request)
    {

        $this->authorizeCheck('add-reservation');

        try {
            $data = $request->validated();
            $data['month'] = substr($request->date, 5, 7 - 5);
            $data['acceptance'] = 'approved';
            $data['clinic_id'] = Auth::user()->organization->id;
            $data['doctor_id'] = $request->doctor_id;


            $data['cost'] = $data['cost'] ?? 0;

            if ($request->has('service_fee')) {
                $data['cost'] += array_sum($request->service_fee); // Sum all service fees
            }

            $reservation = $this->reservation->create($data);

            // Store service fees
            if ($request->has('service_fee_id')) {
                foreach ($request->service_fee_id as $index => $ServiceId) {
                    $fee = $request->service_fee[$index] ?? 0;
                    $notes = $request->service_fee_notes[$index] ?? null;

                    ModuleService::create([
                        'module_id' => $reservation->id,
                        'module_type' => Reservation::class,
                        'service_fee_id' => $ServiceId,
                        'fee' => $fee,
                        'notes' => $notes
                    ]);
                }
            }

            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachment) {
                    $reservation->addMedia($attachment)->toMediaCollection('reservation_attachments');
                }
            }

            if (Route::is('clinic.reservations.add')) {
                return redirect()->route('clinic.reservations.index')->with('toast_success', 'Reservation added successfully');
            } else {
                return redirect()->back()->with('toast_success', 'Reservation added successfully');
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->back()->with('toast_error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCheck('view-reservations');

        // get reservation based on id
        $reservation = $this->reservation->findOrFail($id);
        // get all chronic_diseases of reservation
        $chronic_diseases = $this->chronic_disease->where('id', $id)->get();
        // get all drugs of reservation
        $drugs = $this->drug->where('id', $id)->get();
        // get all rays of reservation
        $rays = $this->ray->where('id', $id)->get();

        return view('backend.dashboards.clinic.pages.reservations.show', compact('reservation', 'chronic_diseases', 'drugs', 'rays'));
    }


    public function edit($id)
    {
        $this->authorizeCheck('edit-reservation');

        $settings = $this->systemControl->pluck('value', 'key');

        $reservation = $this->reservation->with('Services')->findOrFail($id);

        // get reservation_number based on date
        $reservationResNum = $this->reservation->where('date', $reservation->date)->value('reservation_number');

        // number of reservation based on reservation_date
        $numberOfRes = $this->reservationNumber->where('reservation_date', $reservation->date)->value('num_of_reservations');

        $reservationSlot = $this->reservation->where('date', $reservation->date)->value('slot');

        $reservationSlots = ReservationSlots::where('date', $reservation->date)->first();

        $slots = $reservationSlots
            ? $this->getTimeSlot($reservationSlots->duration, $reservationSlots->start_time, $reservationSlots->end_time)
            : [];

        $reservationType = null;
        if ($reservation->reservation_number) {
            $reservationType = 'reservation_number';
        }
        if ($reservation->slot) {
            $reservationType = 'slot';
        }
        if (Auth::user()->hasRole('clinic-admin')) {
            $doctors = Doctor::all();
        } else {
            $patientDoctorsIds = DB::table('patient_organization')
                ->where('patient_id', $id)->pluck('doctor_id')->toArray();

            $userDoctorsIds = DB::table('user_doctors')
                ->where('user_id', Auth::user()->id)
                ->pluck('doctor_id')->toArray();

            $doctorsIds = array_unique(array_merge($patientDoctorsIds, $userDoctorsIds));

            $doctors = Doctor::whereIn('id', $doctorsIds)->get();
        }
        return view(
            'backend.dashboards.clinic.pages.reservations.edit',
            compact(
                'reservation',
                'numberOfRes',
                'reservationResNum',
                'settings',
                'slots',
                'reservationSlot',
                'reservationType',
                'doctors'
            )
        );
    }


    public function update(UpdateReservationRequest $request, $id)
    {
        $this->authorizeCheck('edit-reservation');

        try {
            $data = $request->validated();
            $reservation = $this->reservation->findOrFail($id);

            // Assign default values
            $data['doctor_id'] = $request->doctor_id;
            $data['cost'] = $data['cost'] ?? 0;

            if ($request->filled('service_fee')) {
                $data['cost'] += array_sum($request->service_fee ?? []);
            }

            // Update main reservation
            $reservation->fill($data)->save();

            // Handle service fees safely
            $ServiceIds = $request->input('service_fee_id', []);
            $Services = $request->input('service_fee_price', []);
            $serviceNotes = $request->input('service_fee_notes', []);

            if (!empty($ServiceIds)) {
                foreach ($ServiceIds as $index => $ServiceId) {
                    $fee = $Services[$index] ?? 0;
                    $note = $serviceNotes[$index] ?? null;


                    $moduleFee = $reservation->Services()
                        ->where('service_fee_id', $ServiceId)
                        ->where('module_id', $reservation->id)
                        ->where('module_type', Reservation::class)
                        ->first();


                    if ($moduleFee) {
                        $moduleFee->update([
                            'service_fee_id' => $ServiceId,
                            'fee' => $fee,
                            'notes' => $note,
                        ]);
                    } else {
                        $reservation->Services()->create([
                            'service_fee_id' => $ServiceId,
                            'fee' => $fee,
                            'notes' => $note,
                            'module_id' => $reservation->id,
                            'module_type' => Reservation::class,
                        ]);
                    }
                }
            }

            // Handle attachments
            $existingMediaIds = $request->input('existing_files', []);

            // Delete removed media
            $reservation->media()
                ->where('collection_name', 'reservation_attachments')
                ->when(!empty($existingMediaIds), function ($q) use ($existingMediaIds) {
                    $q->whereNotIn('id', $existingMediaIds);
                })
                ->each(fn($media) => $media->delete());

            // Add new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $reservation->addMedia($attachment)->toMediaCollection('reservation_attachments');
                }
            }

            return redirect()->route('clinic.reservations.index')
                ->with('toast_success', __('Reservation updated successfully'));

        } catch (\Exception $e) {
            return redirect()->back()->with('toast_error', $e->getMessage());
        }
    }



    public function destroy($id)
    {
        $this->authorizeCheck('delete-reservation');
        $reservation = $this->reservation->findOrFail($id);
        $reservation->delete();

        return redirect()->route('clinic.reservations.index');
    }

    public function trash()
    {
        $this->authorizeCheck('delete-reservation');
        $reservations = $this->reservation->onlyTrashed()->get();

        return view('backend.dashboards.clinic.pages.reservations.trash', compact('reservations'));
    }

    public function trashData()
    {

        $query = $this->reservation->onlyTrashed()->with('patient:id,name');


        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('patient_name', function ($reservation) {
                return $reservation->patient->name;
            })
            // ->addColumn('type', function ($reservation) {
            //     switch ($reservation->type) {
            //         case 'check':
            //             return trans('backend/reservations_trans.Check');
            //         case 'recheck':
            //             return trans('backend/reservations_trans.Recheck');
            //         case 'consultation':
            //             return trans('backend/reservations_trans.Consultation');
            //         default:
            //             return '-';
            //     }
            // })
            ->addColumn('payment', function ($reservation) {
                if ($reservation->payment == 'paid') {
                    return '<span class="badge badge-rounded badge-success p-2 m-2">' .
                        trans('backend/reservations_trans.Paid')
                        . '</span>';
                } elseif ($reservation->payment == 'not_paid') {
                    return '<span class="badge badge-rounded badge-danger p-2 m-2">' .
                        trans('backend/reservations_trans.Not_Paid')
                        . '</span>';
                }
            })
            ->addColumn('status', function ($reservation) {
                $status = '';
                $buttons = '';

                if ($reservation->status == 'waiting') {
                    $status = '<span class="badge badge-rounded badge-dark text-white p-2 ">' .
                        trans('backend/reservations_trans.Waiting') .
                        '</span>';
                } elseif ($reservation->status == 'entered') {
                    $status = '<span class="badge badge-rounded badge-dark p-2 ">' .
                        trans('backend/reservations_trans.Entered') .
                        '</span>';
                } elseif ($reservation->status == 'finished') {
                    $status = '<span class="badge badge-rounded badge-dark p-2 ">' .
                        trans('backend/reservations_trans.Finished') .
                        '</span>';
                } elseif ($reservation->status == 'cancelled') {
                    $status = '<span class="badge badge-rounded badge-dark p-2 ">' .
                        trans('backend/reservations_trans.Cancelled') .
                        '</span>';
                }

                // Buttons for status change
                $buttons = '<div class="res_control">' .
                    '<a href="' . route('clinic.reservations_options.reservation_status', [$reservation->id, 'waiting']) . '" class="btn btn-warning btn-sm text-white">' .
                    trans('backend/reservations_trans.Waiting') .
                    '</a>' .
                    '<a href="' . route('clinic.reservations_options.reservation_status', [$reservation->id, 'entered']) . '" class="btn btn-success btn-sm">' .
                    trans('backend/reservations_trans.Entered') .
                    '</a>' .
                    '<a href="' . route('clinic.reservations_options.reservation_status', [$reservation->id, 'finished']) . '" class="btn btn-info btn-sm">' .
                    trans('backend/reservations_trans.Finished') .
                    '</a>' .
                    '<a href="' . route('clinic.reservations_options.reservation_status', [$reservation->id, 'cancelled']) . '" class="btn btn-danger btn-sm">' .
                    trans('backend/reservations_trans.Cancelled') .
                    '</a>' .
                    '</div>';

                return $status . $buttons;
            })
            ->addColumn('ray_action', function ($reservation) {
                $reservation_settings = $this->systemControl->pluck('value', 'key');

                // Check if reservation settings allow showing ray
                if (isset($reservation_settings['show_ray']) && $reservation_settings['show_ray'] == 1) {
                    // Check if Ray exists for this reservation
                    $rayExists = Ray::where('id', $reservation->id)->first();

                    // Return the appropriate buttons based on Ray existence
                    if ($rayExists) {
                        return '<div class="res_control">
                                    <a href="' . route('clinic.rays.add', $reservation->id) . '" class="btn btn-success btn-sm">
                                        ' . trans('backend/reservations_trans.Add') . '
                                    </a>
                                    <a href="' . route('clinic.rays.show', $reservation->id) . '" class="btn btn-info btn-sm">
                                        ' . trans('backend/reservations_trans.Show') . '
                                    </a>
                                </div>';
                    } else {
                        return '<div class="res_control">
                                    <a href="' . route('clinic.rays.add', $reservation->id) . '" class="btn btn-dark btn-sm">
                                        ' . trans('backend/reservations_trans.Add') . '
                                    </a>
                                </div>';
                    }
                }

                // If show_ray is not set to 1, return an empty string or null
                return '';
            })

            ->addColumn('actions', function ($reservation) {
                // Generate restore action form
                $restoreForm = '<form action="' . route('clinic.reservations.restore', $reservation->id) . '" method="post" style="display:inline">
                                    ' . csrf_field() . '
                                    ' . method_field('put') . '
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa fa-edit"></i> إعادة
                                    </button>
                                </form>';

                // Generate force delete action form
                $forceDeleteForm = '<form action="' . route('clinic.reservations.forceDelete', $reservation->id) . '" method="post" style="display:inline">
                                        ' . csrf_field() . '
                                        ' . method_field('delete') . '
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa fa-trash"></i> حذف نهائى
                                        </button>
                                    </form>';

                // Combine both actions into one string
                return $restoreForm . $forceDeleteForm;
            })
            ->rawColumns(['payment', 'status', 'acceptance', 'actions'])
            ->make(true);
    }

    public function restore($id)
    {
        $this->authorizeCheck('restore-reservation');
        $reservation = $this->reservation->onlyTrashed()->findOrFail($id);
        $reservation->restore();

        return redirect()->route('clinic.reservations.index');
    }

    public function forceDelete($id)
    {
        $this->authorizeCheck('force-delete-reservation');
        $reservation = $this->reservation->onlyTrashed()->findOrFail($id);
        $reservation->forceDelete();

        return redirect()->route('clinic.reservations.index');
    }



    public function getResNumberOrSlotAdd(Request $request)
    {


        $date =  $request->date;

        // if system use reservation numbers not slots
        $reservation_reservation_number = Reservation::where('date', $date)
            ->where('clinic_id', Auth::user()->organization->id)
            ->where('doctor_id', $request->doctor_id)
            ->pluck('reservation_number')->map(function ($item) {
                return intval($item);
            })->toArray();
        $number_of_res = ReservationNumber::where('reservation_date', $date)
            ->where('doctor_id', $request->doctor_id)
            ->where('clinic_id', Auth::user()->organization->id)
            ->value('num_of_reservations');

        // if system use reservation slots not numbers
        $reservation_slots = Reservation::where('date', $date)
            ->where('clinic_id', Auth::user()->organization->id)
            ->where('doctor_id', $request->doctor_id)
            ->where('slot', '<>', 'null')->pluck('slot')->toArray();
        $number_of_slot = ReservationSlots::where('date', $date)
            ->where('doctor_id', $request->doctor_id)
            ->where('clinic_id', Auth::user()->organization->id)
            ->first();
        $slots = $number_of_slot ? $this->getTimeSlot($number_of_slot->duration, $number_of_slot->start_time, $number_of_slot->end_time) : [];


        // Create an associative array or Laravel collection with the values
        $data = [
            'reservationsCount' => $number_of_res,
            'todayReservationResNum' => $reservation_reservation_number,
            'slots' => $slots,
            'number_of_slot' => $number_of_slot,
            'today_reservation_slots' =>  $reservation_slots
        ];



        // Return the data as JSON response
        return response()->json($data);
    }

    public function getResNumberOrSlotEdit(Request $request)
    {

        $date =  $request->date;
        $res_id = $request->res_id;

        $reservation = Reservation::findOrFail($res_id);

        // if system use reservation numbers not slots
        $reservation_reservation_number = Reservation::where('date', $date)
            ->where('clinic_id', Auth::user()->organization->id)
            ->pluck('reservation_number')->map(function ($item) {
                return intval($item);
            })->toArray();
        $number_of_res = ReservationNumber::where('reservation_date', $date)
            ->where('clinic_id', Auth::user()->organization->id)
            ->value('num_of_reservations');


        // if system use reservation slots not numbers
        $reservation_slots = Reservation::where('date', $date)
            ->where('slot', '<>', 'null')->pluck('slot')->toArray();
        $number_of_slot = ReservationSlots::where('date', $date)
            ->where('clinic_id', Auth::user()->organization->id)
            ->first();
        $slots = $number_of_slot ? $this->getTimeSlot($number_of_slot->duration, $number_of_slot->start_time, $number_of_slot->end_time) : [];


        // Create an associative array or Laravel collection with the values
        $data = [
            'reservation' => $reservation,
            'reservationsCount' => $number_of_res,
            'todayReservationResNum' => $reservation_reservation_number,
            'slots' => $slots,
            'number_of_slot' => $number_of_slot,
            'today_reservation_slots' =>  $reservation_slots
        ];

        return response()->json($data);
    }

    /**
     * Get available reservation numbers or slots for the same day (for swap).
     * Uses clinic settings to determine number vs slot mode; excludes other reservations' taken values.
     * Current reservation's value is not in "taken" so it appears as available (swap to same = no-op).
     */
    /**
     * Get available (unused) reservation numbers or slots for the same day for swap.
     * Returns only options that are not used by any other reservation (current reservation's value is freed).
     */
    public function getAvailableSlotsNumbersForSwap(Request $request)
    {
        $this->authorizeCheck('view-reservations');
        $reservationId = $request->input('reservation_id');
        if (!$reservationId) {
            return response()->json(['error' => __('Reservation is required')], 422);
        }

        $reservation = $this->reservation->findOrFail($reservationId);
        $date = $reservation->date;
        $clinicId = Auth::user()->organization->id ?? $reservation->clinic_id;
        $doctorId = $reservation->doctor_id;

        // Determine mode: from clinic settings, else infer from reservation
        $settings = $this->systemControl->pluck('value', 'key')->merge($this->settings->pluck('value', 'key'));
        $settingType = $settings['reservation_settings'] ?? null;
        $useNumbers = false;
        $useSlots = false;
        if ($settingType === 'number' || $settingType === 'numbers') {
            $useNumbers = true;
        } elseif ($settingType === 'slots' || $settingType === 'slot') {
            $useSlots = true;
        } else {
            if ($reservation->reservation_number !== null && $reservation->reservation_number !== '') {
                $useNumbers = true;
            } elseif ($reservation->slot !== null && $reservation->slot !== '') {
                $useSlots = true;
            }
        }

        $type = null;
        $current = null;
        $available = [];

        if ($useNumbers) {
            $type = 'number';
            $current = $reservation->reservation_number !== null && $reservation->reservation_number !== ''
                ? (int) $reservation->reservation_number
                : null;

            $number_of_res = ReservationNumber::where('reservation_date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->value('num_of_reservations');

            if ($number_of_res === null) {
                $number_of_res = ReservationNumber::where('reservation_date', $date)
                    ->where('clinic_id', $clinicId)
                    ->value('num_of_reservations');
            }
            $max = (int) ($number_of_res ?: 0);

            // Used this day: other reservations only (exclude current so its number is available)
            $usedRaw = Reservation::where('date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('id', '!=', $reservationId)
                ->pluck('reservation_number');
            $used = $usedRaw->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($item) => (int) $item)->values()->toArray();

            for ($i = 1; $i <= $max; $i++) {
                if (!in_array($i, $used, true)) {
                    $available[] = ['value' => $i, 'label' => (string) $i];
                }
            }
        } elseif ($useSlots) {
            $type = 'slot';
            $current = ($reservation->slot !== null && $reservation->slot !== '') ? $reservation->slot : null;

            $number_of_slot = ReservationSlots::where('date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->first();
            if (!$number_of_slot) {
                $number_of_slot = ReservationSlots::where('date', $date)
                    ->where('clinic_id', $clinicId)
                    ->first();
            }
            $allSlots = $number_of_slot
                ? $this->getTimeSlot($number_of_slot->duration, $number_of_slot->start_time, $number_of_slot->end_time)
                : [];

            // Used this day: other reservations only; normalize to H:i
            $usedRaw = Reservation::where('date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('id', '!=', $reservationId)
                ->whereNotNull('slot')
                ->where('slot', '<>', '')
                ->pluck('slot');
            $used = $usedRaw->map(fn ($s) => $s ? substr($s, 0, 5) : null)->filter()->values()->toArray();

            foreach ($allSlots as $slot) {
                $slotTime = $slot['slot_start_time'] ?? null;
                if ($slotTime && !in_array($slotTime, $used, true)) {
                    $available[] = [
                        'value' => $slotTime,
                        'label' => $slotTime . ' - ' . ($slot['slot_end_time'] ?? ''),
                    ];
                }
            }
        }

        return response()->json([
            'type' => $type,
            'current' => $current,
            'available' => $available,
            'date' => $date,
        ]);
    }

    /**
     * Swap reservation's slot or reservation_number to an available value for the same day.
     */
    public function swapSlotOrNumber(Request $request, $id)
    {
        $this->authorizeCheck('edit-reservation');
        $reservation = $this->reservation->findOrFail($id);
        $newNumber = $request->input('reservation_number');
        $newSlot = $request->input('slot');

        $date = $reservation->date;
        $clinicId = Auth::user()->organization->id ?? $reservation->clinic_id;
        $doctorId = $reservation->doctor_id;

        if ($newNumber !== null && $newNumber !== '') {
            $taken = Reservation::where('date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('id', '!=', $id)
                ->pluck('reservation_number')->map(fn ($item) => (int) $item)->toArray();
            if (in_array((int) $newNumber, $taken, true)) {
                return response()->json(['success' => false, 'message' => __('This reservation number is already taken for this day.')], 422);
            }
            $max = (int) (ReservationNumber::where('reservation_date', $date)->where('clinic_id', $clinicId)->where('doctor_id', $doctorId)->value('num_of_reservations') ?: 0);
            if ((int) $newNumber < 1 || (int) $newNumber > $max) {
                return response()->json(['success' => false, 'message' => __('Invalid reservation number.')], 422);
            }
            $reservation->reservation_number = $newNumber;
            $reservation->slot = null;
            $reservation->save();
            return response()->json(['success' => true, 'message' => __('Reservation number updated successfully.')]);
        }

        if ($newSlot !== null && $newSlot !== '') {
            $taken = Reservation::where('date', $date)
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('id', '!=', $id)
                ->whereNotNull('slot')
                ->pluck('slot')->toArray();
            if (in_array($newSlot, $taken, true)) {
                return response()->json(['success' => false, 'message' => __('This slot is already taken for this day.')], 422);
            }
            $number_of_slot = ReservationSlots::where('date', $date)->where('clinic_id', $clinicId)->where('doctor_id', $doctorId)->first();
            $allSlots = $number_of_slot
                ? $this->getTimeSlot($number_of_slot->duration, $number_of_slot->start_time, $number_of_slot->end_time)
                : [];
            $validSlots = array_map(fn ($s) => $s['slot_start_time'] ?? null, $allSlots);
            if (!in_array($newSlot, $validSlots, true)) {
                return response()->json(['success' => false, 'message' => __('Invalid slot.')], 422);
            }
            $reservation->slot = $newSlot;
            $reservation->reservation_number = null;
            $reservation->save();
            return response()->json(['success' => true, 'message' => __('Reservation slot updated successfully.')]);
        }

        return response()->json(['success' => false, 'message' => __('Please choose a new reservation number or slot.')], 422);
    }

    public function editChronicDisease($id)
    {
        $this->authorizeCheck('edit-chronic-disease');

        $reservation = $this->reservation->findOrFail($id);
        $chronic_diseases = ChronicDisease::where('reservation_id', $id)->get();

        return view(
            'backend.dashboards.clinic.pages.reservations.editChronicDisease',
            compact('chronic_diseases', 'reservation')
        );
    }
    public function updateChronicDisease(Request $request, $reservation_id)
    {
        $this->authorizeCheck('edit-chronic-disease');

        try {

            $validated = $request->validate([
                'name.*' => 'nullable|string',
                'measure.*' => 'nullable|string',
                'date.*' => 'nullable|date',
                'notes.*' => 'nullable|string',
                'id.*' => 'nullable|integer|exists:chronic_diseases,id',
            ]);


            // Update or create chronic diseases
            foreach ($request->name as $index => $name) {
                $data = [
                    'patient_id' => $request->patient_id,
                    'reservation_id' => $request->reservation_id,
                    'name' => $name,
                    'measure' => $request->measure[$index] ?? null,
                    'date' => $request->date[$index] ?? null,
                    'notes' => $request->notes[$index] ?? null,
                    'clinic_id' => Auth::user()->organization->id,
                ];

                $id = $request->id[$index] ?? null;

                if ($id) {
                    ChronicDisease::where('id', $id)->update($data);
                } else {
                    ChronicDisease::create($data);
                }
            }

            return redirect()->back()->with('toast_success', __('backend/messages.updated_successfully'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('toast_error', __('backend/messages.something_went_wrong'));
        }
    }

    public function getDoctorServices($doctorId)
    {
        $doctor = Doctor::with('Services')->findOrFail($doctorId);

        return response()->json([
            'services' => $doctor->Services->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'service_name' => $fee->service_name,
                    'fee' => $fee->fee,
                    'notes' => $fee->notes,
                ];
            }),
        ]);
    }

    public function deleteService($id)
    {
        $this->authorizeCheck('delete-service-fee');

        $Service = ModuleService::findOrFail($id);
        $Service->delete();

        return response()->json([
            'success' => true,
            'message' => __('backend/messages.deleted_successfully'),
        ]);

    }
}
