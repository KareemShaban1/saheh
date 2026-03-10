<?php

namespace Modules\Clinic\Dashboard\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Traits\AuthorizeCheck;
use App\Http\Traits\TimeSlotsTrait;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\OnlineReservation;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Modules\Clinic\User\Models\User;
use Modules\Clinic\User\Models\UserDoctor;
use App\Traits\Scopes\DoctorScopeTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

class DashboardController extends Controller
{
    use TimeSlotsTrait, DoctorScopeTrait, AuthorizeCheck;
    //

    public function index()
    {


        $this->authorizeCheck('view-dashboard');

        $user = Auth::user();
        $clinicId = $user->organization->id ?? 'global';
        $cachePrefix = 'clinic_dashboard:' . $clinicId;
        $cacheTtl = 60; // seconds

        // get current date on egypt
        $current_date = Carbon::now('Egypt')->format('Y-m-d');

        // get count of all patients
        $patients_count = Cache::remember("{$cachePrefix}:patients_count", $cacheTtl, function () {
            return Patient::query()->clinic()->count();
        });

        $patients = Cache::remember("{$cachePrefix}:patients", $cacheTtl, function () {
            return Patient::query()->clinic()->get();
        });

        $user_count = Cache::remember("{$cachePrefix}:user_count", $cacheTtl, function () {
            return User::count();
        });

        $doctors_count = Cache::remember("{$cachePrefix}:doctors_count", $cacheTtl, function () {
            return Doctor::count();
        });

        // get all reservations
        $all_reservations_count = Cache::remember("{$cachePrefix}:all_reservations_count", $cacheTtl, function () {
            return Reservation::count();
        });

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $reservations_date = Reservation::whereBetween('date', [$startDate, $endDate])->pluck('date');

        // get all reservations
        $online_reservations_count = Cache::remember("{$cachePrefix}:online_reservations_count", $cacheTtl, function () {
            return OnlineReservation::count();
        });

        // get reservation where date = current_date
        $today_res_count = Cache::remember("{$cachePrefix}:today_res_count:{$current_date}", $cacheTtl, function () use ($current_date) {
            return Reservation::where('date', $current_date)->count();
        });

        // $medicines_count = Medicine::count();
        $medicines_count = 0;

        $today_payment = Cache::remember("{$cachePrefix}:today_payment:{$current_date}", $cacheTtl, function () use ($current_date) {
            return Reservation::where('date', $current_date)->sum('cost');
        });

        $current_month = Carbon::now('Egypt')->format('m');

        $month_payment = Cache::remember("{$cachePrefix}:month_payment:{$current_month}", $cacheTtl, function () use ($current_month) {
            return Reservation::where('month', $current_month)->where('payment', 'paid')->sum('cost');
        });

        $last_patients = Cache::remember("{$cachePrefix}:last_patients", $cacheTtl, function () {
            return Patient::clinic()->select('id', 'name', 'phone')->withCount('reservations')->latest()->take(5)->get();
        });

        $reservations = Cache::remember("{$cachePrefix}:recent_reservations", $cacheTtl, function () {
            return Reservation::with('patient:id,name')->latest()->take(5)->get();
        });

        $online_reservations = Cache::remember("{$cachePrefix}:recent_online_reservations", $cacheTtl, function () {
            return OnlineReservation::latest()->take(5)->get();
        });

        // Get all doctors for the clinic
        $userRole = $user->roles->first()?->name;

        $doctors = Doctor::query();

        if ($userRole !== 'clinic-admin') {
            $userDoctors = UserDoctor::where('user_id', $user->id)->pluck('doctor_id')->toArray();
            $doctors->whereIn('id', $userDoctors);
        }

        $doctors = $doctors->get();

        // Create weekly slots for each doctor
        $doctor_weekly_slots = [];

        foreach ($doctors as $doctor) {
            $weekly_slots = [];

            for ($i = 0; $i < 7; $i++) {
                $date = now()->subDays($i);
                $dateFormatted = $date->format('Y-m-d');
                $activeClass = $i == 0 ? 'active show' : '';

                // Number of Reservations with doctor filter
                $resQuery = ReservationNumber::where('reservation_date', $dateFormatted)
                    ->where('clinic_id', $user->organization->id ?? null)
                    ->where('doctor_id', $doctor->id);

                $number_of_reservations = $resQuery->value('num_of_reservations') ?? 0;

                // Slots with doctor filter
                $slotQuery = ReservationSlots::where('date', $dateFormatted)
                    ->where('clinic_id', $user->organization->id ?? null)
                    ->where('doctor_id', $doctor->id);


                $number_of_slot = $slotQuery->first();


                $slots = $number_of_slot
                    ? $this->getTimeSlot(
                        $number_of_slot->duration,
                        $number_of_slot->start_time,
                        $number_of_slot->end_time
                    )
                    : [];

                $weekly_slots[] = [
                    'date' => $dateFormatted,
                    'active_class' => $activeClass,
                    'number_of_reservations' => $number_of_reservations,
                    'slots' => $slots,
                ];
            }

            $doctor_weekly_slots[$doctor->id] = [
                'doctor' => $doctor,
                'weekly_slots' => $weekly_slots
            ];
        }



        $user_chart_options = [
            'chart_title' => 'Patients by months',
            'report_type' => 'group_by_date',
            'model' => 'App\Models\Shared\Patient',
            'group_by_field' => 'created_at',
            'chart_color' => '155, 0, 0',
            'chart_height' => '450px',
            'group_by_period' => 'day',
            'chart_type' => 'bar',
        ];
        $user_chart = new LaravelChart($user_chart_options);


        $reservation_chart_options = [
            'chart_title' => 'Res by months',
            'report_type' => 'group_by_date',
            'model' => 'Modules\Clinic\Reservation\Models\Reservation',
            'chart_color' => '0, 0,67',
            'chart_height' => '450px',
            'group_by_field' => 'created_at',
            'group_by_period' => 'day',
            'chart_type' => 'bar',
        ];
        $reservation_chart = new LaravelChart($reservation_chart_options);


        return view('backend.dashboards.clinic.pages.dashboard.index', compact(
            'user_chart',
            'reservation_chart',
            'patients_count',
            'all_reservations_count',
            'online_reservations_count',
            'today_res_count',
            'today_payment',
            'month_payment',
            'medicines_count',
            'user_count',
            'doctors_count',
            'last_patients',
            'patients',
            'reservations',
            'online_reservations',
            'startDate',
            'endDate',
            'doctor_weekly_slots',
            'doctors',
        ));
    }
}