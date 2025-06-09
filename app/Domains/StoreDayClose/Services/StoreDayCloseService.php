<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Services;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreDayClosePayment\StoreDayClosePaymentQueries;
use App\Models\Location;
use App\Models\Order;
use App\Models\StoreDayClose;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreDayCloseService
{
    public function addStoreDayClose(
        CounterUpdateQueries $counterUpdateQueries,
        StoreDayCloseQueries $storeDayCloseQueries,
        Location $location,
        ?StoreDayClose $lastStoreDayClose,
        ?int $storeManagerId = null
    ): StoreDayClose {
        $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);

        $counterUpdates = $counterUpdateQueries->getByStoreWithPaymentsFilterByDates(
            $location->id,
            $lastStoreDayClose
        );

        $orderQueries = resolve(OrderQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);

        $orderDetails = $orderQueries->getByLocationWithPaymentsFilterByDates(
            $location->id,
            $lastStoreDayClose?->closed_at
        );

        $dateOfFirstCounterOfTheDay = null;

        if ($lastStoreDayClose instanceof StoreDayClose) {
            $dateOfFirstCounterOfTheDay = $counterUpdateQueries->getFirstCounterOpenDate(
                $location->id,
                $lastStoreDayClose,
            )?->opened_by_pos_at;
        }

        $prepareOrderDetails = $this->prepareOrderDetails($orderDetails);

        return DB::transaction(function () use (
            $storeDayCloseQueries,
            $location,
            $storeManagerId,
            $counterUpdates,
            $prepareOrderDetails,
            $dateOfFirstCounterOfTheDay,
            $storeDayClosePaymentQueries,
            $orderPaymentQueries,
            $lastStoreDayClose,
            $orderQueries,
            $orderDetails
        ): StoreDayClose {
            $storeDayClose = $storeDayCloseQueries->addNew(
                $location,
                $storeManagerId,
                $counterUpdates,
                $prepareOrderDetails,
                $dateOfFirstCounterOfTheDay,
            );

            $counterUpdatePayments = $counterUpdates->pluck('payments')->collapse()->groupBy('payment_type_id');

            foreach ($counterUpdatePayments as $paymentTypeId => $payments) {
                $storeDayClosePaymentQueries->addNew($storeDayClose->getKey(), $paymentTypeId, $payments);
            }

            $orderPayments = $orderPaymentQueries->getOrderPaymentWithGivenTimeFrame(
                $location->id,
                $lastStoreDayClose?->closed_at
            );

            foreach ($orderPayments as $paymentTypeId => $orderPaymentDetails) {
                $storeDayClosePaymentQueries->updateOrderPaymentDetails(
                    $storeDayClose->getKey(),
                    $paymentTypeId,
                    $orderPaymentDetails
                );
            }

            $orderQueries->updateLocationDayCloseId($orderDetails->pluck('id')->toArray(), $storeDayClose->getKey());

            return $storeDayClose;
        });
    }

    private function prepareOrderDetails(Collection $orderDetails): array
    {
        $regularOrders = $orderDetails->where('type_id', '=', OrderTypes::REGULAR_ORDER);
        $cancelOrders = $orderDetails->where('type_id', '=', OrderTypes::CANCEL_ORDER);
        $pendingAndCompleteLayawayOrders = $orderDetails->whereIn('type_id', [
            OrderTypes::COMPLETE_LAYAWAY_ORDER,
            OrderTypes::PENDING_LAYAWAY_ORDER,
        ]);
        $pendingAndCompleteCreditOrders = $orderDetails->whereIn('type_id', [
            OrderTypes::COMPLETE_CREDIT_ORDER,
            OrderTypes::PENDING_CREDIT_ORDER,
        ]);

        $totalItemDiscountAmount = $regularOrders->sum('item_discount_amount') + $pendingAndCompleteLayawayOrders->sum(
            'item_discount_amount'
        ) + $pendingAndCompleteCreditOrders->sum('item_discount_amount');
        $totalCartDiscountAmount = $regularOrders->sum('cart_discount_amount') + $pendingAndCompleteLayawayOrders->sum(
            'cart_discount_amount'
        ) + $pendingAndCompleteCreditOrders->sum('cart_discount_amount');

        $totalTaxAmount = $regularOrders->sum('total_tax_amount') + $pendingAndCompleteLayawayOrders->sum(
            'total_tax_amount'
        ) + $pendingAndCompleteCreditOrders->sum('total_tax_amount');
        $totalRoundOff = $regularOrders->sum('round_off') + $pendingAndCompleteLayawayOrders->sum(
            'round_off'
        ) + $pendingAndCompleteCreditOrders->sum('round_off');

        return [
            'orders_collection_amount' => $regularOrders->sum('total_amount_paid'),
            'total_orders' => $regularOrders->count(),
            'total_orders_amount' => $regularOrders->sum('total_amount_paid'),
            'total_layaway_orders' => $pendingAndCompleteLayawayOrders->count(),
            'total_layaway_orders_amount' => $pendingAndCompleteLayawayOrders->sum(
                'total_amount_paid'
            ) + $pendingAndCompleteLayawayOrders->sum('layaway_pending_amount'),
            'total_credit_orders' => $pendingAndCompleteCreditOrders->count(),
            'total_credit_orders_amount' => $pendingAndCompleteCreditOrders->sum(
                'total_amount_paid'
            ) + $pendingAndCompleteCreditOrders->sum('credit_pending_amount'),
            'total_cancelled_orders' => $cancelOrders->count(),
            'total_cancelled_orders_amount' => $cancelOrders->sum('total_amount_paid'),
            'total_order_item_wise_discount_amount' => $totalItemDiscountAmount,
            'total_order_cart_wide_discount_amount' => $totalCartDiscountAmount,
            'total_order_tax_amount' => $totalTaxAmount,
            'total_orders_round_off' => $totalRoundOff,
            'total_order_returns' => 0.0,
            'total_order_returns_amount' => 0.0,
            'total_order_returns_round_off' => 0.0,
            'total_order_complimentary_item_discount_used' => $orderDetails
                ->flatMap(fn (Order $orderDetail) => $orderDetail->orderItems->whereNotNull(
                    'complimentary_item_reason_id'
                ))
                ->count(),
            'total_order_complimentary_item_discount_amount' => $orderDetails
                ->flatMap(fn (Order $orderDetail) => $orderDetail->orderItems
                    ->whereNotNull('complimentary_item_reason_id')
                    ->pluck('total_discount_amount'))
                ->sum(),
        ];
    }
}
