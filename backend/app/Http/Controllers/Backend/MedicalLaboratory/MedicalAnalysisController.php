<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
;
use App\Models\LabService;
use App\Models\LabServiceOption;
use App\Models\MedicalAnalysis;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Models\Scopes\MedicalLaboratoryScope;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class MedicalAnalysisController extends Controller
{
    //
    public function index()
    {
        $medicalAnalysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)->get();

        return view('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.index', compact('medicalAnalysis'));
    }

    public function data()
    {
        $medicalAnalysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)->get();

        return DataTables::of($medicalAnalysis)
            ->addColumn('patient', function ($analysis) {
                return '<a href="' . route('medicalLaboratory.patients.show', $analysis->patient->id) . '" 
                            class="badge badge-success text-white font-weight-bold">
                            ' . $analysis->patient->name . '
                        </a>';
            })
            ->addColumn('cost', function ($analysis) {
                return $analysis->cost ?? 0;
            })
            ->addColumn('payment', function ($analysis) {
                switch ($analysis->payment) {
                    case 'paid':
                        return  trans('backend/medicalAnalysis_trans.Paid');
                    case 'not_paid':
                        return trans('backend/medicalAnalysis_trans.Not_Paid');
                    default:
                        return 'Unknown';
                }
            })
            ->addColumn('date', function ($analysis) {
                return $analysis->date;
            })
            ->addColumn('service_fee', function ($analysis) {
                $serviceOptions = $analysis->labServiceOptions()->get();
                $serviceOptionNames = $serviceOptions->pluck('name')->toArray();


                $serviceOptionNames = array_map(function ($name) {
                    return '<span class="badge badge-primary">' . $name . '</span>';
                }, $serviceOptionNames);

                return implode(' ', $serviceOptionNames);

            })
            ->addColumn('action', function ($analysis) {
                $editUrl = route('medicalLaboratory.analysis.edit', $analysis->id);
                $deleteUrl = route('medicalLaboratory.analysis.destroy', $analysis->id);

                return '
                <a href="' . $editUrl . '" class="btn btn-warning btn-sm">
                    <i class="fa fa-edit"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this item?\')">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
                <a href="' . route('medicalLaboratory.analysis.report', $analysis->id) . '" class="btn btn-info btn-sm">
                    <i class="fa fa-file-pdf"></i>
                    '.__('backend/medicalAnalysis_trans.Gathered_Report').'
                </a>
                <a href="' . route('medicalLaboratory.analysis.page-report', $analysis->id) . '" class="btn btn-success btn-sm">
                    <i class="fa fa-file-pdf"></i>
                    '.__('backend/medicalAnalysis_trans.Page_Report').'
                </a>
            ';
            })
            ->rawColumns(['action', 'service_fee' , 'patient']) // Ensure the HTML in the action column is not escaped
            ->make(true);
    }

    public function show($id)
    {
        // get reservation based on id
        $medical_analysis = MedicalAnalysis::where('id', $id)
            ->withoutGlobalScope(MedicalLaboratoryScope::class)
            ->get();

        return view('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.show', compact('medical_analysis'));
    }

    public function add($id)
    {


        $patient = Patient::findOrFail($id);

        return view('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.add', compact('patient'));
    }

    public function create()
    {
        $patients = Patient::medicalLaboratory()->get();
        $types = Type::get();

        return view(
            'backend.dashboards.medicalLaboratory.pages.medicalAnalysis.create',
            compact('patients', 'types')
        );
    }
    public function store(Request $request)
    {
        try {
            // Extract base data
            $data = $request->only([
                'patient_id',
                'date',
                'payment',
                'doctor_name',
                'cost',
                'report'
            ]);

            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;

            // Create the medical analysis record
            $medical_analysis = MedicalAnalysis::create($data);

            $totalCost = 0;

            // Loop through lab_service_id (keyed by categoryIndex)
            if ($request->has('lab_service_id') && is_array($request->lab_service_id)) {
                foreach ($request->lab_service_id as $categoryIndex => $serviceIds) {

                    // Find matching lab_service_category_id using manual lookup
                    $labServiceCategoryId = $request->lab_service_category_id[array_search($categoryIndex, array_keys($request->lab_service_id))] ?? null;

                    foreach ($serviceIds as $optionIndex => $labServiceId) {
                        $name         = $request->name[$categoryIndex][$optionIndex] ?? null;
                        $price        = $request->price[$categoryIndex][$optionIndex] ?? 0;
                        $value        = $request->value[$categoryIndex][$optionIndex] ?? null;
                        $unit         = $request->unit[$categoryIndex][$optionIndex] ?? null;
                        $normal_range = $request->normal_range[$categoryIndex][$optionIndex] ?? null;

                        $totalCost += floatval($price);

                        LabServiceOption::create([
                            'lab_service_id' => $labServiceId,
                            'lab_service_category_id' => $labServiceCategoryId,
                            'module_id' => $medical_analysis->id,
                            'module_type' => MedicalAnalysis::class,
                            'name' => $name,
                            'price' => $price,
                            'value' => $value,
                            'unit' => $unit,
                            'normal_range' => $normal_range,
                        ]);
                    }
                }
            }

            // Save cost
            $medical_analysis->cost = $totalCost;
            $medical_analysis->save();

            return redirect()->route('medicalLaboratory.analysis.index')
                ->with('toast_success', 'Medical Analysis added successfully');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }



    public function edit($id)
    {

        $analysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)
            ->findOrFail($id);
        $analysis->load('labServiceOptions');

        return view('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.edit', compact('analysis'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        try {
            DB::beginTransaction();

            // Fetch the existing medical analysis
            $medical_analysis = MedicalAnalysis::findOrFail($id);

            // Extract base data
            $data = $request->only([
                'patient_id',
                'date',
                'payment',
                'doctor_name',
                'cost',
                'report'
            ]);

            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;

            // Update the medical analysis record
            $medical_analysis->update($data);

            $totalCost = 0;

            // Delete previous related options
            LabServiceOption::where('module_id', $medical_analysis->id)
                ->where('module_type', MedicalAnalysis::class)
                ->delete();

            // Rebuild options based on new flat arrays
            if (
                $request->has('lab_service_id') && is_array($request->lab_service_id) &&
                $request->has('name') && is_array($request->name)
            ) {
                $count = count($request->name);

                for ($i = 0; $i < $count; $i++) {
                    // Defensive: skip if lab_service_id is missing or empty
                    $labServiceId = $request->lab_service_id[$i] ?? null;
                    if (empty($labServiceId)) {
                        continue;
                    }

                    // Fetch LabService only if needed
                    $labService = LabService::find($labServiceId);
                    if (!$labService || !$labService->lab_service_category_id) {
                        // Optionally log or collect skipped IDs for debugging
                        Log::warning("Skipped LabServiceOption: lab_service_id $labServiceId not found or has no category");
                        continue;
                    }
                    $labServiceCategoryId = $labService->lab_service_category_id;


                    $name = $request->name[$i] ?? null;
                    $price = $request->price[$i] ?? 0;
                    $value = $request->value[$i] ?? null;
                    $unit = $request->unit[$i] ?? null;
                    $normal_range = $request->normal_range[$i] ?? null;


                    $totalCost += floatval($price);

                    LabServiceOption::create([
                        'lab_service_id' => $labServiceId,
                        'lab_service_category_id' => $labServiceCategoryId,
                        'module_id' => $medical_analysis->id,
                        'module_type' => MedicalAnalysis::class,
                        'name' => $name,
                        'price' => $price,
                        'value' => $value,
                        'unit' => $unit,
                        'normal_range' => $normal_range,
                    ]);
                }
            }
            // Update cost
            $medical_analysis->cost = $totalCost;
            $medical_analysis->save();

            DB::commit();

            return redirect()->route('medicalLaboratory.analysis.index')
                ->with('toast_success', 'Medical Analysis updated successfully');
        } catch (ValidationException $e) {
            DB::rollBack();
            dd($e->getMessage());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }





    public function delete() {}

    public function restore() {}

    public function forceDelete() {}

    public function generateReport($id)
    {
        $analysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)
            ->findOrFail($id);
    
        $analysis->load('labServiceOptions');
    
        $pdf = PDF::loadView('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.report-pdf', compact('analysis'));
    
        return $pdf->stream('medical_analysis_report_' . $id . '.pdf');
    }

    public function generatePageReport($id)
    {
        $analysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)
            ->findOrFail($id);
    
        $analysis->load('labServiceOptions');
    
        $pdf = PDF::loadView('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.report-page', compact('analysis'));
    
        return $pdf->stream('medical_analysis_report_' . $id . '.pdf');
    }
}
