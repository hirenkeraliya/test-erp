<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClosePayment;

use App\Models\StoreDayClosePayment;
use Illuminate\Support\Collection;

class StoreDayClosePaymentQueries
{
    public function addNew(int $storeDayCloseId, int $paymentTypeId, Collection $payments): void
    {
        StoreDayClosePayment::create([
            'store_day_close_id' => $storeDayCloseId,
            'payment_type_id' => $paymentTypeId,
            'total_transactions' => $payments->sum('total_transactions'),
            'total_amount' => $payments->sum('total_amount'),
        ]);
    }

    public function updateOrderPaymentDetails(int $storeDayCloseId, int $paymentTypeId, Collection $orderPayments): void
    {
        StoreDayClosePayment::updateOrCreate([
            'store_day_close_id' => $storeDayCloseId,
            'payment_type_id' => $paymentTypeId,
        ], [
            'total_transactions' => 0.0,
            'total_amount' => 0.0,
            'total_order_transactions' => $orderPayments->count(),
            'total_order_amount' => $orderPayments->sum('amount'),
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,store_day_close_id,payment_type_id,total_transactions,total_amount,total_order_transactions,total_order_amount';
    }
}
