<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByCategoryListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $category = $this->resource->toArray();

        return [
            'id' => $category['category_id'],
            'name' => $category['name'],
            'location_name' => $category['location_name'],
            'goods_receive_note_in_balance' => $category['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $category['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $category['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $category['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $category['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $category['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $category['delivery_order_in_balance'],
            'delivery_order_out_balance' => $category['delivery_order_out_balance'],
            'sold' => $category['sold'] ? CommonFunctions::numberFormat((float) $category['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $category['balance']),
        ];
    }
}
