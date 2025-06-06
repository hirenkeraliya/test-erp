<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Resources;

use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\HoldBookingPaymentItem;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\HoldSaleItem;
use App\Models\HoldSaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosHoldSaleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var HoldSale $holdSale */
        $holdSale = $this;

        /** @var Collection $holdSaleDetails */
        $holdSaleDetails = $holdSale->getHoldSaleDetails();

        return [
            'id' => $holdSale->getKey(),
            'offline_id' => $holdSale->offline_id,
            'cancelled_at' => $holdSale->cancelled_at ?: null,
            'complete_sale_id' => $holdSale->complete_sale_id ?: null,
            'complete_at' => $holdSale->complete_at ?: null,
            'type_id' => [
                'id' => $holdSale->type_id,
                'name' => HoldSaleTypes::getFormattedCaseName($holdSale->type_id),
                'key' => HoldSaleTypes::getCaseNameByValue($holdSale->type_id),
            ],
            'hold_sale_details' => $this->getPreparedHoldSaleDetails($holdSaleDetails),
        ];
    }

    private function getPreparedHoldSaleDetails(Collection $holdSaleDetails): Collection
    {
        return $holdSaleDetails->map(function ($item): array {
            /** @var HoldSaleDetail $holdSaleDetail */
            $holdSaleDetail = $item;

            /** @var HoldSaleItem $holdSaleItem */
            $holdSaleItem = $holdSaleDetail->getHoldSaleItem();

            /** @var HoldSaleReturnItem $holdSaleReturnItem */
            $holdSaleReturnItem = $holdSaleDetail->getHoldSaleReturnItem();

            /** @var HoldBookingPaymentItem $holdBookingPaymentItem */
            $holdBookingPaymentItem = $holdSaleDetail->getHoldBookingPaymentItem();

            $userDataPreparer = resolve(UserDataPreparer::class);

            return [
                'id' => $holdSaleDetail->getKey(),
                'user_type' => $userDataPreparer->getUserType($holdSaleDetail),
                'user_id' => $holdSaleDetail->member_id,
                'member_id' => $holdSaleDetail->member_id,
                'happened_at' => $holdSaleDetail->happened_at,
                'released_at' => $holdSaleDetail->released_at,
                'total_amount_paid' => (float) $holdSaleDetail->total_amount_paid,
                'total_tax_amount' => (float) $holdSaleDetail->total_tax_amount,
                'cart_discount_amount' => (float) $holdSaleDetail->cart_discount_amount,
                'items_discount_amount' => (float) $holdSaleDetail->items_discount_amount,
                'total_discount_amount' => (float) $holdSaleDetail->total_discount_amount,
                'is_layaway' => (float) $holdSaleDetail->is_layaway,
                'layaway_pending_amount' => (float) $holdSaleDetail->layaway_pending_amount,
                'is_credit_sale' => (float) $holdSaleDetail->is_credit_sale,
                'credit_pending_amount' => (float) $holdSaleDetail->credit_pending_amount,
                'round_off_amount' => (float) $holdSaleDetail->total_discount_amount,
                'change_due' => (float) $holdSaleDetail->change_due,
                'bill_reference_number' => $holdSaleDetail->bill_reference_number,
                'notes' => $holdSaleDetail->notes,
                'hold_sale_item' => $this->getPreparedHoldSaleItem($holdSaleItem),
                'hold_sale_return_item' => $this->getPreparedHoldSaleReturnItem($holdSaleReturnItem),
                'hold_booking_payment_item' => $this->getPreparedHoldBookingPaymentItem($holdBookingPaymentItem),
            ];
        });
    }

    private function getPreparedHoldSaleItem(?HoldSaleItem $holdSaleItem): array
    {
        if (! $holdSaleItem instanceof HoldSaleItem) {
            return [];
        }

        return [
            'id' => $holdSaleItem->getKey(),
            'product_id' => $holdSaleItem->product_id,
            'derivative_id' => $holdSaleItem->product_id,
            'quantity' => (float) $holdSaleItem->quantity,
            'original_sale_item_id' => (float) $holdSaleItem->returned_quantity,
            'returned_quantity' => (float) $holdSaleItem->returned_quantity,
            'original_price_per_unit' => (float) $holdSaleItem->original_price_per_unit,
            'cart_discount_amount' => (float) $holdSaleItem->cart_discount_amount,
            'item_discount_amount' => (float) $holdSaleItem->item_discount_amount,
            'total_discount_amount' => (float) $holdSaleItem->total_discount_amount,
            'total_tax_amount' => (float) $holdSaleItem->total_tax_amount,
            'price_paid_per_unit' => (float) $holdSaleItem->price_paid_per_unit,
            'total_price_paid' => (float) $holdSaleItem->total_price_paid,
            'group_id' => $holdSaleItem->group_id,
            'is_exchange' => $holdSaleItem->is_exchange,
        ];
    }

    private function getPreparedHoldSaleReturnItem(?HoldSaleReturnItem $holdSaleReturnItem): array
    {
        if (! $holdSaleReturnItem instanceof HoldSaleReturnItem) {
            return [];
        }

        return [
            'id' => $holdSaleReturnItem->getKey(),
            'hold_sale_detail_id' => $holdSaleReturnItem->hold_sale_detail_id,
            'sale_item_id' => $holdSaleReturnItem->sale_item_id,
            'product_id' => $holdSaleReturnItem->product_id,
            'sale_return_reason_id' => $holdSaleReturnItem->sale_return_reason_id,
            'quantity' => (float) $holdSaleReturnItem->quantity,
            'total_price_paid' => (float) $holdSaleReturnItem->total_price_paid,
            'cart_discount_amount' => (float) $holdSaleReturnItem->cart_discount_amount,
            'item_discount_amount' => (float) $holdSaleReturnItem->item_discount_amount,
            'total_discount_amount' => (float) $holdSaleReturnItem->total_discount_amount,
            'total_tax_amount' => (float) $holdSaleReturnItem->total_tax_amount,
        ];
    }

    private function getPreparedHoldBookingPaymentItem(?HoldBookingPaymentItem $holdBookingPaymentItem): array
    {
        if (! $holdBookingPaymentItem instanceof HoldBookingPaymentItem) {
            return [];
        }

        return [
            'id' => $holdBookingPaymentItem->getKey(),
            'hold_sale_detail_id' => $holdBookingPaymentItem->hold_sale_detail_id,
            'product_id' => $holdBookingPaymentItem->product_id,
            'quantity' => (float) $holdBookingPaymentItem->quantity,
        ];
    }
}
