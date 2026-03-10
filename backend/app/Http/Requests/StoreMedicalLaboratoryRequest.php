<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalLaboratoryRequest extends FormRequest
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
            'medical_laboratory_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'medical_laboratory_email' => 'required|email|unique:medical_laboratories,email',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'medical_laboratory_name' => __('Medical Laboratory Name'),
            'start_date' => __('Start Date'),
            'governorate_id' => __('Governorate'),
            'city_id' => __('City'),
            'area_id' => __('Area'),
            'address' => __('Address'),
            'phone' => __('Phone'),
            'medical_laboratory_email' => __('Medical Laboratory Email'),
            'user_name' => __('User Name'),
            'user_email' => __('User Email'),
            'password' => __('Password'),
            'password_confirmation' => __('Password Confirmation'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            '*.required' => __('validation.required'),
            '*.email' => __('validation.email'),
            '*.unique' => __('validation.unique'),
            '*.exists' => __('validation.exists'),
            '*.min' => __('validation.min.string'),
            '*.max' => __('validation.max.string'),
            '*.date' => __('validation.date'),
            '*.confirmed' => __('validation.confirmed'),
            '*.string' => __('validation.string'),
        ];
    }
}
