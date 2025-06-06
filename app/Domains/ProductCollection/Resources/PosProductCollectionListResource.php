<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProductCollectionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productCollection = $this->resource;

        /** @var Carbon $createdAt */
        $createdAt = $productCollection->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $productCollection->updated_at;

        return [
            'id' => $productCollection->id,
            'name' => $productCollection->name,
            'status' => $productCollection->status ? 'Active' : 'Inactive',
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'product_ids' => $productCollection->productCollectionProducts->pluck('product_id')->toArray(),
        ];
    }
}
