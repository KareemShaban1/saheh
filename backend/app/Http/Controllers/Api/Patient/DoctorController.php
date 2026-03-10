<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\DoctorCollection;
use App\Http\Resources\Doctor\DoctorResource;
use App\Models\Clinic;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use App\Models\Scopes\OrganizationScope;
use App\Models\Settings;
use App\Traits\ApiHelperTrait;
use Illuminate\Http\Request;
use Modules\Clinic\Doctor\Models\Doctor;

class DoctorController extends Controller
{
    //
    use ApiHelperTrait;

    public function index(Request $request)
    {


        $query = Doctor::with('ServicesWithoutScope');

        if ($request->clinic_id) {
            $query = $query->where('clinic_id', $request->clinic_id);
        }


        $doctors = $query->get();

        $doctorsCollection = (new DoctorCollection($doctors))
            ->withFullData(! ($request->full_data == 'false'));

        return $this->returnJSON($doctorsCollection, 'All Doctors', true);
    }

    public function show(Request $request, $id)
    {
        $doctor = Doctor::with('reviews')->findOrFail($id);
        $doctorResource = (new DoctorResource($doctor))
            ->withFullData(! ($request->full_data == 'false'));

        return $this->returnJSON($doctorResource, 'Doctor', true);
    }

    public function doctorNumberOfReservations(Request $request)
    {
        $doctor = Doctor::findOrFail($request->doctor_id);

        return $this->handleNumberOfReservations($doctor, $request->reservation_date);
    }

    public function doctorSlots(Request $request)
    {
        $doctor = Doctor::findOrFail($request->doctor_id);

        return $this->handleSlotReservations($doctor, $request->reservation_date);
    }

    public function doctorReservationSlotsNumbers(Request $request)
    {
        // ✅ Validate input
        $data = $request->validate([
            'clinic_id' => 'required|integer|exists:clinics,id',
            'doctor_id' => 'required|integer|exists:doctors,id',
            'reservation_date' => 'required|date',
        ]);

        // ✅ Fetch doctor
        $doctor = Doctor::findOrFail($data['doctor_id']);

        // ✅ Fetch reservation setting type (slots or numbers)
        $settingType = Settings::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_type', Clinic::class)
            ->where('organization_id', $data['clinic_id'])
            ->where('type', 'clinic_reservations_settings')
            ->pluck('value', 'key')['reservation_settings'] ?? null;

        if (! $settingType) {
            return $this->returnJSON(null, 'Reservation setting type not found.', false);
        }

        // ✅ Delegate based on setting type
        return $settingType === 'number'
            ? $this->handleNumberOfReservations($doctor, $data['reservation_date'])
            : $this->handleSlotReservations($doctor, $data['reservation_date']);
    }

    private function handleNumberOfReservations(Doctor $doctor, string $date)
    {

        $record = ReservationNumber::where('doctor_id', $doctor->id)
            ->where('reservation_date', $date)
            ->first();

        $reserved = Reservation::where('doctor_id', $doctor->id)
            ->where('date', $date)
            ->pluck('reservation_number')
            ->toArray();

        $all = $record ? range(1, $record->num_of_reservations) : [];
        $available = array_values(array_diff($all, $reserved));

        return $this->returnJSON([
            'type' => 'numbers',
            'record' => $record,
            'reservation_numbers' => $all,
            'reserved_numbers' => $reserved,
            'available_numbers' => $available,
        ], 'Doctor Number of Reservations', true);
    }

    private function handleSlotReservations(Doctor $doctor, string $date)
    {
        $record = ReservationSlots::where('doctor_id', $doctor->id)
            ->where('date', $date)
            ->first();

        if (! $record) {
            return $this->returnJSON([
                'record' => null,
                'slots' => [],
                'reserved_slots' => [],
                'available_slots' => [],
            ], 'Doctor slots not found for this date.', true);
        }

        $start = strtotime($record->start_time);
        $end = strtotime($record->end_time);
        $duration = (int) $record->duration;

        $all = [];
        while ($start + ($duration * 60) <= $end) {
            $all[] = date('H:i', $start);
            $start += $duration * 60;
        }

        $reserved = Reservation::where('doctor_id', $doctor->id)
            ->where('date', $date)
            ->pluck('slot')
            ->toArray();

        $available = array_values(array_diff($all, $reserved));

        return $this->returnJSON([
            'type' => 'slots',
            'record' => $record,
            'slots' => $all,
            'reserved_slots' => $reserved,
            'available_slots' => $available,
        ], 'Doctor Slots', true);
    }

    //get service fees of doctor based on doctor id
    public function getServices($doctor_id)
    {
        $doctor = Doctor::findOrFail($doctor_id);
        return $this->returnJSON($doctor->ServicesWithoutScope, 'Service Fees', true);
    }
}