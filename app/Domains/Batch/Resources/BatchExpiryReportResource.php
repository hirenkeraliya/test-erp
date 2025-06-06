<?php

declare(strict_types=1);

namespace App\Domains\Batch\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class BatchExpiryReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Batch $batch */
        $batch = $this;

        /** @var Product $product */
        $product = $batch->product;

        /** @var Collection $categories */
        $categories = config('app.product_variant') ? $product?->masterProduct?->categories ?? collect([
        ]) : $product->categories;

        /** @var Brand $brand */
        $brand = config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;

        $inventoryUnit = $batch->inventoryUnit;

        $inventory = null;
        if ($inventoryUnit) {
            $inventory = $inventoryUnit->inventory;
        }

        $location = null;
        if ($inventory) {
            $location = $inventory->location;
        }

        return [
            'location' => $location ? LocationTypes::getFormattedCaseName(
                $location->type_id
            ) . ' : ' . $location->name : '',
            'supplier' => $brand->name,
            'product' => $product->name,
            'categories' => $categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'upc' => $product->upc,
            'batch' => $batch->number,
            'quantity' => $inventoryUnit?->quantity,
            'expired_by' => $batch->expiry_date,
            'is_expired' => $batch->expiry_date <= now()->format('Y-m-d'),
            'is_expired_soon' => now()->addDays(30)->format('Y-m-d') >= $batch->expiry_date,
        ];
    }
}
