<?php

namespace Modules\Clinic\ReservationNumber\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\ReservationNumber\Http\Requests\Backend\StoreReservationNumberRequest;
use Modules\Clinic\ReservationNumber\Http\Requests\Backend\UpdateReservationNumberRequest;
use App\Http\Traits\SlotsNumbersCheck;
use App\Http\Traits\AuthorizeCheck;
use Modules\Clinic\Doctor\Models\Doctor;
use Illuminate\Http\Request;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ReservationNumberController extends Controller
{
    use SlotsNumbersCheck, AuthorizeCheck;

    public function index()
    {
        $this->authorizeCheck('view-reservation-number');

        $doctors = Doctor::all();

        return view('backend.dashboards.clinic.pages.num_of_reservations.index', compact('doctors'));
    }

    public function data()
    {
        // Scope to current clinic and eager-load doctor user (avoids null due to clinic scoping + N+1)
        $clinicId = Auth::user()->organization->id;
        $num_of_reservations = ReservationNumber::query()
            ->where('clinic_id', $clinicId)
            ->with(['doctor.user'])
            ->get();

        return DataTables::of($num_of_reservations)
            ->addColumn('action', function ($number) {
                $editUrl = route('clinic.reservation_numbers.edit', $number->id);
                $deleteUrl = route('clinic.reservation_numbers.destroy', $number->id);

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
            ->addColumn('doctor', function ($number) {
                return $number->doctor?->user?->name;
            })
            ->rawColumns(['action']) // Ensure the HTML in the action column is not escaped
            ->make(true);
    }


    public function add()
    {
        $this->authorizeCheck('add-reservation-number');

        $doctors = Doctor::all();

        return view(
            'backend.dashboards.clinic.pages.num_of_reservations.add',
            compact('doctors')
        );
    }

    public function store(StoreReservationNumberRequest $request)
    {
        $this->authorizeCheck('add-reservation-number');

        $validatedData = $request->validated();

        // Check if slots already set for this day
        if ($this->reservationNumberCheck($validatedData)) {
            $message = 'تمت إضافة عدد الحجوزات لهذا اليوم مسبقًا.';

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409); // Conflict status code
            }

            return redirect()->back()->with('toast_error', $message);
        }

        // Set clinic_id from authenticated user
        $validatedData['clinic_id'] = Auth::user()->organization->id;

        // Create reservation record
        ReservationNumber::create($validatedData);

        $message = 'تمت إضافة عدد الحجوزات لهذا اليوم بنجاح.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('clinic.patients.index')
            ->with('toast_success', $message);
    }


    public function edit(Request $request, $id)
    {
        $this->authorizeCheck('edit-reservation-number');

        $num_of_res =  ReservationNumber::findOrFail($id);

        $doctors = Doctor::all();

        if ($request->ajax()) {

            return response()->json([
                'id' => $num_of_res->id,
                'num_of_reservations' => $num_of_res->num_of_reservations,
                'doctor_id' => $num_of_res->doctor_id,
                'reservation_date' => $num_of_res->reservation_date

            ]);
        }

        return view(
            'backend.dashboards.clinic.pages.num_of_reservations.edit',
            compact('num_of_res', 'doctors')
        );
    }

    public function update(UpdateReservationNumberRequest $request, $id)
    {
        $this->authorizeCheck('edit-reservation-number');

        $validatedData = $request->validated();

        try {
            $num_of_reservations = ReservationNumber::findOrFail($id);
            $num_of_reservations->update($validatedData);
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث عدد الحجوزات لهذا اليوم بنجاح',
                ]);
            }
            return redirect()->route('clinic.reservation_numbers.index');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong',
                ]);
            }
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function destroy($id)
    {
        $this->authorizeCheck('delete-reservation-number');

        try {
            $num_of_reservations = ReservationNumber::findOrFail($id);
            $num_of_reservations->delete();
            return redirect()->route('clinic.reservation_numbers.index');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
