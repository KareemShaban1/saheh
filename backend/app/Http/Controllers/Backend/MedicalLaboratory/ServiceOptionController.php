<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\LabService;
use App\Models\LabServiceOption;
use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Http\Request;

class ServiceOptionController extends Controller
{
    //

    public function getOptions($id)
    {
        $serviceOptions = ServiceOption::where('service_fee_id', $id)->get();

        return response()->json($serviceOptions);
    }

    public function getServiceByCategory($category_id)
    {
        $services = LabService::where('lab_service_category_id', $category_id)->get();
        return response()->json($services);
       
    }
    
    public function store(Request $request)
    {
        if (!empty($request->option_name)) {
            foreach ($request->option_name as $index => $names) {
                $units = $request->option_unit[$index] ?? [];
                $normal_ranges = $request->option_normal_range[$index] ?? [];

                foreach ($names as $i => $name) {
                    ServiceOption::create([
                        'service_fee_id' => $request->service_fee_id,
                        'name' => $name,
                        'unit' => $units[$i] ?? null,
                        'normal_range' => $normal_ranges[$i] ?? null,
                    ]);
                }
            }
        }

        return back()->with('toast_success', 'Service Options Added');
    }

    public function edit($id)
    {
        $Service = Service::findOrFail($id);
        $serviceOption = $Service->serviceOptions;
        return response()->json($serviceOption);
    }

    public function update(Request $request, $id)
    {
        $names = $request->option_name ?? [];
        $units = $request->option_unit ?? [];
        $ranges = $request->option_normal_range ?? [];

        foreach ($names as $i => $name) {
            $existingOption = ServiceOption::where('service_fee_id', $id)
                ->where('name', $name)
                ->first();

            if ($existingOption) {
                $existingOption->update([
                    'unit' => $units[$i] ?? null,
                    'normal_range' => $ranges[$i] ?? null,
                ]);
            } else {
                ServiceOption::create([
                    'service_fee_id' => $id,
                    'name' => $name,
                    'unit' => $units[$i] ?? null,
                    'normal_range' => $ranges[$i] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Service Options updated successfully']);
    }



    public function destroy($id)
    {
        $serviceOption = ServiceOption::findOrFail($id);
        $serviceOption->delete();
        return response()->json(['success' => true]);
    }
}
