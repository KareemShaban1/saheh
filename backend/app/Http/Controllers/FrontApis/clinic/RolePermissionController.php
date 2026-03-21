<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\BaseFrontApiController;
class RolePermissionController extends BaseFrontApiController
{
    /**
     * Roles list for clinic
     */
    public function roles()
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $roles = Role::where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
            ->withCount('permissions')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'permissions_count' => $r->permissions_count]);
        return $this->returnJSON($roles, 'Roles', 'success');
    }

    /**
     * Permissions list for clinic role forms.
     */
    public function permissions()
    {
        $this->ensureClinicAuth();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]);

        return $this->returnJSON($permissions, 'Permissions', 'success');
    }

    /**
     * Single role details including selected permissions.
     */
    public function roleDetails($id)
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
            ->with('permissions:id,name')
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => $role->permissions->count(),
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($v) => (int) $v)->values(),
            'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ], 'Role details', 'success');
    }

    /**
     * Create role with selected permissions.
     */
    public function createRole(Request $request)
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'team_id' => $orgId,
        ]);
        $permissions = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role created', 'success');
    }

    /**
     * Update role and selected permissions.
     */
    public function updateRole(Request $request, $id)
    {
        $this->ensureClinicAuth();
        $orgId = request()->user()->organization_id;
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($orgId) {
                $query->where('team_id', $orgId)
                    ->orWhereNull('team_id');
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'required|exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);
        $permissions = Permission::query()
            ->whereIn('id', $validated['permission_ids'] ?? [])
            ->pluck('name')
            ->toArray();
        $role->syncPermissions($permissions);

        return $this->returnJSON([
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ], 'Role updated', 'success');
    }
}
