<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Models\Shared\OrganizationInventory;
use App\Http\Requests\Backend\MedicalLaboratory\StoreOrganizationInventoryRequest;
use App\Http\Requests\Backend\MedicalLaboratory\UpdateOrganizationInventoryRequest;
use App\Http\Controllers\Controller;
use App\Http\Traits\AuthorizeCheck;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class OrganizationInventoryController extends Controller
{

    use AuthorizeCheck;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // $this->authorizeCheck('view-organization-inventory');

        return view('backend.dashboards.medicalLaboratory.pages.organization-inventories.index');
    }

    public function data()
    {
        // $this->authorizeCheck('view-organization-inventory');

        $organizationInventories = OrganizationInventory::fromSameOrganization()->get();

        return DataTables::of($organizationInventories)
        ->addColumn('movements' , function ($organizationInventory) {
            return '<a class="btn btn-primary btn-sm" href="' . route('medicalLaboratory.inventory-movements.index', $organizationInventory->id) . '">Movements</a>';
        })
            ->addColumn('action', function ($organizationInventory) {
                return '<button class="btn btn-warning btn-sm" onclick="editOrganizationInventory(' . $organizationInventory->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteOrganizationInventory(' . $organizationInventory->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['action', 'movements'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        // $this->authorizeCheck('add-organization-inventory');

        return view('backend.dashboards.medicalLaboratory.pages.organization-inventories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationInventoryRequest $request)
    {
        // $this->authorizeCheck('add-organization-inventory');
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            $validatedData['organization_id'] = auth()->user()->organization_id;
            $validatedData['organization_type'] = MedicalLaboratory::class;

            OrganizationInventory::create($validatedData);

            DB::commit();

            return response()->json(['message' => 'Organization Inventory Created!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Organization Inventory Not Created!']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganizationInventory $organizationInventory)
    {
        //
        // $this->authorizeCheck('view-organization-inventory');

        return view('backend.dashboards.medicalLaboratory.pages.organization-inventories.show', compact('organizationInventory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        // $this->authorizeCheck('edit-organization-inventory');

        $organizationInventory = OrganizationInventory::findOrFail($id);

        return response()->json([
            'id' => $organizationInventory->id,
            'name' => $organizationInventory->name,
            'quantity' => $organizationInventory->quantity,
            'unit' => $organizationInventory->unit,
            'price' => $organizationInventory->price,
            'description' => $organizationInventory->description,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationInventoryRequest $request, $id)
    {
        // $this->authorizeCheck('edit-organization-inventory');

        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            $organizationInventory = OrganizationInventory::findOrFail($id);
            $organizationInventory->update($validatedData);

            DB::commit();

            return response()->json(['toast_success' => 'Organization Inventory Updated!']);
            // return redirect()->route('clinic.organization-inventories.index')
            //     ->with('toast_success', 'Organization Inventory updated Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['toast_error' => 'Organization Inventory Not Updated!']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // $this->authorizeCheck('delete-organization-inventory');

        DB::beginTransaction();

        try {
            $organizationInventory = OrganizationInventory::findOrFail($id);
            $organizationInventory->delete();

            DB::commit();

            return response()->json(['success' => 'Organization Inventory Deleted!']);
            // return redirect()->route('clinic.organization-inventories.index')
            //     ->with('toast_success', 'Organization Inventory deleted Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Organization Inventory Not Deleted!']);
        }
    }
}