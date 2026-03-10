<?php

namespace App\Http\Requests\Backend\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            //
            'service_name' => 'required',
            'fee' => 'required',
            'notes' => 'required',
            'doctor_id' => 'required',
        ];
    }
}
