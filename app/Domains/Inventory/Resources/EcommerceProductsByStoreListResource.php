<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use App\Models\City;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceProductsByStoreListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $inventory = $this->resource;

        /** @var Location $location */
        $location = $inventory->location;

        /** @var ?City $city */
        $city = $location->getCity();

        /** @var Carbon $updatedAt */
        $updatedAt = $inventory->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $inventory->created_at;

        return [
            'id' => $location->id,
            'name' => $location->name,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city?->name,
            'area_code' => $location->area_code,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
