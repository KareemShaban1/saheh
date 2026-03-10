<?php

namespace App\Http\Controllers\Backend\RadiologyCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreAnalysisRequest;
use App\Http\Requests\Backend\UpdateAnalysisRequest;
use App\Models\MedicalAnalysis;
use App\Models\MedicalLaboratory;
use App\Models\ModuleService;
use App\Models\OrganizationService;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Scopes\MedicalLaboratoryScope;
use App\Models\Service;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

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
            ->addColumn('cost', function ($analysis) {
                return $analysis->cost ?? 0;
            })
            ->addColumn('payment', function ($analysis) {
                switch ($analysis->payment) {
                    case 'paid':
                        return  trans('backend/medicalAnalysis_trans.Paid') ;
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
                $Services = $analysis->Services()->get();
                $ServiceIds = $Services->pluck('service_fee_id')->toArray();
                $ServiceNames = Service::
                whereIn('id', $ServiceIds)->pluck('service_name')->toArray();
                
                $ServiceNames = array_map(function ($name) {
                    return '<span class="badge badge-primary">' . $name . '</span>';
                }, $ServiceNames);

                return implode(' ', $ServiceNames);
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
            ';
            })
            ->editColumn('patient', function ($analysis) {
                return $analysis->patient->name ?? 'N/A';
            })
            ->rawColumns(['action', 'service_fee']) // Ensure the HTML in the action column is not escaped
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
            $data = $request->except('images');
            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;
    
            // Create the medical analysis
            $medical_analysis = MedicalAnalysis::create($data);
    
            $totalCost = 0;
    
            if ($request->has('service_fee_id')) {
                foreach ($request->service_fee_id as $index => $ServiceId) {
                    $fee = $request->service_fee[$index] ?? 0;
                    $notes = $request->service_fee_notes[$index] ?? null;
    
                    $totalCost += $fee;
    
                    $analysisService = new ModuleService();
                    $analysisService->module_id = $medical_analysis->id;
                    $analysisService->module_type = MedicalAnalysis::class;
                    $analysisService->service_fee_id = $ServiceId;
                    $analysisService->fee = $fee;
                    $analysisService->notes = $notes;
                    $analysisService->save();
    
                    if ($request->hasFile("service_fee_images.$index")) {
                        foreach ($request->file("service_fee_images")[$index] as $image) {
                            $analysisService->addMedia($image)->toMediaCollection('service_fee_images');

                            $medical_analysis->addMedia($image)->toMediaCollection('analysis_images');
                        }
                    }

                }
            }
    
            // Set the total cost at the end
            $medical_analysis->cost = $totalCost;
            $medical_analysis->save();
    
            return redirect()->route('medicalLaboratory.analysis.index')->with('toast_success', 'Medical Analysis added successfully');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
    

    public function edit($id)
    {

        $analysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)
        ->findOrFail($id);
        $analysis->load('Services');


        return view('backend.dashboards.medicalLaboratory.pages.medicalAnalysis.edit', compact('analysis'));
    }

    public function update(Request $request, $id)
    {
        try {
            $medical_analysis = MedicalAnalysis::withoutGlobalScope(MedicalLaboratoryScope::class)->findOrFail($id);
    
            $data = $request->except(['images', 'service_fee_id', 'service_fee', 'service_fee_notes', 'service_fee_images']);
    
            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;

            $medical_analysis->update($data);
    
            $existingFees = $medical_analysis->Services()->get()->keyBy('service_fee_id');
        
            $totalCost = 0;

            foreach ($request->service_fee_id as $index => $ServiceId) {
                $fee = $request->service_fee[$index] ?? 0;
                $notes = $request->service_fee_notes[$index] ?? null;
            
                $totalCost += $fee; // accumulate total cost
                
                $Service = $existingFees->get($ServiceId);
            
                if ($Service) {
                    $Service->update([
                        'fee' => $fee,
                        'notes' => $notes,
                    ]);
                } else {
                    $Service = ModuleService::create([
                        'module_id' => $medical_analysis->id,
                        'module_type' => MedicalAnalysis::class,
                        'service_fee_id' => $ServiceId,
                        'fee' => $fee,
                        'notes' => $notes,
                    ]);
                }
            
                // Handle images
                if ($request->hasFile("service_fee_images.$index")) {
                    $Service->clearMediaCollection('service_fee_images');
                    foreach ($request->file("service_fee_images")[$index] as $image) {
                        $Service->addMedia($image)->toMediaCollection('service_fee_images');
                    }
                }
            }
            
            $medical_analysis->cost = $totalCost;
            $medical_analysis->save();
            
    
            return redirect()->route('medicalLaboratory.analysis.index')->with('toast_success', 'Medical Analysis updated successfully');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    
    

    public function delete() {}

    public function restore() {}

    public function forceDelete() {}
}
