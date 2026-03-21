<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMedia;
use App\Models\OrganizationMediaInteraction;
use Illuminate\Http\Request;

class ReelController extends Controller
{
    public function feed(Request $request)
    {
        $patient = $request->user();
        $limit = max(1, min((int) $request->get('limit', 40), 100));

        $items = OrganizationMedia::query()
            ->where('media_type', 'reel')
            ->where('is_active', true)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (OrganizationMedia $item) use ($patient) {
                $interaction = OrganizationMediaInteraction::query()
                    ->where('organization_media_id', $item->id)
                    ->where('patient_id', $patient->id)
                    ->first();

                return [
                    'id' => $item->id,
                    'owner_type' => $item->owner_type,
                    'owner_id' => $item->owner_id,
                    'media_type' => $item->media_type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'file_url' => url('storage/' . ltrim($item->file_path, '/')),
                    'mime_type' => $item->mime_type,
                    'duration_seconds' => $item->duration_seconds,
                    'likes_count' => OrganizationMediaInteraction::query()->where('organization_media_id', $item->id)->where('liked', true)->count(),
                    'saves_count' => OrganizationMediaInteraction::query()->where('organization_media_id', $item->id)->where('saved', true)->count(),
                    'liked_by_me' => (bool) ($interaction?->liked ?? false),
                    'saved_by_me' => (bool) ($interaction?->saved ?? false),
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function toggleLike(Request $request, int $id)
    {
        $patient = $request->user();
        $media = OrganizationMedia::query()->where('id', $id)->where('media_type', 'reel')->firstOrFail();

        $interaction = OrganizationMediaInteraction::query()->firstOrCreate(
            [
                'organization_media_id' => $media->id,
                'patient_id' => $patient->id,
            ],
            [
                'liked' => false,
                'saved' => false,
            ],
        );

        $interaction->liked = !$interaction->liked;
        $interaction->save();

        return response()->json([
            'status' => true,
            'liked' => (bool) $interaction->liked,
            'likes_count' => OrganizationMediaInteraction::query()->where('organization_media_id', $media->id)->where('liked', true)->count(),
        ]);
    }

    public function toggleSave(Request $request, int $id)
    {
        $patient = $request->user();
        $media = OrganizationMedia::query()->where('id', $id)->where('media_type', 'reel')->firstOrFail();

        $interaction = OrganizationMediaInteraction::query()->firstOrCreate(
            [
                'organization_media_id' => $media->id,
                'patient_id' => $patient->id,
            ],
            [
                'liked' => false,
                'saved' => false,
            ],
        );

        $interaction->saved = !$interaction->saved;
        $interaction->save();

        return response()->json([
            'status' => true,
            'saved' => (bool) $interaction->saved,
            'saves_count' => OrganizationMediaInteraction::query()->where('organization_media_id', $media->id)->where('saved', true)->count(),
        ]);
    }
}

