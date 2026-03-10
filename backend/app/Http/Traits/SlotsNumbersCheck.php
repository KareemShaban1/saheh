<?php

namespace App\Http\Traits;

use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Illuminate\Support\Facades\Auth;

trait SlotsNumbersCheck
{
    public function slotsCheck($reservation_date)
    {

        $slotsCount = ReservationSlots::where('date', $reservation_date)->count();

        return $slotsCount > 0;

    }
    public function reservationNumberCheck($request)
    {
        $resNumberCount = ReservationNumber::
        where('clinic_id', Auth::user()->organization_id)
        ->where('doctor_id',$request['doctor_id'])
        ->where('reservation_date', $request['reservation_date'])->count();


        return $resNumberCount > 0 ;
    }
}