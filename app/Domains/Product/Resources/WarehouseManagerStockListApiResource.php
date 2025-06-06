<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseManagerStockListApiResource extends JsonResource
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

        /** @var Product $product */
        $product = $inventory->product;

        return [
            'id' => $inventory->id,
            'name' => $product->name,
            'stock' => $inventory->stock,
        ];
    }
}
