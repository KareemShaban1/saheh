<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $expected = match ($type) {
            'clinic' => Clinic::class,
            'medical_laboratory' => MedicalLaboratory::class,
            'radiology_center' => RadiologyCenter::class,
            default => null,
        };
        if (!$expected || $user->organization_type !== $expected) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}
