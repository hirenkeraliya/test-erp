<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByStoreListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $location = $this->resource->toArray();

        return [
            'id' => $location['location_id'],
            'name' => $location['name'],
            'code' => $location['code'],
            'goods_receive_note_in_balance' => $location['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $location['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $location['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $location['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $location['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $location['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $location['delivery_order_in_balance'],
            'delivery_order_out_balance' => $location['delivery_order_out_balance'],
            'sold' => $location['sold'] ? CommonFunctions::numberFormat((float) $location['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $location['balance']),
        ];
    }
}
