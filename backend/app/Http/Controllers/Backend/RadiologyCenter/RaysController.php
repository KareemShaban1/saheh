<?php

namespace App\Http\Controllers\Backend\RadiologyCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreRayRequest;
use App\Http\Requests\Backend\UpdateRayRequest;
use App\Http\Traits\AuthorizeCheck;
use App\Models\MedicalLaboratory;
use App\Models\ModuleService;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\Settings;
use App\Models\SystemControl;
use App\Models\Ray;
use App\Models\Scopes\RadiologyCenterScope;
use App\Models\Service;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\Facades\DataTables;

class RaysController extends Controller
{
    use AuthorizeCheck;
    //
    public function index()
    {
        $rays = Ray::withoutGlobalScope(RadiologyCenterScope::class)->get();

        return view('backend.dashboards.radiologyCenter.pages.rays.index', compact('rays'));
    }

    public function data()
    {
        $rays = Ray::withoutGlobalScope(RadiologyCenterScope::class)->get();

        return DataTables::of($rays)
            ->addColumn('cost', function ($rays) {
                return $rays->cost ?? 0;
            })
            ->addColumn('payment', function ($rays) {
                switch ($rays->payment) {
                    case 'paid':
                        return  trans('backend/rays_trans.Paid');
                    case 'not_paid':
                        return trans('backend/rays_trans.Not_Paid');
                    default:
                        return 'Unknown';
                }
            })
            ->addColumn('date', function ($rays) {
                return $rays->date;
            })
            ->addColumn('service_fee', function ($rays) {
                $Services = $rays->Services()->get();
                $ServiceIds = $Services->pluck('service_fee_id')->toArray();
                $ServiceNames = Service::whereIn('id', $ServiceIds)->pluck('service_name')->toArray();

                $ServiceNames = array_map(function ($name) {
                    return '<span class="badge badge-primary">' . $name . '</span>';
                }, $ServiceNames);

                return implode(' ', $ServiceNames);
            })
            ->addColumn('action', function ($rays) {
                $editUrl = route('radiologyCenter.rays.edit', $rays->id);
                $deleteUrl = route('radiologyCenter.rays.destroy', $rays->id);

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
            ->editColumn('patient', function ($rays) {
                return $rays->patient->name ?? 'N/A';
            })
            ->rawColumns(['action', 'service_fee']) // Ensure the HTML in the action column is not escaped
            ->make(true);
    }

    public function show($id)
    {
        // get reservation based on id
        $rays = Ray::where('id', $id)
            ->withoutGlobalScope(RadiologyCenterScope::class)
            ->get();

        return view('backend.dashboards.radiologyCenter.pages.rays.show', compact('rays'));
    }

    public function add($id)
    {


        $patient = Patient::findOrFail($id);

        return view('backend.dashboards.radiologyCenter.pages.rays.add', compact('patient'));
    }

    public function create()
    {
        $patients = Patient::radiologyCenter()->get();
        $types = Type::get();

        return view(
            'backend.dashboards.radiologyCenter.pages.rays.create',
            compact('patients', 'types')
        );
    }
    public function store(Request $request)
    {
        try {
            $data = $request->except('images');
            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;

            // Create the medical rays
            $rays = Ray::create($data);

            $totalCost = 0;

            if ($request->has('service_fee_id')) {
                foreach ($request->service_fee_id as $index => $ServiceId) {
                    $fee = $request->service_fee[$index] ?? 0;
                    $notes = $request->service_fee_notes[$index] ?? null;

                    $totalCost += $fee;

                    $raysService = new ModuleService();
                    $raysService->module_id = $rays->id;
                    $raysService->module_type = Ray::class;
                    $raysService->service_fee_id = $ServiceId;
                    $raysService->fee = $fee;
                    $raysService->notes = $notes;
                    $raysService->save();

                    if ($request->hasFile("service_fee_images.$index")) {
                        foreach ($request->file("service_fee_images")[$index] as $image) {
                            if ($image->isValid()) {
                                $raysService->addMedia($image)->toMediaCollection('service_fee_images');
                            }
                        }
                    }
                }
            }
            // Set the total cost at the end
            $rays->cost = $totalCost;
            $rays->save();

            return redirect()->route('radiologyCenter.rays.index')->with('toast_success', 'Medical Analysis added successfully');
        } catch (ValidationException $e) {

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }


    public function edit($id)
    {

        $rays = Ray::withoutGlobalScope(RadiologyCenterScope::class)
            ->findOrFail($id);
        $rays->load('Services');


        return view('backend.dashboards.radiologyCenter.pages.rays.edit', compact('rays'));
    }

    public function update(Request $request, $id)
    {
        try {
            $rays = Ray::withoutGlobalScope(RadiologyCenterScope::class)->findOrFail($id);

            $data = $request->except(['images', 'service_fee_id', 'service_fee', 'service_fee_notes', 'service_fee_images']);

            $data['organization_id'] = auth()->user()->organization_id;
            $data['organization_type'] = MedicalLaboratory::class;

            $rays->update($data);

            $existingFees = $rays->Services()->get()->keyBy('service_fee_id');

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
                        'module_id' => $rays->id,
                        'module_type' => Ray::class,
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

            $rays->cost = $totalCost;
            $rays->save();


            return redirect()->route('radiologyCenter.rays.index')->with('toast_success', 'Medical Analysis updated successfully');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }




    public function delete() {}

    public function restore() {}

    public function forceDelete() {}
}
