<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\CounterUpdate;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StoreManagerAppClosedCounterDetailsResource extends JsonResource
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

        /** @var Collection $payments */
        $payments = $counterUpdate->getPayments();

        /** @var Collection $denominations */
        $denominations = $counterUpdate->denominations;

        $payments = $this->getPreparedPayments($payments);

        return [
            'mismatch_amount' => $counterUpdate->getMismatchAmount(),
            'amount_mismatch_reason' => $counterUpdate->getAmountMismatchReason(),
            'sales_collection_amount' => $counterUpdate->sales_collection_amount,
            'opening_balance' => $counterUpdate->opening_balance,
            'closing_balance' => $counterUpdate->closing_balance,
            'total_sales' => $counterUpdate->total_sales,
            'total_sales_amount' => $counterUpdate->total_sales_amount,
            'total_layaway_sales' => $counterUpdate->total_layaway_sales,
            'total_layaway_sales_amount' => $counterUpdate->total_layaway_sales_amount,
            'total_credit_sales' => $counterUpdate->total_credit_sales,
            'total_credit_sales_amount' => $counterUpdate->total_credit_sales_amount,
            'total_voided_sales' => $counterUpdate->total_voided_sales,
            'total_voided_sales_amount' => $counterUpdate->total_voided_sales_amount,
            'total_item_wise_discount_amount' => $counterUpdate->total_item_wise_discount_amount,
            'total_cart_wide_discount_amount' => $counterUpdate->total_cart_wide_discount_amount,
            'total_discount_amount' => $counterUpdate->total_item_wise_discount_amount + $counterUpdate->total_cart_wide_discount_amount,
            'total_tax_amount' => $counterUpdate->total_tax_amount,
            'total_sales_round_off' => $counterUpdate->total_sales_round_off,
            'total_sale_returns' => $counterUpdate->total_sale_returns,
            'total_sale_returns_amount' => $counterUpdate->total_sale_returns_amount,
            'total_credit_notes_used_amount' => $counterUpdate->total_credit_notes_used_amount,
            'total_credit_notes_used' => $counterUpdate->total_credit_notes_used,
            'total_credit_notes_refunded_amount' => $counterUpdate->total_credit_notes_refunded_amount,
            'total_credit_notes_refunded' => $counterUpdate->total_credit_notes_refunded,
            'total_sale_returns_round_off' => $counterUpdate->total_sale_returns_round_off,
            'total_cashback' => $counterUpdate->total_cashback,
            'total_cashback_amount' => $counterUpdate->total_cashback_amount,
            'total_vouchers_used' => $counterUpdate->total_vouchers_used,
            'total_vouchers_generated' => $counterUpdate->total_vouchers_generated,
            'total_booking_payment_amount' => $counterUpdate->total_booking_payment_amount,
            'total_booking_payment_refunded_amount' => $counterUpdate->total_booking_payment_refunded_amount,
            'total_booking_payment_used_amount' => $counterUpdate->total_booking_payment_used_amount,
            'total_cash_ins_amount' => $counterUpdate->total_cash_ins_amount,
            'total_cash_outs_amount' => $counterUpdate->total_cash_outs_amount,
            'total_cash_amount_in_sales' => $counterUpdate->total_cash_amount_in_sales,
            'total_cash_amount_in_booking_payment' => $counterUpdate->total_cash_amount_in_booking_payment,
            'total_cash_amount_in_booking_payment_refunded' => $counterUpdate->total_cash_amount_in_booking_payment_refunded,
            'total_cash_amount_in_credit_note_refunded' => $counterUpdate->total_cash_amount_in_credit_note_refunded,
            'total_new_booking_payments' => $counterUpdate->total_new_booking_payments,
            'total_used_booking_payments' => $counterUpdate->total_used_booking_payments,
            'total_cancel_layaway_sales' => $counterUpdate->total_cancel_layaway_sales,
            'total_cancel_layaway_sales_amount' => $counterUpdate->total_cancel_layaway_sales_amount,
            'denominations' => $this->getDenominationDetails($denominations),
            'payments' => $payments,
            'total_payments' => $payments->sum('total'),
            'total_cash_transaction' => $counterUpdate->opening_balance + $counterUpdate->total_cash_amount_in_sales + $counterUpdate->total_cash_amount_in_booking_payment + $counterUpdate->total_cash_amount_in_booking_payment_refunded + $counterUpdate->total_cash_ins_amount - $counterUpdate->total_cash_outs_amount + $counterUpdate->total_cash_amount_in_credit_note_refunded,
        ];
    }

    private function getDenominationDetails(Collection $denominations): Collection
    {
        return $denominations->map(function ($details): array {
            /** @var CloseCounterDenomination $closeCounterDenomination */
            $closeCounterDenomination = $details;

            return [
                'denomination' => $closeCounterDenomination->denomination,
                'denomination_quantity' => $closeCounterDenomination->quantity,
            ];
        });
    }

    private function getPreparedPayments(Collection $payments): Collection
    {
        $payments = $payments->where('paymentType.id', '!=', StaticPaymentTypes::CREDIT_NOTE->value);

        return $payments->map(function ($item): array {
            /** @var CloseCounterPayment $closeCounterPayment */
            $closeCounterPayment = $item;

            /** @var PaymentType $paymentType */
            $paymentType = $closeCounterPayment->paymentType;

            return [
                'payment_type_id' => $closeCounterPayment->payment_type_id,
                'payment_type' => $paymentType->name,
                'total_transactions' => $closeCounterPayment->total_transactions,
                'total' => (float) $closeCounterPayment->total_amount,
            ];
        })->values();
    }
}
