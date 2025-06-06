<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Order\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberOrderListApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $order = $this->resource;

        return [
            'id' => $order->getKey(),
            'offline_order_id' => $order->receipt_number,
            'member_id' => $order->member_id,
            'total_tax_amount' => (float) $order->total_tax_amount,
            'cart_discount_amount' => (float) $order->cart_discount_amount,
            'item_discount_amount' => (float) $order->item_discount_amount,
            'total_discount_amount' => (float) $order->total_discount_amount,
            'total_amount_paid' => (float) $order->total_amount_paid,
            'total_amount_before_round_off' => (float) $order->total_amount_before_round_off,
            'happened_at' => $order->happened_at,
            'order_notes' => $order->notes,
            'bill_reference_number' => $order->bill_reference_number,
            'round_off_amount' => (float) $order->round_off,
            'layaway_pending_amount' => (float) $order->layaway_pending_amount,
            'credit_pending_amount' => (float) $order->credit_pending_amount,
            'layaway_completed_at' => (float) $order->layaway_completed_at,
            'credit_completed_at' => (float) $order->credit_completed_at,
            'delivery_charges' => (float) $order->delivery_charges,
            'status' => OrderStatus::getCaseNameByValue($order->getStatus()->value),
            'order_items' => $order->order_items_count,
        ];
    }
}
