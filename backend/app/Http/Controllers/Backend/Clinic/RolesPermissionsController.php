<?php

namespace App\Http\Controllers\Backend\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Clinic\StoreRoleRequest;
use App\Http\Requests\Backend\Clinic\UpdateRoleRequest;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Traits\AuthorizeCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class RolesPermissionsController extends Controller
{
    use AuthorizeCheck;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorizeCheck('view-roles');

        $roles = Role::where('guard_name', 'web')->orderBy('id', 'DESC')->paginate(5);
        return view('backend.dashboards.clinic.pages.roles.index', compact('roles'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function data()
    {
        $roles = Role::where('guard_name', 'web')
            ->withCount('permissions');

        return DataTables::of($roles)

            ->addColumn('actions', function ($role) {
                return ' <button class="btn btn-warning btn-sm editRole" data-id="' . $role->id . '">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm deleteRole" data-id="' . $role->id . '">
                    <i class="fa fa-trash"></i>
                </button>';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function permissions()
    {
        $permissions = Permission::where('guard_name', 'web')->get();
        return response()->json($permissions);
    }



    public function store(StoreRoleRequest $request)
    {
        $this->authorizeCheck('add-role');

        try {
            $validatedData = $request->validated();

            // Create the role
            $role = Role::create([
                'name' => $validatedData['name'],
                'guard_name' => 'web',
            ]);

            // Sync permissions properly
            $role->syncPermissions($validatedData['permissions']);

            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'toast_error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'toast_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    public function edit($id)
    {
        $this->authorizeCheck('edit-role');

        $role = Role::find($id);
        $permissions = Permission::where('guard_name', 'web')->get();
        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_id", $id)
            ->pluck('permission_id')
            ->toArray();

        return response()->json([
            'status' => 'success',
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ]);
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $this->authorizeCheck('edit-role');

        $validatedData = $request->validated();

        $role = Role::findOrFail($id); // Use findOrFail to handle invalid IDs
        $role->update(['name' => $validatedData['name']]);

        if ($request->has('permissions')) {
            $role->syncPermissions($validatedData['permissions']);
        } else {
            $role->syncPermissions([]);
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
        ]);
    }


    public function destroy($id)
    {
        $this->authorizeCheck('delete-role');

        DB::table("roles")->where('id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully',
        ]);
    }
}
