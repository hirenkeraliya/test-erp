<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use App\CommonFunctions;
use App\Models\Inventory;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreWiseProductStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Inventory $inventory */
        $inventory = $this;

        /** @var Location $location */
        $location = $inventory->location;

        return [
            'store_id' => $location->id,
            'location_id' => $location->id,
            'store_name' => $location->name,
            'location_name' => $location->name,
            'stock' => CommonFunctions::truncateDecimal((float) $inventory->stock),
        ];
    }
}
