<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementBySizeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $size = $this->resource->toArray();

        return [
            'id' => $size['size_id'],
            'name' => $size['name'],
            'location_name' => $size['location_name'],
            'goods_receive_note_in_balance' => $size['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $size['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $size['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $size['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $size['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $size['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $size['delivery_order_in_balance'],
            'delivery_order_out_balance' => $size['delivery_order_out_balance'],
            'sold' => $size['sold'] ? CommonFunctions::numberFormat((float) $size['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $size['balance']),
        ];
    }
}
