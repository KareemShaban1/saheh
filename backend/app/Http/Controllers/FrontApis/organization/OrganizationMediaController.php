<?php

namespace App\Http\Controllers\FrontApis\organization;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\OrganizationMedia;
use App\Models\RadiologyCenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Clinic\Doctor\Models\Doctor;

class OrganizationMediaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = OrganizationMedia::query()
            ->where('is_active', true)
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        $targetType = $request->query('target_type');
        $targetId = $request->query('target_id');

        if ($targetType && $targetId) {
            [$ownerType, $ownerId] = $this->resolveOwnedTarget($user->organization_type, (int) $user->organization_id, (string) $targetType, (int) $targetId);
            $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
        } else {
            $query->where('owner_type', $user->organization_type)->where('owner_id', (int) $user->organization_id);
        }

        if ($request->filled('media_type')) {
            $query->where('media_type', $request->query('media_type'));
        }

        return response()->json([
            'status' => true,
            'data' => $query->get()->map(fn (OrganizationMedia $item) => $this->transform($item)),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'media_type' => ['required', Rule::in(['reel', 'video', 'story'])],
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:102400',
            'duration_seconds' => 'nullable|integer|min:0|max:86400',
            'sort_order' => 'nullable|integer|min:0|max:100000',
            'target_type' => ['nullable', Rule::in(['organization', 'doctor'])],
            'target_id' => 'nullable|integer|min:1',
        ]);

        $targetType = $validated['target_type'] ?? 'organization';
        $targetId = (int) ($validated['target_id'] ?? $user->organization_id);
        [$ownerType, $ownerId] = $this->resolveOwnedTarget($user->organization_type, (int) $user->organization_id, $targetType, $targetId);

        $path = $request->file('file')->store('organization-media', 'public');

        $record = OrganizationMedia::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'media_type' => $validated['media_type'],
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'mime_type' => $request->file('file')->getMimeType(),
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Media uploaded successfully.',
            'data' => $this->transform($record),
        ], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $item = OrganizationMedia::query()->findOrFail($id);

        $isOwnerOrg = $item->owner_type === $user->organization_type && (int) $item->owner_id === (int) $user->organization_id;
        $isOwnedDoctor = $user->organization_type === Clinic::class
            && $item->owner_type === Doctor::class
            && Doctor::query()->where('id', $item->owner_id)->where('organization_id', $user->organization_id)->exists();

        abort_unless($isOwnerOrg || $isOwnedDoctor, 403, 'You are not allowed to delete this media.');

        $item->delete();

        return response()->json([
            'status' => true,
            'message' => 'Media deleted successfully.',
        ]);
    }

    private function resolveOwnedTarget(string $organizationType, int $organizationId, string $targetType, int $targetId): array
    {
        if ($targetType === 'organization') {
            abort_unless($targetId === $organizationId, 403, 'Invalid organization target.');
            return [$organizationType, $organizationId];
        }

        abort_unless($targetType === 'doctor', 422, 'Invalid target type.');
        abort_unless($organizationType === Clinic::class, 403, 'Only clinics can upload doctor media.');

        $doctor = Doctor::query()
            ->where('id', $targetId)
            ->where('organization_id', $organizationId)
            ->first();

        abort_unless($doctor, 404, 'Doctor not found.');

        return [Doctor::class, (int) $doctor->id];
    }

    private function transform(OrganizationMedia $item): array
    {
        return [
            'id' => $item->id,
            'owner_type' => $item->owner_type,
            'owner_id' => $item->owner_id,
            'media_type' => $item->media_type,
            'title' => $item->title,
            'description' => $item->description,
            'file_path' => $item->file_path,
            'file_url' => url('storage/' . ltrim($item->file_path, '/')),
            'mime_type' => $item->mime_type,
            'duration_seconds' => $item->duration_seconds,
            'sort_order' => $item->sort_order,
            'created_at' => $item->created_at,
        ];
    }
}

