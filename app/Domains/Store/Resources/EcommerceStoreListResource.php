<?php

declare(strict_types=1);

namespace App\Domains\Store\Resources;

use App\Models\City;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceStoreListResource extends JsonResource
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

        /** @var Carbon $updatedAt */
        $updatedAt = $location->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $location->created_at;

        /** @var Country $country */
        $country = $location->country;

        /** @var City $city */
        $city = $location->city;

        return [
            'id' => $location->id,
            'name' => $location->name,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city->name ?? null,
            'country' => $country->name,
            'area_code' => $location->area_code,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
