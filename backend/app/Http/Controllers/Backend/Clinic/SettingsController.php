<?php

namespace App\Http\Controllers\Backend\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Models\SystemControl;
use App\Http\Traits\AuthorizeCheck;
use App\Models\Clinic;

class SettingsController extends Controller
{
    use AuthorizeCheck;

    public function index()
    {
        $this->authorizeCheck('view-settings');

        return view('backend.dashboards.clinic.pages.settings.index');
    }

    public function clinicSettings()
    {
        $this->authorizeCheck('view-settings');

        $collection = Settings::all();
        $settings = $collection->pluck('value', 'key');

        return view('backend.dashboards.clinic.pages.settings.clinicSettings', compact('settings'));
    }

    public function updateClinicSettings(Request $request)
    {
        $this->authorizeCheck('edit-system');

        foreach ($request->all() as $key => $value) {
            Settings::where('key', $key)->update(['value' => $value]);
        }

        return back();
    }

    public function reservationSettings()
    {
        $this->authorizeCheck('view-settings');

        $settings = Settings::where('type', 'clinic_reservations_settings')->pluck('value', 'key');

        // dd($settings);
        return view('backend.dashboards.clinic.pages.settings.reservationSettings', ['settings' => $settings]);
    }

    public function updateReservationSettings(Request $request)
    {
        $this->authorizeCheck('edit-settings');

        try {
            foreach ($request->except('_token') as $key => $value) {
                Settings::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => 'clinic_reservations_settings',
                        'organization_id' => auth()->user()->organization_id,
                        'organization_type' => Clinic::class
                    ]
                );
            }

            return back()->with('toast_success', 'Settings updated successfully');
        } catch (\Exception $e) {

            dd($e->getMessage());
            return back()->with('toast_error', $e->getMessage());
        }
    }

    public function zoomSettings()
    {
        $this->authorizeCheck('view-settings');

        $collection = Settings::all();
        $zoomSettings = $collection->pluck('value', 'key');

        return view('backend.dashboards.clinic.pages.settings.zoomSettings', compact('zoomSettings'));
    }

    public function updateZoomSettings(Request $request)
    {
        $this->authorizeCheck('edit-settings');

        //  Validate the form data
        $request->validate([
            'zoom_api_key' => 'required',
            'zoom_api_secret' => 'required',
        ]);

        foreach ($request->all() as $key => $value) {
            Settings::where('key', $key)->update(['value' => $value]);
        }
        $envFilePath = base_path('.env');
        $oldEnvContent = file_get_contents($envFilePath);

        // Update the .env file with the new values
        $newZoomApiKey = $request->input('zoom_api_key');
        $newZoomApiSecret = $request->input('zoom_api_secret');

        // Use the `env` helper to update .env values
        $updatedEnvContent = preg_replace('/ZOOM_CLIENT_KEY=.*/', "ZOOM_CLIENT_KEY=$newZoomApiKey", $oldEnvContent);
        $updatedEnvContent = preg_replace('/ZOOM_CLIENT_SECRET=.*/', "ZOOM_CLIENT_SECRET=$newZoomApiSecret", $updatedEnvContent);

        // Write the updated content back to the .env file
        file_put_contents($envFilePath, $updatedEnvContent);

        return back();
    }
}
