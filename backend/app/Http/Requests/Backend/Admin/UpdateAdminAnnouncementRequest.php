<?php

namespace App\Http\Requests\Backend\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminAnnouncementRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'is_active' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'type' => 'required|in:text,banner',
            'send_notification' => 'nullable',
        ];
    }
}
