<?php

namespace Modules\Clinic\Doctor\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
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
        // Determine the user ID for update (if doctor exists)
        $doctorId = $this->route('doctor_id'); // assumes your route like clinic.doctors.update/{doctor}

        // Get user_id if updating
        $userId = null;
        if ($doctorId && $doctor = \Modules\Clinic\Doctor\Models\Doctor::find($doctorId)) {
            $userId = $doctor->user_id;
        }

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId,
            ],
            'password' => $this->isMethod('post') ? 'required|min:6' : 'nullable|min:6',
            'phone' => 'required|string|max:20',
            'certifications' => 'required|string',
            'specialty_id' => 'required|exists:specialties,id',
        ];
    }
}
