<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByColorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $color = $this->resource->toArray();

        return [
            'id' => $color['color_id'],
            'name' => $color['name'],
            'location_name' => $color['location_name'],
            'goods_receive_note_in_balance' => $color['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $color['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $color['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $color['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $color['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $color['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $color['delivery_order_in_balance'],
            'delivery_order_out_balance' => $color['delivery_order_out_balance'],
            'sold' => $color['sold'] ? CommonFunctions::numberFormat((float) $color['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $color['balance']),
        ];
    }
}
