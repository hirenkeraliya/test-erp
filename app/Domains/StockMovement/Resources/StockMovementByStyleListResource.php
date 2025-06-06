<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByStyleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $style = $this->resource->toArray();

        return [
            'id' => $style['style_id'],
            'name' => $style['name'],
            'location_name' => $style['location_name'],
            'goods_receive_note_in_balance' => $style['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $style['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $style['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $style['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $style['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $style['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $style['delivery_order_in_balance'],
            'delivery_order_out_balance' => $style['delivery_order_out_balance'],
            'sold' => $style['sold'] ? CommonFunctions::numberFormat((float) $style['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $style['balance']),
        ];
    }
}
