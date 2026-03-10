<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

trait AuthorizeCheck
{
    /**
     * Check if the authenticated user has permission, considering team context.
     * Automatically uses the user's organization as team context if not provided.
     *
     * @param string $permission
     * @param int|null $teamId
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeCheck(string $permission, ?int $teamId = null)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('You must be logged in.'));
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        if (! $user->can($permission)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                __('You are unauthorized to perform this action.')
            );
        }

        // Reset team context after check to avoid leaking to next requests
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }
    }

    /**
     * Check if the authenticated user has any of the given permissions.
     *
     * @param array $permissions
     * @param int|null $teamId
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeAny(array $permissions, ?int $teamId = null)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('You must be logged in.'));
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                $hasPermission = true;
                break;
            }
        }

        // Reset team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        if (! $hasPermission) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                __('You are unauthorized to perform this action.')
            );
        }
    }

    /**
     * Check if the authenticated user has all of the given permissions.
     *
     * @param array $permissions
     * @param int|null $teamId
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeAll(array $permissions, ?int $teamId = null)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('You must be logged in.'));
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                // Reset team context before throwing
                if ($teamId) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
                }
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    __('You are unauthorized to perform this action.')
                );
            }
        }

        // Reset team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }
    }

    /**
     * Check if the authenticated user has a specific role.
     *
     * @param string|array $roles
     * @param int|null $teamId
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeRole($roles, ?int $teamId = null)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('You must be logged in.'));
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        $roles = is_array($roles) ? $roles : [$roles];
        $hasRole = false;

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        // Reset team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        if (! $hasRole) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                __('You do not have the required role to perform this action.')
            );
        }
    }

    /**
     * Check if the authenticated user has permission and belongs to the same organization.
     *
     * @param string $permission
     * @param int $organizationId
     * @param string $organizationType
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeOrganization(string $permission, int $organizationId, string $organizationType)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('You must be logged in.'));
        }

        // Check if user belongs to the same organization
        if ($user->organization_id !== $organizationId || $user->organization_type !== $organizationType) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                __('You cannot access resources from a different organization.')
            );
        }

        // Check permission with organization as team context
        $this->authorizeCheck($permission, $organizationId);
    }

    /**
     * Check if user can perform action (non-throwing version).
     *
     * @param string $permission
     * @param int|null $teamId
     * @return bool
     */
    public function canPerform(string $permission, ?int $teamId = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        $can = $user->can($permission);

        // Reset team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        return $can;
    }

    /**
     * Check if user has role (non-throwing version).
     *
     * @param string|array $roles
     * @param int|null $teamId
     * @return bool
     */
    public function hasRoleCheck($roles, ?int $teamId = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Use user's organization_id as team context if not explicitly provided
        $teamId = $teamId ?? $user->organization_id;

        // Set the team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        $roles = is_array($roles) ? $roles : [$roles];
        $hasRole = false;

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        // Reset team context
        if ($teamId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        return $hasRole;
    }
}