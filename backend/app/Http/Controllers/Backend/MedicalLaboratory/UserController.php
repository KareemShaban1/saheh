<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreUserRequest;
use App\Models\MedicalLaboratory;
use Illuminate\Http\Request;
use Modules\Clinic\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{


    public function index()
    {

        $users = User::with('roles')->get();
        $roles = Role::where('guard_name', '=', 'medical_laboratory')
            ->where('team_id', '=', auth()->user()->organization_id)
            ->get();


        return view('backend.dashboards.medicalLaboratory.pages.users.index', compact('users', 'roles'));
    }

    public function data()
    {
        $users = User::fromSameOrganization()
            ->with(['roles', 'organization'])
            ->select('id', 'name', 'email', 'organization_id', 'organization_type')
            ->get();


        return DataTables::of($users)
            ->addColumn('actions', function ($user) {
                return '<button class="btn btn-warning btn-sm" onclick="editUser(' . $user->id . ')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(' . $user->id . ')">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->addColumn('organization', function ($user) {
                if ($user->organization) {
                    return $user->organization->name;
                }
                return 'N/A';
            })

            ->addColumn('roles', function ($user) {
                // ✅ Set the correct team context for this user's organization
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

                // ✅ Now get the user's roles filtered by team
                $roles = $user->roles()
                    ->wherePivot('team_id', $user->organization_id)
                    ->pluck('name');


                if ($roles->isEmpty()) {
                    return '<span class="badge bg-secondary text-white" style="font-size: 14px;">No Role</span>';
                }

                return $roles->map(function ($role) {
                    return '<span class="badge bg-primary text-white" style="font-size: 14px;">' . e($role) . '</span>';
                })->implode(' ');
            })
            ->rawColumns(['roles', 'actions', 'organization', 'roles'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'roles' => 'required|array'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => auth()->user()->organization_id,
            'organization_type' => MedicalLaboratory::class
        ]);

        // 🔹 Handle roles safely with team context
        $roles = $request->input('roles', []);

        if (!empty($roles)) {
            if (config('permission.teams')) {
                // Set the correct team before assigning roles
                app(PermissionRegistrar::class)
                    ->setPermissionsTeamId($user->organization_id);

                $user->assignRole($roles);

                // Reset context after
                app(PermissionRegistrar::class)
                    ->setPermissionsTeamId(null);
            } else {
                $user->assignRole($roles);
            }
        }

        return response()->json(['success' => 'User added successfully!']);
    }

    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $userRole = $user->roles->pluck('name', 'name')->all();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'userRole' => $userRole
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate(
            [
                'name' => 'required',
                'email' => 'required',
                // 'password' => 'required',

            ],
            [
                'name.required' => 'يجب أدخال أسم المستخدم',
                'email.required' => 'يجب أدخال البريد الألكترونى',
                // 'password.required'=>'يجب أدخال كلمة المرور',

            ]
        );

        try {

            $user = User::findorFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = $request->password ? Hash::make($request->password) : $user->password;
            $user->save();
            
             // 🔹 Handle roles
             $roles = $request->input('roles', []);

             if (!empty($roles)) {
                 // If you're using teams (organization-based roles)
                 if (config('permission.teams')) {
                     // Set the correct team context before syncing
                     app(PermissionRegistrar::class)
                         ->setPermissionsTeamId($user->organization_id);
 
                     // Sync roles for this specific organization/team
                     $user->syncRoles($roles);
 
                     // Reset team context (optional but safe)
                     app(PermissionRegistrar::class)
                         ->setPermissionsTeamId(null);
                 } else {
                     // If teams are not used
                     $user->syncRoles($roles);
                 }
             } else {
                 // If no roles sent, remove all roles for this team
                 if (config('permission.teams')) {
                     DB::table('model_has_roles')
                         ->where('model_type', get_class($user))
                         ->where('model_id', $user->id)
                         ->where('team_id', $user->organization_id)
                         ->delete();
                 } else {
                     $user->syncRoles([]);
                 }
             }

            return redirect()->route('backend.users.index')->with('success', 'Patient added successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['success' => 'User deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }
}
