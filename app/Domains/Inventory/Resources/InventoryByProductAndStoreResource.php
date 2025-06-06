<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryByProductAndStoreResource extends JsonResource
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

        /** @var Product $product */
        $product = $inventory->product;

        return [
            'id' => $product->id,
            'upc' => $product->upc,
            'name' => $product->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'location_code' => $location->code,
        ];
    }
}
