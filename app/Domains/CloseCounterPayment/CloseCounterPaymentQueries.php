<?php

declare(strict_types=1);

namespace App\Domains\CloseCounterPayment;

use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\CloseCounterPayment;
use Closure;

class CloseCounterPaymentQueries
{
    public function addNew(int $counterUpdateId, array $salePayment): void
    {
        CloseCounterPayment::create([
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $salePayment['payment_type_id'],
            'total_transactions' => $salePayment['total_transactions'],
            'total_amount' => $salePayment['total'],
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,payment_type_id,total_transactions,total_amount';
    }

    public function getExcludeCreditNote(): Closure
    {
        return fn ($query) => $query->select(
            'id',
            'counter_update_id',
            'payment_type_id',
            'total_transactions',
            'total_amount'
        )
            ->whereNot('payment_type_id', StaticPaymentTypes::CREDIT_NOTE->value);
    }
}
