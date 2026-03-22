<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationApiPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'getAllPermissions')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!empty($user->organization_id)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->organization_id);
        }

        $permissions = collect($user->getAllPermissions())
            ->map(fn ($permission) => strtolower(trim((string) $permission->name)))
            ->filter()
            ->values();

        // Keep backward compatibility for organizations that have not configured RBAC yet.
        if ($permissions->isEmpty()) {
            return $next($request);
        }

        $resource = $this->resolveResource($request);
        if ($resource === null) {
            return $next($request);
        }

        // Lookup tables (not module-scoped in RBAC seed): allow any authenticated org user.
        if ($this->isUnrestrictedLookupRoute($request, $resource)) {
            return $next($request);
        }

        $action = $this->resolveAction($request->method());
        $candidates = $this->buildCandidatePermissions($resource, $action);

        if ($this->hasMatchingPermission($permissions->all(), $candidates, $resource, $action)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    private function resolveResource(Request $request): ?string
    {
        $path = trim((string) $request->path(), '/');
        $parts = explode('/', $path);

        // api/v1/{guard}/{resource?}
        if (count($parts) < 3) {
            return null;
        }

        return strtolower((string) ($parts[3] ?? 'dashboard'));
    }

    /**
     * Routes that are read-only reference data and are not tied to seeded module permissions
     * (e.g. clinic/specialties vs doctors.*).
     */
    private function isUnrestrictedLookupRoute(Request $request, string $resource): bool
    {
        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        return in_array($resource, ['specialties'], true);
    }

    private function resolveAction(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'manage',
        };
    }

    private function buildCandidatePermissions(string $resource, string $action): array
    {
        return array_values(array_unique([
            '*',
            'all',
            $resource . '.*',
            $resource . '.' . $action,
            $resource . ':' . $action,
            $action . ' ' . $resource,
            'manage ' . $resource,
        ]));
    }

    private function hasMatchingPermission(array $userPermissions, array $candidates, string $resource, string $action): bool
    {
        foreach ($userPermissions as $permission) {
            if (in_array($permission, $candidates, true)) {
                return true;
            }

            if (str_ends_with($permission, '.*')) {
                $prefix = substr($permission, 0, -2);
                if ($prefix !== '' && str_starts_with($resource, $prefix)) {
                    return true;
                }
            }

            if (
                str_starts_with($permission, $resource . '.') ||
                str_starts_with($permission, $resource . ':') ||
                str_contains($permission, ' ' . $resource)
            ) {
                return true;
            }

            if ($permission === $action) {
                return true;
            }
        }

        return false;
    }
}
