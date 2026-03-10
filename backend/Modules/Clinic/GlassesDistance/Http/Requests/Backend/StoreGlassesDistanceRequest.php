<?php

namespace Modules\Clinic\GlassesDistance\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreGlassesDistanceRequest extends FormRequest
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
            //
            // 'name' => 'required',
            'reservation_id' => 'required',
            'patient_id' => 'required',
            'SPH_R_D' => 'required',
            'CYL_R_D' => 'required',
            'AX_R_D' => 'required',
            'SPH_L_D' => 'required',
            'CYL_L_D' => 'required',
            'AX_L_D' => 'required',
            'SPH_R_N' => 'required',
            'CYL_R_N' => 'required',
            'AX_R_N' => 'required',
            'SPH_L_N' => 'required',
            'CYL_L_N' => 'required',
            'AX_L_N' => 'required',

        ];
    }
}
