<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentPayments;

use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\BookingPaymentPayment;
use Illuminate\Support\Collection;

class BookingPaymentPaymentQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,booking_payment_id,payment_type_id,amount,remarks,created_at,currency_id,currency_rate,currency_amount';
    }

    public function getBookingPaymentIdColumn(): string
    {
        return 'id,booking_payment_id';
    }

    public function addNew(
        BookingPaymentData|BookingPaymentTopUpData $data,
        int $bookingPaymentId,
        int $counterUpdateId
    ): BookingPaymentPayment {
        return BookingPaymentPayment::create([
            'booking_payment_id' => $bookingPaymentId,
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $data->payment_type_id,
            'amount' => $data->amount,
            'currency_id' => $data->currency_id ?? null,
            'currency_rate' => $data->current_currency_rate ?? null,
            'currency_amount' => $data->currency_amount ?? null,
            'remarks' => $data->remarks,
        ]);
    }

    public function addNewForMultiple(int $bookingPaymentId, int $counterUpdateId, array $paymentDetails): int
    {
        $extraDetails = $paymentDetails['extra_details'] ?? null;

        return BookingPaymentPayment::create([
            'booking_payment_id' => $bookingPaymentId,
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $paymentDetails['payment_type_id'],
            'amount' => $paymentDetails['amount'],
            'currency_id' => $paymentDetails['currency_id'] ?? null,
            'currency_rate' => $paymentDetails['current_currency_rate'] ?? null,
            'currency_amount' => $paymentDetails['currency_amount'] ?? null,
            'extra_details' => $extraDetails,
        ])->id;
    }

    public function getByCounterUpdateIdWithPaymentType(int $counterUpdateId): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return BookingPaymentPayment::query()
            ->select('id', 'payment_type_id', 'amount')
            ->with('paymentType:' . $paymentTypeQueries->getBasicColumnNames())
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }

    public function getByBookingPaymentId(int $bookingPaymentId): ?BookingPaymentPayment
    {
        return BookingPaymentPayment::select('id')->where('booking_payment_id', $bookingPaymentId)->first();
    }
}
