<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentUse;

use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use Illuminate\Support\Collection;

class BookingPaymentUseQueries
{
    public function addNew(
        BookingPayment $bookingPayment,
        int $salePaymentId,
        int $counterUpdateId,
        float $paymentAmount
    ): void {
        BookingPaymentUse::create([
            'booking_payment_id' => $bookingPayment->id,
            'counter_update_id' => $counterUpdateId,
            'sale_payment_id' => $salePaymentId,
            'amount' => $paymentAmount,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,sale_payment_id,amount,booking_payment_id';
    }

    public function getColumnNamesForPaginatedBookingPayments(): string
    {
        return 'id,booking_payment_id,counter_update_id,amount,created_at';
    }

    public function getByCounterUpdateId(int $counterUpdateId): Collection
    {
        return BookingPaymentUse::select('id', 'amount')
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }
}
