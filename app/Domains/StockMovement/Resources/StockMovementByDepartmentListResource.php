<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementByDepartmentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $department = $this->resource->toArray();

        return [
            'id' => $department['department_id'],
            'name' => $department['name'],
            'location_name' => $department['location_name'],
            'goods_receive_note_in_balance' => $department['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $department['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $department['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $department['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $department['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $department['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $department['delivery_order_in_balance'],
            'delivery_order_out_balance' => $department['delivery_order_out_balance'],
            'sold' => $department['sold'] ? CommonFunctions::numberFormat((float) $department['sold']) : 0,
            'balance' => CommonFunctions::numberFormat((float) $department['balance']),
        ];
    }
}
