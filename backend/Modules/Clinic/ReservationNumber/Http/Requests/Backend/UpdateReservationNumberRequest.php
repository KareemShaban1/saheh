<?php

namespace Modules\Clinic\ReservationNumber\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateReservationNumberRequest extends FormRequest
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
            'reservation_date' => 'required',
            'doctor_id' => 'required|exists:doctors,id',
            'num_of_reservations' => Rule::unique('reservation_numbers')
            ->ignore($request->id)
            ->where(function ($query) use ($request) {
                return $query->where('reservation_date', $request->reservation_date);
            }),
        ];
    }
}