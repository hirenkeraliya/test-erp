<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CloseCounterDenomination\CloseCounterDenominationQueries;
use App\Domains\CloseCounterPayment\CloseCounterPaymentQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Models\CounterUpdate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class CloseCounterService
{
    /**
     * @return array<string, mixed>
     */
    public function prepareAndReturnCounterClosingDetails(CounterUpdate $counterUpdate): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);

        $regularSales = $saleQueries->getRegularSalesByCounterUpdateId($counterUpdate->id);
        $layawaySales = $saleQueries->getLayawaySalesByCounterUpdateId($counterUpdate->id);
        $creditSales = $saleQueries->getCreditSalesByCounterUpdateId($counterUpdate->id);
        $voidedSales = $saleQueries->getVoidedSalesByCounterUpdateId($counterUpdate->id);
        $saleReturns = $saleReturnQueries->getByCounterUpdateId($counterUpdate->id);
        $cancelLayawaySales = $saleQueries->getCancelLayawaySalesByCounterUpdateId($counterUpdate->id);

        $salesAmount = $saleQueries->getSalesWithoutVoidSaleByCounterUpdateId($counterUpdate->id);

        $salePayments = $salePaymentQueries->getByCounterUpdateIdWithRelations($counterUpdate->id);

        $cashMovements = $cashMovementQueries->getByCounterUpdateId($counterUpdate->id);

        $totalCashIns = $this->getTotalCashIns($cashMovements);
        $totalCashOuts = $this->getTotalCashOuts($cashMovements);

        $bookingPaymentCount = $bookingPaymentQueries->getBookingPaymentCountByCounterUpdateId($counterUpdate->id);

        $creditNoteUses = $creditNoteUseQueries->getByCounterUpdateId($counterUpdate->id);
        $bookingPaymentUses = $bookingPaymentUseQueries->getByCounterUpdateId($counterUpdate->id);

        $creditNotesRefunds = $creditNoteRefundQueries->getByCounterUpdateIdWithPaymentType($counterUpdate->id);

        $bookingPaymentRefunds = $bookingPaymentRefundQueries->getByCounterUpdateIdWithPaymentType($counterUpdate->id);

        $bookingPaymentPayments = $bookingPaymentPaymentQueries->getByCounterUpdateIdWithPaymentType(
            $counterUpdate->id
        );

        $saleDiscounts = $saleDiscountQueries->getSaleDiscountByCounterUpdateId($counterUpdate->id);
        $saleItemDiscounts = $saleItemDiscountQueries->getSaleItemDiscountByCounterUpdateId($counterUpdate->id);

        $totalVouchersGenerated = $voucherQueries->getCountByCounterUpdateId($counterUpdate->id);
        $cashbacks = $saleCashbackQueries->getByCounterUpdateId($counterUpdate->id);

        $vouchers = $saleDiscounts->where('discountable_type', ModelMapping::VOUCHER->name);
        $salePromotions = $saleDiscounts->where('discountable_type', ModelMapping::PROMOTION->name);

        $saleItemPromotions = $saleItemDiscounts->where('discountable_type', ModelMapping::PROMOTION->name);
        $saleItemDreamPrices = $saleItemDiscounts->where('discountable_type', ModelMapping::DREAM_PRICE->name);
        $saleItemComplimentary = $saleItemDiscounts->where(
            'discountable_type',
            ModelMapping::COMPLIMENTARY_ITEM_REASON->name
        );
        $saleItemPriceOverrides = $saleItemDiscounts->where(
            'discountable_type',
            ModelMapping::SALE_ITEM_PRICE_OVERRIDE->name
        );

        $totalCashAmountInSales = $salePayments->where(
            'payment_type_id',
            StaticPaymentTypes::CASH->value
        )->sum('amount');

        $totalCashAmountInCreditNoteRefunded = $creditNotesRefunds->where(
            'payment_type_id',
            StaticPaymentTypes::CASH->value
        )->sum('amount');

        $totalCashAmountInBookingPayment = $bookingPaymentPayments->where(
            'payment_type_id',
            StaticPaymentTypes::CASH->value
        )->sum('amount');

        $totalCashAmountInBookingPaymentRefunded = $bookingPaymentRefunds->where(
            'payment_type_id',
            StaticPaymentTypes::CASH->value
        )->sum('amount');

        $closingBalanceInCash = CommonFunctions::numberFormat(
            (
                $counterUpdate->opening_balance + $totalCashAmountInSales +
                $totalCashAmountInBookingPayment + $totalCashIns
            ) - $totalCashAmountInBookingPaymentRefunded - $totalCashOuts - $totalCashAmountInCreditNoteRefunded
        );

        $totalItemDiscountAmount = $regularSales->sum('items_discount_amount') + $layawaySales->sum(
            'items_discount_amount'
        ) + $creditSales->sum('items_discount_amount');
        $totalCartDiscountAmount = $regularSales->sum('cart_discount_amount') + $layawaySales->sum(
            'cart_discount_amount'
        ) + $creditSales->sum('cart_discount_amount');

        $totalTaxAmount = $regularSales->sum('total_tax_amount') + $layawaySales->sum(
            'total_tax_amount'
        ) + $creditSales->sum('total_tax_amount');
        $totalRoundOff = $regularSales->sum('round_off') + $layawaySales->sum('round_off') + $creditSales->sum(
            'round_off'
        );

        $allSalesTotalAmount = (float) $salesAmount->sum('total_amount_paid');
        $totalSalesCollectionAmount = $allSalesTotalAmount - ((float) $saleReturns->sum('total_price_paid'));

        $totalAmountInSales = $salePayments
            ->where('payment_type_id', '!=', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::CREDIT_NOTE->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::GIFT_CARD->value)
            ->sum('amount');

        $totalAmountInBookingPayment = $bookingPaymentPayments
            ->where('payment_type_id', '!=', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::CREDIT_NOTE->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::GIFT_CARD->value)
            ->sum('amount');

        $totalAmountInBookingPaymentRefunded = $bookingPaymentRefunds
            ->where('payment_type_id', '!=', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::CREDIT_NOTE->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::GIFT_CARD->value)
            ->sum('amount');

        $totalAmountInCreditNoteRefunded = $creditNotesRefunds
            ->where('payment_type_id', '!=', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::CREDIT_NOTE->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->where('payment_type_id', '!=', StaticPaymentTypes::GIFT_CARD->value)
            ->sum('amount');

        $totalSalesCollectionAmount = CommonFunctions::numberFormat(
            ($totalAmountInSales + $totalAmountInBookingPayment) - $totalAmountInBookingPaymentRefunded - $totalAmountInCreditNoteRefunded
        );

        return [
            'opening_balance' => (float) $counterUpdate->opening_balance,
            'closing_balance' => $closingBalanceInCash,
            'sales_collection_amount' => $totalSalesCollectionAmount,
            'total_sales' => $regularSales->count(),
            'total_sales_amount' => (float) $regularSales->pluck('total_amount_paid')->sum(),
            'total_layaway_sales' => $layawaySales->count(),
            'total_layaway_sales_amount' => $layawaySales->sum('total_amount_paid') + $layawaySales->sum(
                'layaway_pending_amount'
            ),
            'total_credit_sales' => $creditSales->count(),
            'total_credit_sales_amount' => $creditSales->sum('total_amount_paid') + $creditSales->sum(
                'credit_pending_amount'
            ),
            'total_voided_sales' => $voidedSales->count(),
            'total_voided_sales_amount' => $voidedSales->sum('total_amount_paid'),
            'total_discount_amount' => (float) ($totalItemDiscountAmount + $totalCartDiscountAmount),
            'total_item_wise_discount_amount' => (float) $totalItemDiscountAmount,
            'total_cart_wide_discount_amount' => (float) $totalCartDiscountAmount,
            'total_tax_amount' => (float) $totalTaxAmount,
            'total_sales_round_off' => (float) $totalRoundOff,
            'total_sale_returns' => $saleReturns->count(),
            'total_sale_returns_amount' => (float) $saleReturns->sum('total_price_paid'),
            'total_credit_notes_used_amount' => $creditNoteUses->sum('amount'),
            'total_credit_notes_used' => $creditNoteUses->count(),
            'total_credit_notes_refunded_amount' => $creditNotesRefunds->sum('amount'),
            'total_credit_notes_refunded' => $creditNotesRefunds->count(),
            'total_sale_returns_round_off' => (float) $saleReturns->sum('round_off_amount'),
            'total_cashback' => $cashbacks->count(),
            'total_cashback_amount' => $cashbacks->sum('amount'),
            'total_vouchers_used' => $vouchers->count(),
            'total_voucher_discount_amount' => $vouchers->sum('amount'),
            'total_vouchers_generated' => $totalVouchersGenerated,
            'total_sale_promotion_used' => $salePromotions->count(),
            'total_sale_promotion_discount_amount' => $salePromotions->sum('amount'),
            'total_sale_item_promotion_used' => $saleItemPromotions->count(),
            'total_sale_item_promotion_discount_amount' => $saleItemPromotions->sum('amount'),
            'total_dream_price_used' => $saleItemDreamPrices->count(),
            'total_dream_price_discount_amount' => $saleItemDreamPrices->sum('amount'),
            'total_complimentary_item_discount_used' => $saleItemComplimentary->count(),
            'total_complimentary_item_discount_amount' => $saleItemComplimentary->sum('amount'),
            'total_price_override_used' => $saleItemPriceOverrides->count(),
            'total_price_override_discount_amount' => $saleItemPriceOverrides->sum('amount'),
            'total_booking_payment_amount' => $bookingPaymentPayments->sum('amount'),
            'total_booking_payment_refunded_amount' => $bookingPaymentRefunds->sum('amount'),
            'total_booking_payment_used_amount' => (float) $bookingPaymentUses->sum('amount'),
            'total_cash_ins_amount' => $totalCashIns,
            'total_cash_outs_amount' => $totalCashOuts,
            'total_cash_amount_in_sales' => (float) $totalCashAmountInSales,
            'total_cash_amount_in_booking_payment' => (float) $totalCashAmountInBookingPayment,
            'total_cash_amount_in_booking_payment_refunded' => (float) $totalCashAmountInBookingPaymentRefunded,
            'total_cash_amount_in_credit_note_refunded' => (float) $totalCashAmountInCreditNoteRefunded,
            'total_new_booking_payments' => $bookingPaymentCount,
            'total_used_booking_payments' => $bookingPaymentUses->count(),
            'total_cancel_layaway_sales' => $cancelLayawaySales->count(),
            'total_cancel_layaway_sales_amount' => $cancelLayawaySales->sum('total_amount_paid'),
            'payments' => $this->preparePayments(
                $salePayments,
                $bookingPaymentPayments,
                $bookingPaymentRefunds,
                $creditNotesRefunds,
            ),
        ];
    }

    public function checkRequestDetails(
        CloseCounterData|CloseCounterDataForStoreManager $closeCounterData,
        array $counterClosingDetails,
        int $statusCode
    ): void {
        $counterClosingBalance = (float) $counterClosingDetails['closing_balance'];
        $requestClosingBalance = $closeCounterData->closing_balance;

        if (! CommonFunctions::compareFloatNumbers($counterClosingBalance, $requestClosingBalance)
            && ! $closeCounterData->mismatch_amount_reason
        ) {
            abort(
                $statusCode,
                'Reason field is required. Please specify the amount ' . $requestClosingBalance . ' does not match with the expected amount ' . $counterClosingBalance
            );
        }

        if ($closeCounterData->closing_balance > 0) {
            if (! $closeCounterData->denominations instanceof DataCollection) {
                abort($statusCode, 'A denomination is required');
            }

            $denominationTotal = collect($closeCounterData->denominations)->sum(
                fn ($denomination): float => (float) ($denomination['denomination'] * $denomination['quantity'])
            );

            if (! CommonFunctions::compareFloatNumbers(
                (float) $denominationTotal,
                $closeCounterData->closing_balance
            )) {
                abort($statusCode, 'The cash denomination does not match.');
            }
        }
    }

    public function closeCounter(
        CloseCounterData|CloseCounterDataForStoreManager $closeCounterData,
        CounterUpdate $counterUpdate,
        array $counterClosingDetails,
        string $closedByType,
        int $closedId,
    ): void {
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);

        if ($closeCounterData->denominations instanceof DataCollection) {
            foreach ($closeCounterData->denominations as $denomination) {
                /** @var CloseCounterDenominationData $closeCounterDenominationData */
                $closeCounterDenominationData = $denomination;
                $closeCounterDenominationQueries->addNew($counterUpdate->id, $closeCounterDenominationData);
            }
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdateQueries->closeCounterUpdate(
            $counterUpdate,
            $closeCounterData,
            $counterClosingDetails,
            $closedByType,
            $closedId
        );

        foreach ($counterClosingDetails['payments'] as $salePayment) {
            $closeCounterPaymentQueries->addNew($counterUpdate->id, $salePayment);
        }
    }

    public function preparePayments(
        Collection $salePayments,
        Collection $bookingPaymentPayments,
        Collection $bookingPaymentRefunds,
        Collection $creditNotesRefunds,
    ): Collection {
        $paymentsRecords = $this->prepareMatchedPaymentsFor(
            $salePayments,
            $bookingPaymentPayments,
            $bookingPaymentRefunds,
            $creditNotesRefunds
        );

        $this->addUnmatchedBookingPayments($bookingPaymentPayments, $paymentsRecords);
        $this->addUnmatchedBookingPaymentRefunds($bookingPaymentRefunds, $paymentsRecords);
        $this->addUnmatchedCreditNoteRefundPayments($creditNotesRefunds, $paymentsRecords);

        return $paymentsRecords;
    }

    public function checkCloseCounterDetails(CounterUpdate $counterUpdate, CloseCounterData $closeCounterData): void
    {
        if (null === $closeCounterData->closed_by_pos_at) {
            return;
        }

        /** @var Carbon $requestedClosedAt */
        $requestedClosedAt = Carbon::createFromFormat('Y-m-d H:i:s', $closeCounterData->closed_by_pos_at);
        $requestedClosedAt = $requestedClosedAt->format('Y-m-d H:i:s');

        $openedAt = $counterUpdate->opened_by_pos_at ?? $counterUpdate->created_at;

        if ($requestedClosedAt <= $openedAt) {
            abort(
                412,
                'It is not allowed to close the counter with a date that is earlier than the date it was opened.'
            );
        }
    }

    private function prepareMatchedPaymentsFor(
        Collection $salePayments,
        Collection $bookingPaymentPayments,
        Collection $bookingPaymentRefunds,
        Collection $creditNotesRefunds
    ): Collection {
        return $salePayments->groupBy('payment_type_id')
            ->map(function ($salePayments, $paymentTypeId) use (
                $bookingPaymentPayments,
                $bookingPaymentRefunds,
                $creditNotesRefunds
            ): array {
                $matchedBookingPayments = $bookingPaymentPayments->where('payment_type_id', $paymentTypeId);
                $refundedBookingPayments = $bookingPaymentRefunds->where('payment_type_id', $paymentTypeId);
                $refundedCreditNotePayments = $creditNotesRefunds->where('payment_type_id', $paymentTypeId);

                return [
                    'payment_type_id' => $paymentTypeId,
                    'payment_type' => $salePayments->first()->paymentType->name,
                    'total_transactions' => $salePayments->count() + $matchedBookingPayments->count() + $refundedBookingPayments->count() + $refundedCreditNotePayments->count(),
                    'total' => CommonFunctions::numberFormat(
                        $salePayments->sum('amount') + $matchedBookingPayments->sum('amount')
                    ) - $refundedBookingPayments->sum('amount') - $refundedCreditNotePayments->sum('amount'),
                ];
            })->values();
    }

    private function addUnmatchedBookingPayments(Collection $bookingPaymentPayments, Collection $paymentsRecords): void
    {
        $unmatchedBookingPayments = $bookingPaymentPayments->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedBookingPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'payment_type' => $payments->first()->paymentType->name,
                'total_transactions' => $payments->count(),
                'total' => (float) $payments->sum('amount'),
            ]);
        }
    }

    private function addUnmatchedBookingPaymentRefunds(
        Collection $bookingPaymentRefunds,
        Collection $paymentsRecords
    ): void {
        $unmatchedRefundPayments = $bookingPaymentRefunds->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedRefundPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'payment_type' => $payments->first()->paymentType->name,
                'total_transactions' => $payments->count(),
                'total' => '-' . (float) $payments->sum('amount'),
            ]);
        }
    }

    private function addUnmatchedCreditNoteRefundPayments(
        Collection $creditNotesRefunds,
        Collection $paymentsRecords
    ): void {
        $unmatchedRefundPayments = $creditNotesRefunds->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedRefundPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'payment_type' => $payments->first()->paymentType->name,
                'total_transactions' => $payments->count(),
                'total' => '-' . (float) $payments->sum('amount'),
            ]);
        }
    }

    private function getTotalCashIns(Collection $cashMovements): float
    {
        return $cashMovements->where('cash_movement_type_id', CashMovementTypes::CASH_IN->value)->sum('amount');
    }

    private function getTotalCashOuts(Collection $cashMovements): float
    {
        return $cashMovements->where('cash_movement_type_id', CashMovementTypes::CASH_OUT->value)->sum('amount');
    }
}
