<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
        $service = $this->service;

        return [
            'service_id' => $this->service_fee_id,
            'service_name' => $service?->service_name,
            'fee' => $this->fee,
            'price' => $this->fee,
            'notes' => $this->notes,

            $this->mergeWhen(
                $this->withFullData,
                function () {
                    return [
                       
                    ];
                }
            ),
          

        ];
    }
}
