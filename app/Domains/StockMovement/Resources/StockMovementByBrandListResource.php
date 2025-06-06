<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByBrandListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $brand = $this->resource->toArray();

        return [
            'id' => $brand['brand_id'],
            'name' => $brand['name'],
            'location_name' => $brand['location_name'],
            'goods_receive_note_in_balance' => $brand['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $brand['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $brand['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $brand['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $brand['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $brand['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $brand['delivery_order_in_balance'],
            'delivery_order_out_balance' => $brand['delivery_order_out_balance'],
            'sold' => $brand['sold'] ? CommonFunctions::numberFormat((float) $brand['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $brand['balance']),
        ];
    }
}
