<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateDeclarationAttemptPayment;

use App\Models\CounterUpdateDeclarationAttemptPayment;

class CounterUpdateDeclarationAttemptPaymentQueries
{
    public function getBasicColumns(): string
    {
        return 'id,counter_update_declaration_attempt_id,payment_type_id,declared_amount,calculated_amount,denominations';
    }

    public function createMany(array $counterUpdateDeclarationAttemptPayments): void
    {
        CounterUpdateDeclarationAttemptPayment::insert($counterUpdateDeclarationAttemptPayments);
    }
}
