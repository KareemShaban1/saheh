<?php

namespace App\Http\Controllers\Backend\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ToothRecord;
use App\Http\Requests\StoreToothRecordRequest;
use App\Http\Requests\UpdateToothRecordRequest;
use App\Models\Clinic;
use App\Models\Shared\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ToothRecordController extends Controller
{
   public function show(Patient $patient)
{
    $records = ToothRecord::where('patient_id', $patient->id)->get();

    return view('backend.dashboards.clinic.pages.tooth-record.index', [
        'patient' => $patient,
        'records' => $records
    ]);
}


    public function save(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'tooth_number' => ['required','integer','between:1,32'],
            'status' => ['required', Rule::in(['healthy','decayed','filled','missing','root_canal','crown'])],
            'notes' => ['nullable','string'],
        ]);

        $record = ToothRecord::updateOrCreate(
            ['patient_id' => $patient->id, 'tooth_number' => $data['tooth_number']],
            [
                'organization_id'=> Auth::user()->organization_id,
                'organization_type'=>Clinic::class,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Tooth saved',
            'record' => $record
        ]);
    }

    public function delete(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'tooth_number' => ['required','integer','between:1,32'],
        ]);

        ToothRecord::where('patient_id', $patient->id)
            ->where('tooth_number', $data['tooth_number'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Tooth record removed']);
    }
}