<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedAdminPermissions();
        $this->seedClinicPermissions();
        $this->seedLabPermissions();
        $this->seedRadiologyPermissions();

        $this->command->info('Roles and permissions seeded successfully (dashboard-module based).');
    }

    private function seedAdminPermissions(): void
    {
        $permissions = [
            'view users',
            'create user',
            'update user',
            'delete user',
            'view roles',
            'create role',
            'update role',
            'delete role',
            'view settings',
            'update settings',
            'view activity logs',
            'create activity log',
            'update activity log',
            'delete activity log',
            'view doctor profiles',
            'approve doctor profile',
            'reject doctor profile',
            'toggle featured doctor profile',
            'toggle lock doctor profile',
        ];

        $this->createGuardRolesAndPermissions('admin', 1, $permissions, [
            'admin' => 'all',
        ]);
    }

    private function seedClinicPermissions(): void
    {
        $modules = [
            'dashboard',
            'reservations',
            'reservation-numbers',
            'reservation-slots',
            'doctors',
            'patients',
            'roles',
            'permissions',
            'chat',
            'chats',
            'reviews',
            'announcements',
            'notifications',
            'services',
            'inventory',
            'financial',
            'modules',
            'users',
        ];

        $clinics = Clinic::all();
        if ($clinics->isEmpty()) {
            $this->command->warn('No clinics found. Clinic roles and permissions were skipped.');
            return;
        }

        $permissions = $this->buildModulePermissions($modules);
        $this->command->info("Creating module-based roles for {$clinics->count()} clinic(s)");
        foreach ($clinics as $clinic) {
            $this->createGuardRolesAndPermissions('web', (int) $clinic->id, $permissions, [
                'clinic-admin' => ['*'],
                'clinic-doctor' => [
                    'dashboard.view',
                    'reservations.*',
                    'reservation-numbers.*',
                    'reservation-slots.*',
                    'patients.*',
                    'doctors.*',
                    'reviews.*',
                    'notifications.*',
                    'chat.*',
                    'chats.*',
                    'services.view',
                ],
                'clinic-user' => [
                    'dashboard.view',
                    'reservations.view',
                    'reservations.create',
                    'reservations.update',
                    'patients.view',
                    'patients.create',
                    'patients.update',
                    'notifications.view',
                    'chat.*',
                    'chats.*',
                ],
            ]);
        }
    }

    private function seedLabPermissions(): void
    {
        $modules = [
            'dashboard',
            'reservations',
            'patients',
            'chat',
            'chats',
            'notifications',
            'users',
            'service-categories',
            'services',
            'medical-analyses',
            'financial',
            'roles',
            'permissions',
        ];

        $labs = MedicalLaboratory::all();
        if ($labs->isEmpty()) {
            $this->command->warn('No medical laboratories found. Lab roles and permissions were skipped.');
            return;
        }

        $permissions = $this->buildModulePermissions($modules);
        $this->command->info("Creating module-based roles for {$labs->count()} medical laboratory(ies)");
        foreach ($labs as $lab) {
            $this->createGuardRolesAndPermissions('medical_laboratory', (int) $lab->id, $permissions, [
                'medical-laboratory-admin' => ['*'],
                'medical-laboratory-doctor' => [
                    'dashboard.view',
                    'patients.*',
                    'medical-analyses.*',
                    'services.view',
                    'service-categories.view',
                    'notifications.*',
                    'chat.*',
                    'chats.*',
                ],
                'medical-laboratory-user' => [
                    'dashboard.view',
                    'patients.view',
                    'medical-analyses.view',
                    'notifications.view',
                    'chat.view',
                    'chats.view',
                ],
            ]);
        }
    }

    private function seedRadiologyPermissions(): void
    {
        $modules = [
            'dashboard',
            'reservations',
            'patients',
            'chat',
            'chats',
            'notifications',
            'roles',
            'permissions',
            'financial',
        ];

        $centers = RadiologyCenter::all();
        if ($centers->isEmpty()) {
            $this->command->warn('No radiology centers found. Radiology roles and permissions were skipped.');
            return;
        }

        $permissions = $this->buildModulePermissions($modules);
        $this->command->info("Creating module-based roles for {$centers->count()} radiology center(s)");
        foreach ($centers as $center) {
            $this->createGuardRolesAndPermissions('radiology_center', (int) $center->id, $permissions, [
                'radiology-center-admin' => ['*'],
                'radiology-center-doctor' => [
                    'dashboard.view',
                    'reservations.*',
                    'patients.*',
                    'notifications.*',
                    'chat.*',
                    'chats.*',
                ],
                'radiology-center-user' => [
                    'dashboard.view',
                    'reservations.view',
                    'patients.view',
                    'notifications.view',
                    'chat.view',
                    'chats.view',
                ],
            ]);
        }
    }

    private function buildModulePermissions(array $modules): array
    {
        $permissions = [];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = $module . '.' . $action;
            }
            $permissions[] = 'manage ' . $module;
        }

        return array_values(array_unique($permissions));
    }

    protected function createGuardRolesAndPermissions(string $guard, int $teamId, array $permissions, array $rolesWithPerms): void
    {
        try {
            // Set the team context for permissions if the function exists
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($teamId);
            }
    
            $this->command->info("Creating permissions for {$guard} guard with team ID {$teamId}");
    
            // Create all available permissions first
            foreach ($permissions as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => $guard,
                ]);
            }
    
            $this->command->info("Creating roles for {$guard} guard");
    
            foreach ($rolesWithPerms as $roleName => $perms) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard,
                    'team_id' => $teamId,
                ]);
    
                // 🧠 Get all permissions for this guard
                $allPermissions = Permission::where('guard_name', $guard)->get();
    
                // 🟢 Handle wildcard / all cases
                if ($perms === 'all' || (is_array($perms) && in_array('*', $perms, true))) {
                    $role->syncPermissions($allPermissions);
                    $this->command->info("Assigned ALL permissions to role: {$roleName}");
                    continue;
                }
    
                // Handle wildcard patterns (e.g., 'patients.*', 'reservations.view*')
                $matchedPermissions = collect();
    
                foreach ($perms as $perm) {
                    if (str_contains($perm, '*')) {
                        // Convert wildcard to regex and match against available permissions
                        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $perm) . '$/';
                        $matched = $allPermissions->filter(fn($p) => preg_match($regex, $p->name));
                        $matchedPermissions = $matchedPermissions->merge($matched);
                    } else {
                        $permission = $allPermissions->firstWhere('name', $perm);
                        if ($permission) {
                            $matchedPermissions->push($permission);
                        }
                    }
                }
    
                // Assign resolved permissions to the role
                $role->syncPermissions($matchedPermissions);
                $this->command->info('Assigned '.count($matchedPermissions)." permissions to role: {$roleName}");
            }
        } catch (\Exception $e) {
            $this->command->error("Error creating roles/permissions for {$guard}: ".$e->getMessage());
            throw $e;
        }
    }
    
}