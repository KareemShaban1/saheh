<?php

namespace App\Http\Controllers\FrontApis\clinic;

use Illuminate\Http\Request;
use App\Models\Service;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\ServiceInstruction;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\BaseFrontApiController;
class ServiceController extends BaseFrontApiController
{

    /**
     * Services page data
     */
    public function services(Request $request)
    {
        $this->ensureClinicAuth();
        $query = Service::query()->with(['doctor.user', 'serviceInstructions'])->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->service_name,
            'category' => $s->type ?? 'General',
            'price' => (string) ($s->price ?? 0),
            'duration' => null,
            'status' => 'active',
            'doctor_id' => $s->doctor_id,
            'doctor_name' => $s->doctor?->user?->name ?? null,
            'notes' => $s->notes,
            'service_instructions' => $s->serviceInstructions->map(fn ($i) => [
                'id' => $i->id,
                'instructions' => $i->instructions,
                'type' => $i->type,
                'notes' => $i->notes,
            ])->values(),
            'service_instructions_count' => $s->serviceInstructions->count(),
        ]);

        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Services', 'success');
    }

    /**
     * Single service details
     */
    public function serviceDetails($id)
    {
        $this->ensureClinicAuth();

        $service = Service::query()
            ->with(['doctor.user:id,name', 'serviceInstructions'])
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $service->id,
            'service_name' => $service->service_name,
            'doctor_id' => $service->doctor_id,
            'doctor_name' => $service->doctor?->user?->name,
            'price' => (float) ($service->price ?? 0),
            'type' => $service->type,
            'notes' => $service->notes,
            'service_instructions' => $service->serviceInstructions->map(fn ($i) => [
                'id' => $i->id,
                'instructions' => $i->instructions,
                'type' => $i->type,
                'notes' => $i->notes,
            ])->values(),
        ], 'Service details', 'success');
    }

    /**
     * Create clinic service
     */
    public function createService(Request $request)
    {
        $this->ensureClinicAuth();

        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'doctor_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:main,sub',
            'notes' => 'nullable|string|max:2000',
            'service_instructions' => 'nullable|array',
            'service_instructions.*.instructions' => 'nullable|string|max:5000',
            'service_instructions.*.type' => 'nullable|in:pre,post',
            'service_instructions.*.notes' => 'nullable|string|max:5000',
        ]);

        if (!empty($validated['doctor_id'])) {
            $doctorExists = Doctor::query()->where('id', $validated['doctor_id'])->exists();
            if (!$doctorExists) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['Selected doctor is invalid for this clinic.'],
                ]);
            }
        }

        $instructions = $this->normalizeServiceInstructions($validated['service_instructions'] ?? []);
        $service = DB::transaction(function () use ($validated, $instructions) {
            $createdService = Service::create([
                'service_name' => $validated['service_name'],
                'doctor_id' => $validated['doctor_id'] ?? null,
                'price' => $validated['price'],
                'type' => $validated['type'],
                'notes' => $validated['notes'] ?? null,
                'organization_id' => request()->user()->organization_id,
                'organization_type' => request()->user()->organization_type,
            ]);

            $this->syncServiceInstructions($createdService, $instructions);

            return $createdService;
        });

        return $this->returnJSON(['id' => $service->id], 'Service created', 'success');
    }

    /**
     * Update clinic service
     */
    public function updateService(Request $request, $id)
    {
        $this->ensureClinicAuth();

        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'doctor_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:main,sub',
            'notes' => 'nullable|string|max:2000',
            'service_instructions' => 'nullable|array',
            'service_instructions.*.instructions' => 'nullable|string|max:5000',
            'service_instructions.*.type' => 'nullable|in:pre,post',
            'service_instructions.*.notes' => 'nullable|string|max:5000',
        ]);

        if (!empty($validated['doctor_id'])) {
            $doctorExists = Doctor::query()->where('id', $validated['doctor_id'])->exists();
            if (!$doctorExists) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['Selected doctor is invalid for this clinic.'],
                ]);
            }
        }

        $service = Service::query()->findOrFail($id);
        $instructions = $this->normalizeServiceInstructions($validated['service_instructions'] ?? []);
        DB::transaction(function () use ($service, $validated, $instructions) {
            $service->update([
                'service_name' => $validated['service_name'],
                'doctor_id' => $validated['doctor_id'] ?? null,
                'price' => $validated['price'],
                'type' => $validated['type'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncServiceInstructions($service, $instructions);
        });

        return $this->returnJSON(['id' => $service->id], 'Service updated', 'success');
    }
 

    /**
     * Service instructions for reservation modal
     */
    public function serviceInstructions($serviceId)
    {
        $this->ensureClinicAuth();
        $instructions = ServiceInstruction::query()
            ->where('service_id', $serviceId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'instructions' => $i->instructions,
                'type' => $i->type,
                'notes' => $i->notes,
            ]);

        return $this->returnJSON($instructions, 'Service instructions', 'success');
    }

    private function normalizeServiceInstructions(array $items): array
    {
        return collect($items)
            ->filter(function ($item) {
                if (!is_array($item)) {
                    return false;
                }

                $instruction = (string) ($item['instruction'] ?? $item['instructions'] ?? '');
                return trim($instruction) !== '';
            })
            ->map(fn ($item) => [
                'instructions' => trim((string) ($item['instructions'] ?? '')),
                'type' => in_array(($item['type'] ?? 'pre'), ['pre', 'post'], true) ? $item['type'] : 'pre',
                'notes' => isset($item['notes']) && trim((string) $item['notes']) !== '' ? trim((string) $item['notes']) : null,
            ])
            ->values()
            ->all();
    }

    private function syncServiceInstructions(Service $service, array $instructions): void
    {
        $service->serviceInstructions()->delete();

        if (!empty($instructions)) {
            $service->serviceInstructions()->createMany($instructions);
        }
    }
}
