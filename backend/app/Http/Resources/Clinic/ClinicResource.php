<?php

namespace App\Http\Resources\Clinic;

use App\Http\Resources\Patient\PatientResource;
use App\Models\Clinic;
use App\Models\Shared\PatientReview;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
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
        return [


            $this->mergeWhen(
                $this->withFullData,
                function () {
                    return [
                        'id' => $this->id,
                        'name' => $this->name,
                        'start_date' => $this->start_date,
                        'address' => $this->address,
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'description' => $this->description,
                        'status' => $this->status,
                        "latitude" => $this->latitude,
                        "longitude" => $this->longitude,
                        'governorate' => $this->governorate,
                        'city' => $this->city,
                        'area' => $this->area,
                        'doctors' => $this->doctors,
                        'services' => $this->Services,
                        'patient_review' =>  PatientReview::where('organization_id', $this->id)
                            ->where('organization_type', Clinic::class)
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
