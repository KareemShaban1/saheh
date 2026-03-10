<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\MedicalLaboratory;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Models\SystemControl;
use MacsiDigital\Zoom\Setting;

class SettingsController extends Controller
{
    public function index()
    {


        return view('backend.dashboards.medicalLaboratory.pages.settings.index');
    }

    public function medicalLaboratorySettings()
    {

        $collection = Settings::all();
        $settings = $collection->pluck('value', 'key');

        return view('backend.dashboards.medicalLaboratory.pages.settings.medicalLaboratorySettings', compact('settings'));
    }

    public function updateMedicalLaboratorySettings(Request $request)
    {

        try {
            foreach ($request->except('_token') as $key => $value) {
                Settings::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => 'medical_laboratory_settings',
                        'organization_id' => auth()->user()->organization_id,
                        'organization_type' => MedicalLaboratory::class
                    ]
                );
            }

            return back()->with('toast_success', 'Settings updated successfully');
        } catch (\Exception $e) {

            return back()->with('toast_error', $e->getMessage());
        }
    }
}
