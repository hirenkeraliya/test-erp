<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateDeclarationAttempt;

use App\Domains\Cashier\CashierQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Models\CounterUpdateDeclarationAttempt;
use Illuminate\Support\Collection;

class CounterUpdateDeclarationAttemptQueries
{
    public function getList(int $counterUpdateId, ?string $afterUpdatedAt = null): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        return CounterUpdateDeclarationAttempt::select('id', 'counter_update_id', 'offline_id', 'happened_at')
            ->with([
                'counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttemptPayments.paymentType',
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('counter_update_id', $counterUpdateId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function addNew(string $offlineId, string $happenedAt, int $counterUpdateId): int
    {
        return CounterUpdateDeclarationAttempt::create([
            'counter_update_id' => $counterUpdateId,
            'offline_id' => $offlineId,
            'happened_at' => $happenedAt,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,offline_id,counter_update_id,happened_at';
    }
}
