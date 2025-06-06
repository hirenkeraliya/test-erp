<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByProductUpcListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $stockMovementAggregate = $this->resource;
        $productService = resolve(ProductService::class);

        return [
            'id' => $stockMovementAggregate->product_id,
            'name' => $stockMovementAggregate->name,
            'price' => $stockMovementAggregate->price ?? 0.00,
            'upc' => $stockMovementAggregate->upc,
            'color' => config('app.product_variant') ? null : ($stockMovementAggregate->color ?? 'N/A'),
            'size' => config('app.product_variant') ? null : ($stockMovementAggregate->size ?? 'N/A'),
            'location_name' => $stockMovementAggregate->location_name,
            'goods_receive_note_in_balance' => $stockMovementAggregate->goods_receive_note_in_balance,
            'goods_receive_note_out_balance' => $stockMovementAggregate->goods_receive_note_out_balance,
            'stock_adjustment_in_balance' => $stockMovementAggregate->stock_adjustment_in_balance,
            'stock_adjustment_out_balance' => $stockMovementAggregate->stock_adjustment_out_balance,
            'stock_transfer_in_balance' => $stockMovementAggregate->stock_transfer_in_balance,
            'stock_transfer_out_balance' => $stockMovementAggregate->stock_transfer_out_balance,
            'delivery_order_in_balance' => $stockMovementAggregate->delivery_order_in_balance,
            'delivery_order_out_balance' => $stockMovementAggregate->delivery_order_out_balance,
            'sold' => $stockMovementAggregate->sold
                ? CommonFunctions::numberFormat((float) $stockMovementAggregate->sold)
                : 0,
            'balance' => CommonFunctions::numberFormat((float) $stockMovementAggregate->balance),
            'attributes' => $productService->getAttributesWithNameAndValueKey($stockMovementAggregate->product),
        ];
    }
}
