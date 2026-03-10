<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MedicalLaboratoryTempDataController extends Controller
{
    // Show list of pending Medical Laboratory registrations
    public function pendingMedicalLaboratories()
    {
        // Get unique batch_ids
        $batches = DB::table('temp_data')
            ->where('type', 'medicalLaboratory')
            ->select('batch_id')
            ->groupBy('batch_id')
            ->get();

        $pendingRegistrations = [];

        foreach ($batches as $batch) {
            $medicalLaboratory = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'medicalLaboratory')
                ->first();

            $user = DB::table('temp_data')
                ->where('batch_id', $batch->batch_id)
                ->where('type', 'user')
                ->first();

            $pendingRegistrations[] = [
                'batch_id' => $batch->batch_id,
                'medicalLaboratory' => json_decode($medicalLaboratory->data ?? '{}', true),
                'user' => json_decode($user->data ?? '{}', true),
            ];
        }

        return view('backend.dashboards.admin.pages.temp-data.medicalLaboratories', compact('pendingRegistrations'));
    }

    // Approve one batch
    public function approveMedicalLaboratory($batchId)
    {
        $controller = new MedicalLaboratoryController(); // Or move the logic here directly
        return $controller->approveMedicalLaboratory($batchId);
    }

    // Optional: Delete temp registration
    public function destroyMedicalLaboratory($batchId)
    {
        DB::table('temp_data')->where('batch_id', $batchId)->delete();
        return redirect()->back()->with('success', 'Temp registration deleted.');
    }
}
