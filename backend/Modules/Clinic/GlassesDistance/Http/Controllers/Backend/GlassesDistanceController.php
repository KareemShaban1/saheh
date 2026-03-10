<?php

namespace Modules\Clinic\GlassesDistance\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\GlassesDistance\Http\Requests\Backend\StoreGlassesDistanceRequest;
use Modules\Clinic\GlassesDistance\Http\Requests\Backend\UpdateGlassesDistanceRequest;
use App\Http\Traits\AuthorizeCheck;
use Illuminate\Http\Request;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Settings;
use Modules\Clinic\GlassesDistance\Models\GlassesDistance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PDF;

class GlassesDistanceController extends Controller
{
    use AuthorizeCheck;

    public function index(GlassesDistance $glassesDistance)
    {
        $this->authorizeCheck('view-glasses-distances');
        $glasses_distances = $glassesDistance->all();
        return view('backend.dashboards.clinic.pages.glasses_distance.index', compact('glasses_distances'));
    }

    public function data()
    {
        $glasses_distances = GlassesDistance::all();
        return datatables()->of($glasses_distances)
            ->addColumn('doctor', function ($glasses_distance) {
                return $glasses_distance->reservation->doctor->user->name ?? 'N/A';
            })
            ->addColumn('reservation_date', function ($glasses_distance) {
                return $glasses_distance->reservation->date ?? 'N/A';
            })
            ->addColumn('action', function ($glasses_distance) {
                $actions = [];

                $actions[] = '<a class="dropdown-item" href="' . route('clinic.glasses_distance.edit', $glasses_distance->id) . '">
                        <i class="fas fa-edit mr-1"></i> ' . trans('backend/reservations_trans.Edit') . '</a>';
                    $actions[] = '<a class="dropdown-item" href="' . route('clinic.glasses_distance.glasses_distance_pdf', $glasses_distance->id) . '">
                        <i class="fas fa-eye mr-1"></i> ' . trans('backend/reservations_trans.Show') . '</a>';

                return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cogs"></i> ' . trans('backend/reservations_trans.Control') . '
                        </button>
                        <div class="dropdown-menu">
                            ' . implode('', $actions) . '
                        </div>
                    </div>';
            })
            ->addColumn('patient', function ($glasses_distance) {
                return $glasses_distance->patient->name ?? 'N/A';
            })

            ->make(true);
    }

    public function add(Request $request, Reservation $reservation, $id)
    {
        $this->authorizeCheck('add-glasses-distance');

        // get reservation based on reservation_id
        $reservation = $reservation->findOrFail($id);

        return view('backend.dashboards.clinic.pages.glasses_distance.add', compact('reservation'));
    }

    public function store(StoreGlassesDistanceRequest $request, GlassesDistance $glassesDistance)
    {
        $this->authorizeCheck('add-glasses-distance');

        $request->validated();

        try {

            $data = $request->all();
            $data['clinic_id'] = Auth::user()->organization->id;

            $glassesDistance->create($data);

            return redirect()->route('clinic.glasses_distance.index');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('toast_error', $e->getMessage());
        }
    }

    public function edit(GlassesDistance $glassesDistance, $id)
    {
        $this->authorizeCheck('edit-glasses-distance');

        $glasses_distance = $glassesDistance->findOrFail($id);
        return view('backend.dashboards.clinic.pages.glasses_distance.edit', compact('glasses_distance'));
    }

    public function update($id, UpdateGlassesDistanceRequest $request, GlassesDistance $glassesDistance)
    {
        $this->authorizeCheck('edit-glasses-distance');

        try {
            $glasses_distance = $glassesDistance->findOrFail($id);

            $data = $request->all();

            $glasses_distance->update($data);

            return redirect()->route('clinic.glasses_distance.index');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {

            return redirect()->back()->with('toast_error', $e->getMessage());
        }
    }

    public function glasses_distance_pdf($id, GlassesDistance $glassesDistance, Reservation $reservation)
    {
        $this->authorizeCheck('view-glasses-distances');

        $glasses_distances = $glassesDistance->where('reservation_id', $id)->first();

        $reservation = $reservation->findOrFail($id);

        // Rest of the method implementation
        $collection = Settings::all();
        $setting['setting'] = $collection->flatMap(function ($collection) {
            return [$collection->key => $collection->value];
        });

        $data = [];
        $data['settings'] = $setting['setting'];
        $data['glasses_distance'] = $glasses_distances;
        $data['reservation'] = $reservation;


        $pdf = PDF::loadView('backend.dashboards.clinic.pages.glasses_distance.glasses_distance_pdf', $data);

        return $pdf->stream('Glasses' . '.pdf');
    }
}
