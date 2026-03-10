<?php

namespace Modules\Clinic\ReservationSlot\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\ReservationSlot\Http\Requests\Backend\StoreReservationSlotRequest;
use Modules\Clinic\ReservationSlot\Http\Requests\Backend\UpdateReservationSlotRequest;
use App\Http\Traits\SlotsNumbersCheck;
use App\Http\Traits\AuthorizeCheck;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ReservationSlotsController extends Controller
{
    //
    use SlotsNumbersCheck, AuthorizeCheck;

    public function index()
    {
        $this->authorizeCheck('view-reservation-slots');

        $reservation_slots = ReservationSlots::all();

        $doctors = Doctor::all();

        return view(
            'backend.dashboards.clinic.pages.reservation_slots.index',
            compact('reservation_slots', 'doctors')
        );
    }

    public function data()
    {
        $reservationSlots = ReservationSlots::with(['doctor' => function ($q) {
            $q->withoutGlobalScopes()->with('user');
        }])->get();

        return DataTables::of($reservationSlots)
            ->addColumn('action', function ($number) {
                $editUrl = route('clinic.reservation_slots.edit', $number->id);
                $deleteUrl = route('clinic.reservation_slots.destroy', $number->id);

                return '
                    <button class="btn btn-warning btn-sm edit-btn" data-id="' . $number->id . '">
                        <i class="fa fa-edit"></i>
                    </button>
                    <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this item?\')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                ';
            })
            ->addColumn('doctor', function ($row) {
                return $row->doctor && $row->doctor->user
                    ? $row->doctor->user->name
                    : '—';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function add()
    {
        $this->authorizeCheck('add-reservation-slot');

        $doctors = Doctor::all();


        return view(
            'backend.dashboards.clinic.pages.reservation_slots.add',
            compact('doctors')
        );
    }

    public function store(StoreReservationSlotRequest $request)
    {
        $this->authorizeCheck('add-reservation-slot');

        $validatedData = $request->validated();


        $resNumber_check = $this->reservationNumberCheck($request);



        if ($resNumber_check) {

            if ($request->ajax()) {

                return response()->json([
                    'status' => 'toast_error',
                    'message' => 'تم أضافة reservation number لهذا اليوم'
                ]);
            }

            return redirect()->back()->with('toast_error', 'تم أضافة reservation number لهذا اليوم');
        } else {

            $validatedData['clinic_id'] = Auth::user()->organization_id;
            ReservationSlots::create($validatedData);


            if ($request->ajax()) {

                return response()->json([
                    'status' => 'toast_success',
                    'message' => 'تم أضافة عدد للحجوزات لهذا اليوم بنجاح'
                ]);
            }
            return redirect()->route('clinic.reservation_slots.index')->with('toast_success', 'تم أضافة عدد للحجوزات لهذا اليوم بنجاج');
        }
    }

    public function edit(Request $request , $id)
    {
        $this->authorizeCheck('edit-reservation-slot');

        $reservation_slot =  ReservationSlots::findOrFail($id);
        $doctors = Doctor::all();

        if($request->ajax()){

            return response()->json([
                'id' => $reservation_slot->id,
                'date' => $reservation_slot->date,
                'doctor_id' => $reservation_slot->doctor_id,
                'clinic_id' => $reservation_slot->clinic_id,
                'start_time' => $reservation_slot->start_time,
                'end_time' => $reservation_slot->end_time,
                'duration' => $reservation_slot->duration,
                'total_reservations' => $reservation_slot->total_reservations
            ]);


        }


        return view(
            'backend.dashboards.clinic.pages.reservation_slots.edit',
            compact('reservation_slot', 'doctors')
        );
    }

    public function update(UpdateReservationSlotRequest $request, $id)
    {
        $this->authorizeCheck('edit-reservation-slot');

        try {

            $reservation_slots = ReservationSlots::findOrFail($id);
            $validatedData = $request->validated();
            $reservation_slots->update($validatedData);

            if($request->ajax()){
                return response()->json([
                    'status' => 'toast_success',
                    'message' => 'تم تحديث عدد للحجوزات لهذا اليوم بنجاح'
                ]);
            }

            return redirect()->route('clinic.reservation_slots.index');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function destroy($id)
    {
        $this->authorizeCheck('delete-reservation-slot');

        try {
            $reservation_slots = ReservationSlots::findOrFail($id);
            $reservation_slots->delete();
            return redirect()->route('clinic.reservation_slots.index');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
