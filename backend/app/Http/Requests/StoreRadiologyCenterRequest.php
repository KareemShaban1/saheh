<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyCenterRequest extends FormRequest
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
            'radiology_center_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'radiology_center_email' => 'required|email|unique:radiology_centers,email',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
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
            'radiology_center_name.required' => __('The radiology center name field is required.'),
            'start_date.required' => __('The start date field is required.'),
            'address.required' => __('The address field is required.'),
            'phone.required' => __('The phone field is required.'),
            'phone.regex' => __('Please enter a valid Egyptian phone number starting with +20.'),
            'radiology_center_email.required' => __('The email field is required.'),
            'radiology_center_email.email' => __('Please enter a valid email address.'),
            'radiology_center_email.unique' => __('This email is already registered.'),
            'user_name.required' => __('The user name field is required.'),
            'user_email.required' => __('The user email field is required.'),
            'user_email.email' => __('Please enter a valid email address.'),
            'user_email.unique' => __('This email is already registered.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least 6 characters.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ];
    }
}
