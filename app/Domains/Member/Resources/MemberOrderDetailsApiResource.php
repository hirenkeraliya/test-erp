<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Product\Enums\ProductTypes;
use App\Models\Color;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MemberOrderDetailsApiResource extends JsonResource
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

        /** @var Collection $orderPayments */
        $orderPayments = $order->payments;

        /** @var Collection $orderItems */
        $orderItems = $order->orderItems;

        /** @var Collection $orderChannelReference */
        $orderChannelReference = $order->orderChannelReference;

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
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'payments' => $this->getPreparedOrderPayments($orderPayments),
            'status' => $order->status,
            'status_name' => OrderStatus::getCaseNameByValue($order->status->value),
            'external_order_receipt_number' => $orderChannelReference->external_order_id ?? null,
        ];
    }

    private function getPreparedOrderItems(Collection $orderItems): Collection
    {
        return $orderItems->map(function ($item): array {
            /** @var OrderItem $orderItem */
            $orderItem = $item;

            /** @var Product $product */
            $product = $orderItem->product;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            return [
                'id' => $orderItem->getKey(),
                'product_id' => $orderItem->product_id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'article_number' => $product->article_number,
                    'type_id' => [
                        'id' => $product->type_id,
                        'name' => ProductTypes::getFormattedCaseName($product->type_id),
                        'key' => ProductTypes::getCaseNameByValue($product->type_id),
                    ],
                    'upc' => $product->upc,
                    'color' => $color,
                    'size' => $size,
                ],
                'quantity' => (float) $orderItem->quantity,
                'cart_discount_amount' => (float) $orderItem->cart_discount_amount,
                'item_discount_amount' => (float) $orderItem->item_discount_amount,
                'total_discount_amount' => (float) $orderItem->total_discount_amount,
                'item_tax_amount' => (float) $orderItem->item_tax_amount,
                'price_paid_per_unit' => (float) $orderItem->price_paid_per_unit,
                'total_price_paid' => $orderItem->total_price_paid,
            ];
        });
    }

    private function getPreparedOrderPayments(Collection $orderPayments): Collection
    {
        return $orderPayments->map(function ($payment): array {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $payment;

            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->paymentType;

            return [
                'id' => $orderPayment->getKey(),
                'payment_type' => $paymentType,
                'amount' => (float) $orderPayment->amount,
            ];
        });
    }
}
