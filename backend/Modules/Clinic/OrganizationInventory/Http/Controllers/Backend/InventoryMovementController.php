<?php

namespace Modules\Clinic\OrganizationInventory\Http\Controllers\Backend;

use App\Models\Shared\InventoryMovement;
use Modules\Clinic\OrganizationInventory\Http\Requests\Backend\StoreInventoryMovementRequest;
use Modules\Clinic\OrganizationInventory\Http\Requests\Backend\UpdateInventoryMovementRequest;
use App\Http\Controllers\Controller;
use App\Http\Traits\AuthorizeCheck;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventoryMovementController extends Controller
{
    use AuthorizeCheck;
    /**
     * Display a listing of the resource.
     */
    public function index($inventoryId)
    {
        //
        // $this->authorizeCheck('view-inventory-movement');

        return view('backend.dashboards.clinic.pages.inventory-movements.index', compact('inventoryId'));
    }

    public function data($inventoryId)
    {
        // $this->authorizeCheck('view-inventory-movement');

        $inventoryMovements = InventoryMovement::with('inventory')
            ->where('inventory_id', $inventoryId)
            ->get();

        return DataTables::of($inventoryMovements)
            ->addColumn('name', function ($inventoryMovement) {
                return $inventoryMovement->inventory ? $inventoryMovement->inventory->name : 'N/A';
            })
            ->addColumn('action', function ($inventoryMovement) {
                return '<button class="btn btn-warning btn-sm" onclick="editInventoryMovement(' . $inventoryMovement->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteInventoryMovement(' . $inventoryMovement->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        // $this->authorizeCheck('add-inventory-movement');

        return view('backend.dashboards.clinic.pages.inventory-movements.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryMovementRequest $request)
    {
        // $this->authorizeCheck('add-inventory-movement');
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {

                $inventoryMovement = InventoryMovement::create($validatedData);

                if ($inventoryMovement->type == 'in') {
                    $inventoryMovement->inventory()->update([
                        'quantity' => $inventoryMovement->inventory->quantity + $inventoryMovement->quantity
                    ]);
                } else {
                    $inventoryMovement->inventory()->update([
                        'quantity' => $inventoryMovement->inventory->quantity - $inventoryMovement->quantity
                    ]);
                }

            DB::commit();

            return response()->json(['message' => 'Inventory Movement Created!']);
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            return response()->json(['message' => 'Organization Inventory Not Created!']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryMovement $inventoryMovement)
    {
        //
        // $this->authorizeCheck('view-inventory-movement');

        return view('backend.dashboards.clinic.pages.inventory-movements.show', compact('inventoryMovement'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        // $this->authorizeCheck('edit-inventory-movement');

        $inventoryMovement = InventoryMovement::findOrFail($id);

        return response()->json([
            'id' => $inventoryMovement->id,
            'inventory_id' => $inventoryMovement->inventory_id,
            'type' => $inventoryMovement->type,
            'quantity' => $inventoryMovement->quantity,
            'movement_date' => $inventoryMovement->movement_date,
            'notes' => $inventoryMovement->notes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryMovementRequest $request, $id)
    {
        // $this->authorizeCheck('edit-organization-inventory');

        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            $inventoryMovement = InventoryMovement::findOrFail($id);
            $inventoryMovement->update($validatedData);

            if ($inventoryMovement->type == 'in') {
                $inventoryMovement->inventory()->update([
                    'quantity' => $inventoryMovement->inventory->quantity + $inventoryMovement->quantity
                ]);
            } else {
                $inventoryMovement->inventory()->update([
                    'quantity' => $inventoryMovement->inventory->quantity - $inventoryMovement->quantity
                ]);
            }

            DB::commit();

            return response()->json(['toast_success' => 'Inventory Movement Updated!']);
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
            $inventoryMovement = InventoryMovement::findOrFail($id);
            $inventoryMovement->delete();

            DB::commit();

            return response()->json(['success' => 'Inventory Movement Deleted!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Organization Inventory Not Deleted!']);
        }
    }
}
