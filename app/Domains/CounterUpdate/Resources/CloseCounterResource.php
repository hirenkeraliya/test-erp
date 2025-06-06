<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Models\CloseCounterPayment;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\PaymentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CloseCounterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $this;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();
        $counter['store_id'] = $counter->location_id;

        $openedAt = '';

        if ($counterUpdate->opened_by_pos_at) {
            /** @var Carbon $openedAtFormat */
            $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->opened_by_pos_at);
            $openedAt = $openedAtFormat->format('Y-m-d H:i:s');
        }

        /** @var Carbon|string $closedAt */
        $closedAt = 'N/A';

        if ($counterUpdate->closed_at) {
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->closed_at);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
        }

        /** @var Collection $payments */
        $payments = $counterUpdate->getPayments();

        return [
            'id' => $counterUpdate->id,
            'counter' => $counter,
            'cashier_id' => $counterUpdate->cashier_id,
            'opening_balance' => (float) $counterUpdate->opening_balance,
            'closing_balance' => (float) $counterUpdate->closing_balance,
            'mismatch_amount' => (float) $counterUpdate->mismatch_amount,
            'reason' => $counterUpdate->amount_mismatch_reason,
            'opening_date_time' => $openedAt,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'total_sales' => $counterUpdate->total_sales,
            'total_sales_amount' => (float) $counterUpdate->total_sales_amount,
            'total_layaway_sales' => $counterUpdate->total_layaway_sales,
            'total_layaway_sales_amount' => (float) $counterUpdate->total_layaway_sales_amount,
            'total_credit_sales' => $counterUpdate->total_credit_sales,
            'total_credit_sales_amount' => (float) $counterUpdate->total_credit_sales_amount,
            'total_voided_sales' => $counterUpdate->total_voided_sales,
            'total_voided_sales_amount' => (float) $counterUpdate->total_voided_sales_amount,
            'total_item_wise_discount_amount' => (float) $counterUpdate->total_item_wise_discount_amount,
            'total_cart_wide_discount_amount' => (float) $counterUpdate->total_cart_wide_discount_amount,
            'total_tax_amount' => (float) $counterUpdate->total_tax_amount,
            'total_sales_round_off' => (float) $counterUpdate->total_sales_round_off,
            'total_sale_returns' => $counterUpdate->total_sale_returns,
            'total_sale_returns_amount' => (float) $counterUpdate->total_sale_returns_amount,
            'total_credit_notes_used_amount' => (float) $counterUpdate->total_credit_notes_used_amount,
            'total_credit_notes_used' => $counterUpdate->total_credit_notes_used,
            'total_credit_notes_refunded_amount' => (float) $counterUpdate->total_credit_notes_refunded_amount,
            'total_credit_notes_refunded' => $counterUpdate->total_credit_notes_refunded,
            'total_sale_returns_round_off' => (float) $counterUpdate->total_sale_returns_round_off,
            'total_cashback_amount' => (float) $counterUpdate->total_cashback_amount,
            'total_vouchers_used' => $counterUpdate->total_vouchers_used,
            'total_voucher_discount_amount' => (float) $counterUpdate->total_voucher_discount_amount,
            'total_vouchers_generated' => $counterUpdate->total_vouchers_generated,
            'total_sale_promotion_used' => $counterUpdate->total_sale_promotion_used,
            'total_sale_promotion_discount_amount' => (float) $counterUpdate->total_sale_promotion_discount_amount,
            'total_sale_item_promotion_used' => $counterUpdate->total_sale_item_promotion_used,
            'total_sale_item_promotion_discount_amount' => (float) $counterUpdate->total_sale_item_promotion_discount_amount,
            'total_dream_price_used' => $counterUpdate->total_dream_price_used,
            'total_dream_price_discount_amount' => (float) $counterUpdate->total_dream_price_discount_amount,
            'total_complimentary_item_discount_used' => (float) $counterUpdate->total_complimentary_item_discount_used,
            'total_complimentary_item_discount_amount' => (float) $counterUpdate->total_complimentary_item_discount_amount,
            'total_price_override_used' => (float) $counterUpdate->total_price_override_used,
            'total_price_override_discount_amount' => (float) $counterUpdate->total_price_override_discount_amount,
            'total_booking_payment_amount' => (float) $counterUpdate->total_booking_payment_amount,
            'total_booking_payment_refunded_amount' => (float) $counterUpdate->total_booking_payment_refunded_amount,
            'total_booking_payment_used_amount' => (float) $counterUpdate->total_booking_payment_used_amount,
            'total_cash_ins_amount' => (float) $counterUpdate->total_cash_ins_amount,
            'total_cash_outs_amount' => (float) $counterUpdate->total_cash_outs_amount,
            'total_new_booking_payments' => $counterUpdate->total_new_booking_payments,
            'total_used_booking_payments' => $counterUpdate->total_new_booking_payments,
            'total_cancel_layaway_sales' => $counterUpdate->total_cancel_layaway_sales,
            'total_cancel_layaway_sales_amount' => $counterUpdate->total_cancel_layaway_sales_amount,
            'sale_payments' => $this->getPreparedPayments($payments),
        ];
    }

    private function getPreparedPayments(Collection $payments): Collection
    {
        return $payments->map(function ($item): array {
            /** @var CloseCounterPayment $closeCounterPayment */
            $closeCounterPayment = $item;
            /** @var PaymentType $paymentType */
            $paymentType = $closeCounterPayment->paymentType;

            return [
                'payment_type_id' => $closeCounterPayment->payment_type_id,
                'payment_type' => $paymentType->name,
                'total' => (float) $closeCounterPayment->total_amount,
            ];
        });
    }
}
