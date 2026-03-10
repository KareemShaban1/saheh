<?php

namespace Modules\Clinic\ReservationSlot\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdateReservationSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        return [
            //
            'date' => 'required|unique:reservation_slots,date,' . $request->id,
            'doctor_id' => 'required|exists:doctors,id',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required',
            'total_reservations' => 'required',
        ];
    }
}