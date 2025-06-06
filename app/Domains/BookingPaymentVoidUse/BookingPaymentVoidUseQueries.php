<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentVoidUse;

use App\Models\BookingPaymentVoidUse;

class BookingPaymentVoidUseQueries
{
    public function addNew(int $bookingPaymentId, int $bookingPaymentUseId, int $voidSaleId, float $amount): void
    {
        BookingPaymentVoidUse::create([
            'booking_payment_id' => $bookingPaymentId,
            'booking_payment_uses_id' => $bookingPaymentUseId,
            'void_sale_id' => $voidSaleId,
            'amount' => $amount,
        ]);
    }

    public function getColumnNames(): string
    {
        return 'id,booking_payment_id,booking_payment_uses_id,void_sale_id,amount';
    }
}
