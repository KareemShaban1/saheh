<?php

namespace App\Http\Resources\Doctor;

use App\Models\Clinic;
use App\Models\Shared\PatientReview;
use App\Models\Scopes\OrganizationScope;
use App\Models\Settings;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    private bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $settingType = Settings::withoutGlobalScope(OrganizationScope::class)
        ->where('organization_type', Clinic::class)
        ->where('organization_id', $this->clinic_id)
        ->where('type', 'clinic_reservations_settings')
        ->pluck('value', 'key')['reservation_settings'] ?? null;

        return [
            'id'=>$this->id,
            'name' => $this->user->name,
            $this->mergeWhen(
                $this->withFullData,
                function () use($settingType) {
                    return [
                        'type' => $settingType=== 'slots' ? 'slots' : 'numbers',
                        'phone'=>$this->phone,
                        'certifications'=>$this->certifications,
                        // 'user'=>$this->user,
                        'clinic'=>$this->clinic,
                        'Services'=>$this->ServicesWithoutScope,
                        'patient_review' =>  PatientReview::
                        where('doctor_id', $this->id)
                            ->first() ?? null,
                        'reviews' => $this->reviews,
                        'reviews_statistics' => [
                            'reviews_count'=> $this->reviews->count(),
                            'one_star_reviews_count' => $this->reviews->where('rating',1)->count(),
                            'two_star_reviews_count' => $this->reviews->where('rating',2)->count(),
                            'three_star_reviews_count' => $this->reviews->where('rating',3)->count(),
                            'four_star_reviews_count' => $this->reviews->where('rating',4)->count(),
                            'five_star_reviews_count' => $this->reviews->where('rating',5)->count(),
                            'average_rating' => round($this->reviews->avg('rating'), 1),
                        ],

                    ];
                }
            ),

        ];
    }
}