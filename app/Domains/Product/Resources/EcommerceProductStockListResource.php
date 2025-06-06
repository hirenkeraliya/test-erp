<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceProductStockListResource extends JsonResource
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

        /** @var Carbon $updatedAt */
        $updatedAt = $inventory->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $inventory->created_at;

        return [
            'id' => $inventory->product_id,
            'product_id' => $inventory->product_id,
            'stock' => $inventory->stock,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
