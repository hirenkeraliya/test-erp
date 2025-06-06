<?php

declare(strict_types=1);

namespace App\Domains\Location\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetailPlanningLocationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $location = $this->resource;

        return [
            'id' => $location->getKey(),
            'company_id' => $location->company_id,
            'name' => $location->name,
            'code' => $location->code,
            'phone' => $location->phone,
            'email' => $location->email,
            'city_id' => $location->city_id,
            'state_id' => $location->state_id,
            'country_id' => $location->country_id,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'area_code' => $location->area_code,
            'brands' => $location->brands->pluck('id')->toArray(),
            'region_id' => $location->region_id,
        ];
    }
}
