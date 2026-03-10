<?php

namespace App\Http\Resources\Reservation;

use App\Http\Resources\ChronicDisease\ChronicDiseaseCollection;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\Service\ServiceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'id' => $this->id,
            'patient' => (new PatientResource($this->patient))->withFullData($this->withFullData),
            // 'services' => $this->Services,
            'services'=>(new ServiceCollection($this->Services))->withFullData($this->withFullData),
            

            $this->mergeWhen(
                $this->withFullData,
                function () {
                    return [
                        'clinic' => $this->whenLoaded('clinic'),
		    'doctor' => $this->whenLoaded('doctor'),
                        'chronic_diseases' => (new ChronicDiseaseCollection($this->chronicDisease))->withFullData($this->withFullData),
                        'rays' => $this->rays,
                        'drugs' => $this->drugs,
                        'glasses_distance' => $this->glassesDistance,
                        'prescription' => $this->prescription,
                        'first_diagnosis' => $this->first_diagnosis,
                        'final_diagnosis' => $this->final_diagnosis,
                        'reservation_status' => $this->status,
                        'acceptance' => $this->acceptance,
                    ];
                }
            ),
            'reservation_number' => (string) $this->reservation_number,
			'slot' => (string) $this->slot,
            // 'reservation_number' => (string) $this->id,
            'date' => $this->date,
            'reservation_type' => $this->type,
            'cost' => $this->cost,
            'payment' => $this->payment,

        ];
    }
}
