<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\OnlineReservation;
use Modules\Clinic\User\Models\User;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Modules\Clinic\User\Models\UserDoctor;
use App\Traits\Scopes\DoctorScopeTrait;
use App\Http\Traits\TimeSlotsTrait;

/**
 * API Dashboard Controller for Clinic
 * Returns JSON data for Vue.js frontend
 */
class DashboardController extends Controller
{
    use TimeSlotsTrait, DoctorScopeTrait;

    /**
     * Get dashboard data
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $current_date = Carbon::now('Egypt')->format('Y-m-d');
            $current_month = Carbon::now('Egypt')->format('m');

            // Get statistics
            $stats = [
                'doctors_count' => Doctor::count(),
                'patients_count' => Patient::query()->clinic()->count(),
                'medicines_count' => 0, // Update if you have Medicine model
                'today_res_count' => Reservation::where('date', $current_date)->count(),
                'online_reservations_count' => OnlineReservation::count(),
                'all_reservations_count' => Reservation::count(),
                'today_payment' => Reservation::where('date', $current_date)->sum('cost'),
                'month_payment' => Reservation::where('month', $current_month)
                    ->where('payment', 'paid')
                    ->sum('cost'),
            ];

            // Get last patients
            $lastPatients = Patient::clinic()
                ->select('id', 'name', 'phone')
                ->withCount('reservations')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($patient) {
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'phone' => $patient->phone,
                        'reservations_count' => $patient->reservations_count,
                    ];
                });

            // Get recent reservations
            $reservations = Reservation::with('patient:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($reservation) {
                    return [
                        'id' => $reservation->id,
                        'patient' => [
                            'id' => $reservation->patient->id ?? null,
                            'name' => $reservation->patient->name ?? 'N/A',
                        ],
                        'payment' => $reservation->payment,
                        'res_status' => $reservation->res_status,
                        'acceptance' => $reservation->acceptance,
                        'date' => $reservation->date,
                    ];
                });

            // Get online reservations
            $onlineReservations = OnlineReservation::latest()
                ->take(5)
                ->get()
                ->map(function ($onlineReservation) {
                    return [
                        'id' => $onlineReservation->id,
                        'patient' => [
                            'id' => $onlineReservation->patient->id ?? null,
                            'name' => $onlineReservation->patient->name ?? 'N/A',
                        ],
                        'topic' => $onlineReservation->topic,
                        'start_at' => $onlineReservation->start_at,
                        'duration' => $onlineReservation->duration,
                    ];
                });

            // Get doctors
            $user = Auth::user();
            $userRole = $user->roles->first()?->name;

            $doctorsQuery = Doctor::query();
            if ($userRole !== 'clinic-admin') {
                $userDoctors = UserDoctor::where('user_id', $user->id)->pluck('doctor_id')->toArray();
                $doctorsQuery->whereIn('id', $userDoctors);
            }

            $doctors = $doctorsQuery->get()->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                ];
            });

            // Get doctor weekly slots
            $doctorWeeklySlots = [];
            foreach ($doctorsQuery->get() as $doctor) {
                $weekly_slots = [];
                for ($i = 0; $i < 7; $i++) {
                    $date = now()->subDays($i);
                    $dateFormatted = $date->format('Y-m-d');

                    $resQuery = ReservationNumber::where('reservation_date', $dateFormatted)
                        ->where('clinic_id', Auth::user()->organization->id)
                        ->where('doctor_id', $doctor->id);

                    $number_of_reservations = $resQuery->value('num_of_reservations') ?? 0;

                    $slotQuery = ReservationSlots::where('date', $dateFormatted)
                        ->where('clinic_id', Auth::user()->organization->id)
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
                        'active_class' => $i == 0 ? 'active show' : '',
                        'number_of_reservations' => $number_of_reservations,
                        'slots' => $slots,
                    ];
                }

                $doctorWeeklySlots[$doctor->id] = [
                    'doctor' => [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                    ],
                    'weekly_slots' => $weekly_slots
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'last_patients' => $lastPatients,
                    'reservations' => $reservations,
                    'online_reservations' => $onlineReservations,
                    'doctors' => $doctors,
                    'doctor_weekly_slots' => $doctorWeeklySlots,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}







