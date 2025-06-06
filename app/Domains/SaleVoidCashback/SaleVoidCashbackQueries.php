<?php

declare(strict_types=1);

namespace App\Domains\SaleVoidCashback;

use App\Models\SaleVoidCashback;

class SaleVoidCashbackQueries
{
    public function addNew(int $saleCashbackId, int $voidSaleId, float $cashMovementId): void
    {
        SaleVoidCashback::create([
            'sale_cashback_id' => $saleCashbackId,
            'void_sale_id' => $voidSaleId,
            'cash_movement_id' => $cashMovementId,
        ]);
    }
}
