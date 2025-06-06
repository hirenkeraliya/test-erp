<?php

declare(strict_types=1);

namespace App\Domains\Location\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationListResource extends JsonResource
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

        /** @var Region $region */
        $region = $location->region;

        /** @var City $city */
        $city = $location->city;

        return [
            'id' => $location->getKey(),
            'name' => $location->name,
            'type' => LocationTypes::getFormattedCaseName($location->type_id),
            'code' => $location->code,
            'phone' => $location->phone,
            'email' => $location->email,
            'city' => $city->name ?? 'N/A',
            'region' => $region->name ?? 'N/A',
            'is_email_verified' => $location->is_email_verified,
        ];
    }
}
