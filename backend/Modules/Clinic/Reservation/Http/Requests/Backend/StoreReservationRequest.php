<?php

namespace Modules\Clinic\Reservation\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [

            'patient_id' => 'required|exists:patients,id',
            // 'type'=>'required',
            'reservation_number' => 'nullable',
            'payment' => 'required',
            'date' => 'required',
            'service_fee_id*' => 'required',
            'acceptance' => 'required',

        ];
    }

    public function messages()
    {
        return [
            'reservation_number.required' => 'برجاء أدخال رقم الكشف',
            // 'type.required'=>'برجاء أدخال نوع الكشف',
            'payment.required' => 'برجاء أدخال حالة الدفع',
            'cost.required' => 'برجاء أدخال المبلغ',
            'cost.max' => 'يجب أن لا يزيد المبلغ عن أريع خانات',
            'cost.regex' => 'يجب أن يكون المبلغ أرقام',
            'acceptance.required' => 'برجاء أدخال حالة الكشف',
            'patient_id.required' => 'برجاء أدخال المريض',
            'patient_id.exists' => 'المريض غير موجود',
            'date.date' => 'يجب أن يكون تاريخ الكشف يوم من الأسبوع',
            'date.date_format' => 'يجب أن يكون تاريخ الكشف يوم من الأسبوع',
        ];
    }
}
