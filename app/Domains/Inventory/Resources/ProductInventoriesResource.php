<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductInventoriesResource extends JsonResource
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

        return [
            'store_name' => $inventory->location?->name,
            'store_code' => $inventory->location?->code,
            'location_name' => $inventory->location?->name,
            'location_code' => $inventory->location?->code,
            'stock' => $inventory->stock,
        ];
    }
}
