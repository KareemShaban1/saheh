<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Http\Traits\AuthorizeCheck;
use App\Models\MedicalAnalysis;
use App\Models\OnlineReservation;
use App\Models\Ray;
use Modules\Clinic\Reservation\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;


class FeeController extends Controller
{
    use AuthorizeCheck;


    public function index()
    {
        $this->authorizeCheck('view-fees');
        // get current date on egypt
        $current_date = Carbon::now('Egypt')->format('Y-m-d');
        // get all reservations
        $reservations = Reservation::all();

        return view('backend.dashboards.medicalLaboratory.pages.fees.index', compact('current_date', 'reservations'));
    }

    public function data(Request $request)
    {
        $filter = $request->input('filter', 'today');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
    
        $query = MedicalAnalysis::paid();
    
        switch ($filter) {
            case 'today':
                $query->whereDate('date', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('date', Carbon::now()->month)
                      ->whereYear('date', Carbon::now()->year);
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
                break;
        }
    
        // Clone the query to get total before applying pagination
        $totalCost = $query->sum('cost');
    
        return DataTables::of($query)
            ->addColumn('patient_name', fn($r) => $r->patient->name ?? '-')
            ->addColumn('reservation_number', fn($r) => $r->reservation_number)
            ->addColumn('payment', fn($r) => $r->payment === 'paid' ? trans('backend/fees_trans.Paid') : trans('backend/fees_trans.Not_Paid'))
            ->addColumn('cost', fn($r) => $r->cost)
            ->addColumn('date', fn($r) => $r->date)
            ->addColumn('total', fn($r) => $r->total)
            ->with(['total_cost' => $totalCost]) 
            ->make(true);
    }
    
}
