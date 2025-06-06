<?php

declare(strict_types=1);

namespace App\Domains\SaleCashback;

use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Sale\SaleQueries;
use App\Models\SaleCashback;
use Illuminate\Support\Collection;

class SaleCashbackQueries
{
    public function addNew(
        int $saleId,
        int $cashbackId,
        float $amount,
        float $roundOff,
        string $happenedAt,
        int $cashMovementId
    ): void {
        SaleCashback::create([
            'sale_id' => $saleId,
            'cashback_id' => $cashbackId,
            'cash_movement_id' => $cashMovementId,
            'amount' => $amount,
            'round_off' => $roundOff,
            'happened_at' => $happenedAt,
        ]);
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,sale_id,cashback_id,amount,round_off';
    }

    public static function getColumnNamesForAdminReports(): string
    {
        return 'id,sale_id,cashback_id,amount';
    }

    public static function getByCounterUpdateId(int $counterUpdateId): Collection
    {
        $cashMovementQueries = resolve(CashMovementQueries::class);

        return SaleCashback::query()
            ->select('id', 'sale_id', 'cash_movement_id', 'amount')
            ->whereHas('cashMovement', function ($query) use ($cashMovementQueries, $counterUpdateId): void {
                $query->select('id')
                    ->where($cashMovementQueries->filterByCounterUpdateId($counterUpdateId));
            })
            ->get();
    }

    public function getBySaleId(int $saleId): ?SaleCashback
    {
        $saleQueries = resolve(SaleQueries::class);

        return SaleCashback::select('id', 'sale_id', 'amount')
            ->with('sale:' . $saleQueries->getColumnNamesForVoidSaleCashBack())
            ->where('sale_id', $saleId)
            ->first();
    }
}
