<?php


namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\Medicine\Models\Medicine;
use App\Models\OnlineReservation;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Scopes\ClinicScope;

class DashboardController extends Controller
{
    public function index()
    {
        $doctors_count = Doctor::withoutGlobalScope(ClinicScope::class)->count();
        $patients_count = Patient::count();
        $medicines_count = Medicine::count();
        $online_reservations_count = OnlineReservation::count();
        $all_reservations_count = Reservation::count();
        return view(
            'backend.dashboards.admin.pages.dashboard.index',
            compact('doctors_count','patients_count','medicines_count'
            ,'online_reservations_count' , 'all_reservations_count')
        );
    }
}
