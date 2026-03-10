<?php

namespace Modules\Clinic\Reservation\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            
                // 'patient_id' => 'required|exists:patients,id',
                'reservation_number'=>'required',          
                'payment'=>'required|in:paid,not_paid',
                'date' => 'required',
                'status'=>'required|in:waiting,entered,finished,cancelled',
                'acceptance'=>'required|in:approved,not_approved',
                'first_diagnosis'=>'required',
                'final_diagnosis'=>'required',
                'doctor_id'=>'required|exists:doctors,id',
                'service_fee_id'=>'required|array',
                'service_fee_id.*'=>'required|exists:services,id',            
                'service_fee_price'=>'required|array',
                'service_fee_price.*'=>'required|numeric',
    
        ];
    }

    public function messages(){
        return[
                // 'reservation_number.required'=>'برجاء أدخال رقم الكشف',
                'type.required'=>'برجاء أدخال نوع الكشف',
                'payment.required'=>'برجاء أدخال حالة الدفع',
                // 'cost.required'=>'برجاء أدخال المبلغ',
                // 'cost.max'=>'يجب أن لا يزيد المبلغ عن أريع خانات',
                // 'cost.regex'=>'يجب أن يكون المبلغ أرقام',
                'date.required'=>'برجاء أدخال تاريخ الكشف',
                'res_status.required'=>'برجاء أدخال حالة الكشف',
        ];
    }
}