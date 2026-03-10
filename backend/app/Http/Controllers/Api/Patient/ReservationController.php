<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Http\Resources\Reservation\ReservationCollection;
use App\Http\Resources\Reservation\ReservationResource;
use App\Models\Clinic;
use App\Models\ModuleService;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use App\Models\Scopes\OrganizationScope;
use App\Models\Settings;
use Modules\Clinic\User\Models\User;
use App\Notifications\MakeAppointmentNotification;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use MacsiDigital\Zoom\Setting;

class ReservationController extends Controller
{
    //

    use ApiHelperTrait;

    public function index(Request $request)
    {

        $query = Reservation::with('services','doctor.user','clinic')->patient();

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });


        $query->when($request->filled('acceptance'), function ($q) use ($request) {
            $q->where('acceptance', $request->acceptance);
        });
        $reservations = $query->get();

        $reservationCollection = (new ReservationCollection($reservations))->withFullData(!($request->full_data == 'false'));

        return $this->returnJSON($reservationCollection, 'All Reservations', true);
    }

    public function show(Request $request, $id)
    {
        $reservation = Reservation::with('Services')->patient()->findOrFail($id);

        $reservationResource = (new ReservationResource($reservation))->withFullData(!($request->full_data == 'false'));

        return $this->returnJSON($reservationResource, 'Reservation', true);
    }

    public function store(StoreReservationRequest $request)
    {
        try {
            // $data = $request->validated(); // validated() returns only validated data

            $data = $request->all();

            // Load settings without global scope
            $settings = Settings::withoutGlobalScope(OrganizationScope::class)
                ->where('organization_type', Clinic::class)
                ->where('organization_id', $data['clinic_id'])
                ->where('type', 'clinic_reservations_settings')
                ->pluck('value', 'key')
                ->toArray();

            $reservationSettingType = $settings['reservation_settings'] ?? null;

            if (!$reservationSettingType) {
                return $this->returnJSON(null, 'Reservation setting type not found.', false);
            }

            // ✅ Validate availability of slot or number based on setting
            $hasAvailableSlot = match ($reservationSettingType) {
                'number' => ReservationNumber::where('clinic_id', $data['clinic_id'])
                    ->where('doctor_id', $data['doctor_id'])
                    ->where('reservation_date', $data['date'])
                    ->exists(),
                default => ReservationSlots::where('clinic_id', $data['clinic_id'])
                    ->where('doctor_id', $data['doctor_id'])
                    ->where('date', $data['date'])
                    ->exists()
            };

            if (!$hasAvailableSlot) {
                return $this->returnJSON(null, 'No reservation ' . $reservationSettingType . ' available for this date.', false);
            }

            // ✅ Ensure selected reservation value isn't already reserved for this clinic/doctor/date
            // Previous logic used OR without proper grouping/scope, causing false positives.
            $selectedReservationValue = match ($reservationSettingType) {
                'number' => trim((string) ($data['reservation_number'] ?? '')),
                default => trim((string) ($data['slot'] ?? $data['time'] ?? '')),
            };

            $reservationExists = Reservation::query()
                ->where('clinic_id', $data['clinic_id'])
                ->where('doctor_id', $data['doctor_id'])
                ->whereDate('date', $data['date'])
                ->when($reservationSettingType === 'number', function ($q) use ($selectedReservationValue) {
                    if ($selectedReservationValue === '') {
                        // If number mode but no number provided, fail validation path as unavailable.
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $q->where('reservation_number', $selectedReservationValue);
                })
                ->when($reservationSettingType !== 'number', function ($q) use ($selectedReservationValue) {
                    if ($selectedReservationValue === '') {
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $q->where('slot', $selectedReservationValue);
                })
                ->exists();

            if ($reservationExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation already exists for this date'
                ], 422);
            }

            // ✅ Assign defaults and extras
            $data['month'] = date('m', strtotime($data['date']));
            $data['acceptance'] = 'pending';
            $data['status'] = 'waiting';
            $data['payment'] = 'not_paid';
            $data['cost'] = $data['cost'] ?? 0;
            $data['patient_id'] = auth()->user()->id;

            // ✅ Calculate cost from service fees if any
            if (!empty($request->service_fee)) {
                $data['cost'] += array_sum($request->service_fee);
            }

            // ✅ Create reservation
            $reservation = Reservation::create($data);

            // ✅ Store service fees
            if ($request->filled('service_fee_id')) {
                foreach ($request->service_fee_id as $index => $ServiceId) {
                    ModuleService::create([
                        'module_id'      => $reservation->id,
                        'module_type'    => Reservation::class,
                        'service_fee_id' => $ServiceId,
                        'fee'            => $request->service_fee[$index] ?? 0,
                        'notes'          => $request->service_fee_notes[$index] ?? null,
                    ]);
                }
            }

            // Send notifications to all clinic dashboard users in this clinic.
            $clinicUsers = User::where('organization_id', $data['clinic_id'])
                ->where('organization_type', Clinic::class)
                ->get();

            foreach ($clinicUsers as $clinicUser) {
                $clinicUser->notify(new MakeAppointmentNotification($reservation));
            }

            // ✅ Prepare resource
            $reservationResource = (new ReservationResource($reservation))
                ->withFullData(!($request->full_data === 'false'));



            return $this->returnJSON($reservationResource, 'Reservation created successfully.', true);
        } catch (\Throwable $e) {
            return $this->returnJSON(null, 'Error: ' . $e->getMessage(), false);
        }
    }

    public function changeReservationStatus($id, $status)
    {

        $reservation = Reservation::findOrFail($id);


        $reservation->update([
            'status' => $status
        ]);

        $reservationResource = (new ReservationResource($reservation))->withFullData(true);

        return $this->returnJSON($reservationResource, 'Reservation', true);
    }
}
