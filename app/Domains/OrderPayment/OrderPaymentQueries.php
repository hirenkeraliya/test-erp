<?php

declare(strict_types=1);

namespace App\Domains\OrderPayment;

use App\Domains\Order\OrderQueries;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Collection;

class OrderPaymentQueries
{
    public function addNew(Order $order, array $paymentDetails, int $storeManagerId, int $locationId): void
    {
        OrderPayment::create([
            'order_id' => $order->getKey(),
            'payment_type_id' => $paymentDetails['type_id'],
            'store_manager_id' => $storeManagerId,
            'location_id' => $locationId,
            'amount' => $paymentDetails['amount'],
            'notes' => $paymentDetails['notes'] ?? null,
        ]);
    }

    public function addNewForEcommerce(Order $order, array $paymentDetails, int $locationId): void
    {
        OrderPayment::create([
            'order_id' => $order->getKey(),
            'payment_type_id' => $paymentDetails['type_id'],
            'location_id' => $locationId,
            'amount' => $paymentDetails['amount'],
            'notes' => $paymentDetails['notes'] ?? null,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,order_id,payment_type_id,store_manager_id,location_id,amount,notes,created_at';
    }

    public function getOrderPaymentWithGivenTimeFrame(
        int $locationId,
        ?string $lastStoreDayCloseClosedAtDate
    ): Collection {
        $orderQueries = resolve(OrderQueries::class);

        return OrderPayment::query()
            ->select(...explode(',', $this->getBasicColumnNames()))
            ->whereHas('order', $orderQueries->filterByLocationId($locationId))
            ->when($lastStoreDayCloseClosedAtDate, function ($query) use ($lastStoreDayCloseClosedAtDate): void {
                $query->where('created_at', '>=', $lastStoreDayCloseClosedAtDate)
                    ->where('created_at', '<=', now()->format('Y-m-d H:i:s'));
            })
            ->get()
            ->groupBy('payment_type_id');
    }
}
