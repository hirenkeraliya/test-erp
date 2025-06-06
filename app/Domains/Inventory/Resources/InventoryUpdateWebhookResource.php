<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryUpdateWebhookResource extends JsonResource
{
    public function toArray($request): array
    {
        $inventory = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $inventory->updated_at;

        return [
            'id' => $inventory->product_id,
            'product_id' => $inventory->product_id,
            'stock' => $inventory->stock,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
