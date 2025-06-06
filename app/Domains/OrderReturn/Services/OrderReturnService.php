<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Services;

use App\Domains\Order\Enums\OrderTypes;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrderReturnService
{
    public CheckOrderReturnDetailsService $checkOrderReturnDetailsService;

    public Collection $orderReturnItems;

    public Collection $returnedOrderItems;

    public Collection $orderReturnReasons;

    public function setDetails(CheckOrderReturnDetailsService $checkOrderReturnDetailsService): void
    {
        $this->checkOrderReturnDetailsService = $checkOrderReturnDetailsService;
        $this->orderReturnItems = collect($checkOrderReturnDetailsService->orderReturnData->order_return_items);

        $this->returnedOrderItems = $this->getReturnedOrderItems(
            $this->orderReturnItems->pluck('order_item_id')->unique()->toArray()
        );

        $returnReasonIds = $this->orderReturnItems->pluck('order_return_reason_id')->toArray();
        $this->orderReturnReasons = $this->getOrderReturnReasons($returnReasonIds);
    }

    public function getReturnedOrderItems(array $orderItemIds): Collection
    {
        if ($this->hasReturnItems()) {
            $orderItemQueries = resolve(OrderItemQueries::class);

            return $orderItemQueries->getByIdsWithRelations($orderItemIds);
        }

        return collect([]);
    }

    public function hasReturnItems(): bool
    {
        return $this->orderReturnItems->isNotEmpty();
    }

    public function getOrderReturnReasons(array $orderReturnReasonIds): Collection
    {
        if ($this->hasReturnItems()) {
            $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);

            return $saleReturnReasonQueries->getByIdsAndCompanyIdForOrderReturn(
                $orderReturnReasonIds,
                $this->checkOrderReturnDetailsService->companyId
            );
        }

        return collect([]);
    }

    public function checkRoundOffValue(): void
    {
        $orderReturnRoundOffAmount = $this->checkOrderReturnDetailsService->orderReturnData->order_return_round_off_amount;

        if (null !== $orderReturnRoundOffAmount) {
            return;
        }

        abort(412,
            'We regret to inform you that the round-off amount for the order return has not been specified or included.'
        );
    }

    public function checkReturnItems(): void
    {
        if (! $this->hasReturnItems()) {
            return;
        }

        $order = $this->returnedOrderItems->first()->order;

        if ($order->getTypeId()->value === OrderTypes::PENDING_LAYAWAY_ORDER->value && $order->layaway_pending_amount > 0) {
            abort(412, 'Pending Layaway order cannot be returned.');
        }

        if ($order->getTypeId()->value === OrderTypes::PENDING_CREDIT_ORDER->value) {
            abort(412, 'Pending Credit order cannot be returned.');
        }

        if ($order->getTypeId()->value === OrderTypes::CANCEL_ORDER->value) {
            abort(412, 'Cancel cannot be returned.');
        }

        $returnReasonIds = $this->getReturnReasonIds();

        if (
            $this->orderReturnReasons->count()
            !== count($returnReasonIds)
        ) {
            abort(412, 'Some of the order return reasons are not available in our records.');
        }

        $ordersReturnDaysLimit = $this->checkOrderReturnDetailsService->location->sales_return_days_limit;

        if ($ordersReturnDaysLimit > 0) {
            /** @var Carbon $returnDate */
            $returnDate = Carbon::now();

            $orderCreatedDate = $order->getCreatedAt();
            $orderAndOrderReturnDifferentDays = $returnDate->diffInDays($orderCreatedDate);

            if ($orderAndOrderReturnDifferentDays > $ordersReturnDaysLimit) {
                abort(412, 'Order cannot be returned after ' . $ordersReturnDaysLimit . ' days.');
            }
        }

        foreach ($this->orderReturnItems as $orderReturnItem) {
            $returnedOrderItem = $this->returnedOrderItems->firstWhere('id', $orderReturnItem['order_item_id']);

            /** @var Product $product */
            $product = $returnedOrderItem->product;

            if (
                $product->type_id !== ProductTypes::REGULAR_PRODUCT->value
                && $product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value
            ) {
                abort(412, 'Returns are not permitted for non-regular items.');
            }

            $availableQuantities = $orderReturnItem['return_quantity'];

            if ($orderReturnItem['return_quantity'] > $availableQuantities) {
                abort(
                    412,
                    'Only ' . $availableQuantities . ' units can be given for return. But requested return quantities are ' . $orderReturnItem['return_quantity'] . '.'
                );
            }
        }
    }

    public function getReturnReasonIds(): array
    {
        return $this->orderReturnItems
            ->pluck('order_return_reason_id')
            ->unique()
            ->toArray();
    }
}
