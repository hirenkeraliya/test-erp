<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentRefund;

use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\BookingPaymentRefund;
use Illuminate\Support\Collection;

class BookingPaymentRefundQueries
{
    public function addNew(array $bookingPaymentDetails): void
    {
        BookingPaymentRefund::create([
            'booking_payment_id' => $bookingPaymentDetails['booking_payment_id'],
            'counter_update_id' => $bookingPaymentDetails['counter_update_id'],
            'payment_type_id' => $bookingPaymentDetails['payment_type_id'],
            'amount' => $bookingPaymentDetails['amount'],
            'currency_id' => $bookingPaymentDetails['currency_id'] ?? null,
            'currency_rate' => $bookingPaymentDetails['currency_rate'] ?? null,
            'currency_amount' => $bookingPaymentDetails['currency_amount'] ?? null,
        ]);
    }

    public function getByCounterUpdateIdWithPaymentType(int $counterUpdateId): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return BookingPaymentRefund::query()
            ->select('id', 'payment_type_id', 'amount')
            ->with('paymentType:' . $paymentTypeQueries->getBasicColumnNames())
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,booking_payment_id,counter_update_id,payment_type_id,amount,created_at,currency_id,currency_rate,currency_amount';
    }
}
