<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Resources;

use App\CommonFunctions;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\StoreDayClose;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class DayCloseReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StoreDayClose $storeDayClose */
        $storeDayClose = $this;

        /** @var Location $location */
        $location = $storeDayClose->location;

        /** @var ?StoreManager $storeManager */
        $storeManager = $storeDayClose->storeManager;

        /** @var ?Employee $employee */
        $employee = $storeManager instanceof StoreManager ? $storeManager->employee : null;

        /** @var Collection $payments */
        $payments = $storeDayClose->payments;

        $payments = $this->getPreparedStoreDayClosePayments($payments);

        /** @var Carbon $openedAtFormat */
        $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $storeDayClose->opened_at);
        $openedAt = $openedAtFormat->format('d-m-Y h:i:s A');

        /** @var Carbon $closedAtFormat */
        $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $storeDayClose->closed_at);
        $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $storeDayClose->id,
            'location' => $location->name,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'store_manager' => $employee instanceof Employee ? $employee->getFullName() : 'System Generated',
            'sales_collection_amount' => $storeDayClose->sales_collection_amount,
            'total_sales' => $storeDayClose->total_sales,
            'total_sales_amount' => $storeDayClose->total_sales_amount,
            'total_layaway_sales' => $storeDayClose->total_layaway_sales,
            'total_layaway_sales_amount' => $storeDayClose->total_layaway_sales_amount,
            'total_credit_sales' => $storeDayClose->total_credit_sales,
            'total_credit_sales_amount' => $storeDayClose->total_credit_sales_amount,
            'total_voided_sales' => $storeDayClose->total_voided_sales,
            'total_voided_sales_amount' => $storeDayClose->total_voided_sales_amount,
            'total_item_wise_discount_amount' => $storeDayClose->total_item_wise_discount_amount,
            'total_cart_wide_discount_amount' => $storeDayClose->total_cart_wide_discount_amount,
            'total_tax_amount' => $storeDayClose->total_tax_amount,
            'total_sales_round_off' => $storeDayClose->total_sales_round_off,
            'total_sale_returns' => $storeDayClose->total_sale_returns,
            'total_sale_returns_amount' => $storeDayClose->total_sale_returns_amount,
            'total_credit_notes_used_amount' => $storeDayClose->total_credit_notes_used_amount,
            'total_credit_notes_used' => $storeDayClose->total_credit_notes_used,
            'total_credit_notes_refunded_amount' => $storeDayClose->total_credit_notes_refunded_amount,
            'total_credit_notes_refunded' => $storeDayClose->total_credit_notes_refunded,
            'total_sale_returns_round_off' => $storeDayClose->total_sale_returns_round_off,
            'total_cashback' => $storeDayClose->total_cashback,
            'total_cashback_amount' => $storeDayClose->total_cashback_amount,
            'total_vouchers_used' => $storeDayClose->total_vouchers_used,
            'total_vouchers_generated' => $storeDayClose->total_vouchers_generated,
            'total_booking_payment_amount' => $storeDayClose->total_booking_payment_amount,
            'total_booking_payment_refunded_amount' => $storeDayClose->total_booking_payment_refunded_amount,
            'total_booking_payment_used_amount' => $storeDayClose->total_booking_payment_used_amount,
            'total_cash_amount_in_sales' => $storeDayClose->total_cash_amount_in_sales,
            'total_cash_amount_in_booking_payment' => $storeDayClose->total_cash_amount_in_booking_payment,
            'total_cash_amount_in_booking_payment_refunded' => $storeDayClose->total_cash_amount_in_booking_payment_refunded,
            'total_cash_amount_in_credit_note_refunded' => $storeDayClose->total_cash_amount_in_credit_note_refunded,
            'total_cash_ins_amount' => $storeDayClose->total_cash_ins_amount,
            'total_cash_outs_amount' => $storeDayClose->total_cash_outs_amount,
            'payments' => $payments,
            'total_payments' => $payments->sum('total'),
            'total_cash_transaction' => $storeDayClose->total_cash_amount_in_sales + $storeDayClose->total_cash_amount_in_booking_payment + $storeDayClose->total_cash_amount_in_booking_payment_refunded + $storeDayClose->total_cash_amount_in_credit_note_refunded + $storeDayClose->opening_balance + $storeDayClose->total_cash_ins_amount - $storeDayClose->total_cash_outs_amount,
            'opening_balance' => $storeDayClose->opening_balance,
            'total_new_booking_payments' => $storeDayClose->total_new_booking_payments,
            'total_used_booking_payments' => $storeDayClose->total_used_booking_payments,
            'total_cancel_layaway_sales' => $storeDayClose->total_cancel_layaway_sales,
            'total_cancel_layaway_sales_amount' => $storeDayClose->total_cancel_layaway_sales_amount,
            'orders_collection_amount' => $storeDayClose->orders_collection_amount,
            'total_orders' => CommonFunctions::currencyFormat((float) $storeDayClose->total_orders),
            'total_orders_amount' => $storeDayClose->total_orders_amount,
            'total_layaway_orders' => CommonFunctions::currencyFormat((float) $storeDayClose->total_layaway_orders),
            'total_layaway_orders_amount' => $storeDayClose->total_layaway_orders_amount,
            'total_credit_orders' => CommonFunctions::currencyFormat((float) $storeDayClose->total_credit_orders),
            'total_credit_orders_amount' => $storeDayClose->total_credit_orders_amount,
            'total_cancelled_orders' => CommonFunctions::currencyFormat((float) $storeDayClose->total_cancelled_orders),
            'total_cancelled_orders_amount' => $storeDayClose->total_cancelled_orders_amount,
            'total_order_item_wise_discount_amount' => $storeDayClose->total_order_item_wise_discount_amount,
            'total_order_cart_wide_discount_amount' => $storeDayClose->total_order_cart_wide_discount_amount,
            'total_order_tax_amount' => $storeDayClose->total_order_tax_amount,
            'total_orders_round_off' => $storeDayClose->total_orders_round_off,
            'total_order_returns' => CommonFunctions::currencyFormat((float) $storeDayClose->total_order_returns),
            'total_order_returns_amount' => $storeDayClose->total_order_returns_amount,
            'total_order_returns_round_off' => $storeDayClose->total_order_returns_round_off,
            'total_order_complimentary_item_discount_used' => $storeDayClose->total_order_complimentary_item_discount_used,
            'total_order_complimentary_item_discount_amount' => $storeDayClose->total_order_complimentary_item_discount_amount,
            'total_order_payments' => $payments->sum('total_order_amount'),
        ];
    }

    private function getPreparedStoreDayClosePayments(Collection $payments): Collection
    {
        $payments = $payments->where('paymentType.id', '!=', StaticPaymentTypes::CREDIT_NOTE->value);

        return $payments->map(function ($storeDayClosePayment): array {
            /** @var PaymentType $paymentType */
            $paymentType = $storeDayClosePayment->paymentType;

            return [
                'id' => $storeDayClosePayment->id,
                'payment_type' => $paymentType->name,
                'total_transactions' => $storeDayClosePayment->total_transactions,
                'total' => $storeDayClosePayment->total_amount,
                'total_order_transactions' => $storeDayClosePayment->total_order_transactions ?? 0.0,
                'total_order_amount' => $storeDayClosePayment->total_order_amount ?? 0.0,
            ];
        });
    }
}
