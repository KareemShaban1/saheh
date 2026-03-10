<?php
namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ClinicTempDataController extends Controller
{
    // Show list of pending clinic registrations
    public function pendingClinics()
    {
        // Get unique batch_ids
        $batches = DB::table('temp_data')
            ->where('type', 'clinic')
            ->select('batch_id')
            ->groupBy('batch_id')
            ->get();

        $pendingRegistrations = [];

        foreach ($batches as $batch) {
            $clinic = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'clinic')
                ->first();

            $user = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'user')
                ->first();

            $pendingRegistrations[] = [
                'batch_id' => $batch->batch_id,
                'clinic' => json_decode($clinic->data ?? '{}', true),
                'user' => json_decode($user->data ?? '{}', true),
            ];
        }

        return view('backend.dashboards.admin.pages.temp-data.clinics', compact('pendingRegistrations'));
    }

    // Approve one batch
    public function approveClinic($batchId)
    {
        $controller = new ClinicController(); // Or move the logic here directly
        return $controller->approveClinic($batchId);
    }

    // Optional: Delete temp registration
    public function destroyClinic($batchId)
    {
        DB::table('temp_data')->where('batch_id', $batchId)->delete();
        return redirect()->back()->with('success', 'Temp registration deleted.');
    }


}
