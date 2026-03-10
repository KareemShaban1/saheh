<?php
namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadiologyCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RadiologyCenterTempDataController extends Controller
{
    // Show list of pending Radiology Center registrations
    public function pendingRadiologyCenters()
    {
        // Get unique batch_ids
        $batches = DB::table('temp_data')
        ->where('type', 'radiologyCenter')

            ->select('batch_id')
            ->groupBy('batch_id')
            ->get();

        $pendingRegistrations = [];

        foreach ($batches as $batch) {
            $radiologyCenter = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'radiologyCenter')
                ->first();

            $user = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'user')
                ->first();

            $pendingRegistrations[] = [
                'batch_id' => $batch->batch_id,
                'radiologyCenter' => json_decode($radiologyCenter->data ?? '{}', true),
                'user' => json_decode($user->data ?? '{}', true),
            ];
        }

        return view('backend.dashboards.admin.pages.temp-data.radiologyCenters', compact('pendingRegistrations'));
    }

    // Approve one batch
    public function approveRadiologyCenter($batchId)
    {
        $controller = new RadiologyCenterController(); // Or move the logic here directly
        return $controller->approveRadiologyCenter($batchId);
    }

    // Optional: Delete temp registration
    public function destroyRadiologyCenter($batchId)
    {
        DB::table('temp_data')->where('batch_id', $batchId)->delete();
        return redirect()->back()->with('success', 'Temp registration deleted.');
    }


}
