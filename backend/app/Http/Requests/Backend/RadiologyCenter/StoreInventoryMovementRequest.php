<?php

namespace App\Http\Requests\Backend\RadiologyCenter;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
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
            'inventory_id' => 'required|exists:organization_inventories,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:in,out',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }
}
