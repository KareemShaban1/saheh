<?php

namespace Modules\Clinic\User\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Clinic\StoreUserRequest;
use App\Http\Requests\Backend\Clinic\UpdateUserRequest;
use App\Models\Clinic;
use App\Http\Traits\AuthorizeCheck;
use Modules\Clinic\Doctor\Models\Doctor;
use Illuminate\Http\Request;
use Modules\Clinic\User\Models\User;
use Modules\Clinic\User\Models\UserDoctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use AuthorizeCheck;

    public function index()
    {

        $this->authorizeCheck('view-users');


        $users = User::with('roles')->get();
        $roles = Role::where('guard_name', '=', 'web')
            ->where('team_id', '=', auth()->user()->organization_id)
            ->get();
        $doctors = Doctor::all();

        return view(
            'backend.dashboards.clinic.pages.users.index',
            compact('users', 'roles', 'doctors')
        );
    }

    public function data()
    {
        $this->authorizeCheck('view-users');


        $users = User::fromSameOrganization()
            ->with(['organization:id,name', 'roles:name', 'userDoctors.doctor.user:id,name'])
            ->select('id', 'name','job_title', 'email', 'organization_id', 'organization_type');

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
                    return  $user->organization->name;
                }
                return 'N/A';
            })

            ->addColumn('doctors', function ($user) {
                $doctorsWithBadges = $user->userDoctors->map(function ($userDoctor) {
                    $name = optional($userDoctor->doctor->user)->name;
                    return $name ? '<span class="badge bg-success text-white" style="font-size: 14px;">' . e($name) . '</span>' : '';
                })->implode(' ');

                return $doctorsWithBadges;
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
            ->rawColumns(['roles', 'actions', 'organization', 'doctors'])
            ->make(true);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorizeCheck('add-users');

        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' =>  $validatedData['name'],
                'email' =>  $validatedData['email'],
                'job_title'=>$validatedData['job_title'],
                'password' => Hash::make($validatedData['password']),
                'organization_id' => auth()->user()->organization_id,
                'organization_type' => Clinic::class
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

            // 🔹 Handle doctor relations
            if ($request->has('doctor_id')) {
                foreach ($request->doctor_id as $doctor_id) {
                    $user->userDoctors()->create([
                        'doctor_id' => $doctor_id
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => 'User added successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }


    public function edit($id)
    {
        $this->authorizeCheck('edit-users');


        $user = User::with('roles')->findOrFail($id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        $userRole = $user->roles()
            ->wherePivot('team_id', $user->organization_id)
            ->pluck('name');

        $userDoctors = $user->userDoctors->pluck('doctor_id')->all();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'userRole' => $userRole,
            'userDoctors' => $userDoctors
        ]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $this->authorizeCheck('edit-users');

        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            // 🔹 Update basic fields
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }
            $user->save();

            // 🔹 Handle roles
            $roles = $request->input('roles', []);

            if (!empty($roles)) {
                // If you're using teams (organization-based roles)
                if (config('permission.teams')) {
                    // Set the correct team context before syncing
                    app(\Spatie\Permission\PermissionRegistrar::class)
                        ->setPermissionsTeamId($user->organization_id);

                    // Sync roles for this specific organization/team
                    $user->syncRoles($roles);

                    // Reset team context (optional but safe)
                    app(\Spatie\Permission\PermissionRegistrar::class)
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

            // 🔹 Sync doctors
            $doctorIds = $request->input('doctor_id', []);
            UserDoctor::where('user_id', $user->id)->delete();
            foreach ($doctorIds as $doctorId) {
                UserDoctor::create([
                    'user_id' => $user->id,
                    'doctor_id' => $doctorId
                ]);
            }

            DB::commit();

            return redirect()
                ->route('backend.users.index')
                ->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }



    public function destroy($id)
    {
        $this->authorizeCheck('delete-users');

        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['success' => 'User deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!'], 500);
        }
    }
}
