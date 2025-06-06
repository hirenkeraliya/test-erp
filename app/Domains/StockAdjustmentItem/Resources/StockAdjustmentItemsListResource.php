<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustmentItem\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentItemsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockAdjustmentItem $stockAdjustmentItem */
        $stockAdjustmentItem = $this;

        /** @var Location $location */
        $location = $stockAdjustmentItem->location;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);

        /** @var Product $product */
        $product = $stockAdjustmentItem->product;

        return [
            'id' => $stockAdjustmentItem->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'quantity' => $stockAdjustmentItem->quantity,
            'location' => $location->name . ' (' . $locationType . ')',
        ];
    }
}
