<?php

declare(strict_types=1);

use App\Domains\SaleVoidCashback\SaleVoidCashbackQueries;
use App\Models\CashMovement;
use App\Models\SaleCashback;
use App\Models\VoidSale;

test('Sale void cashback can be added', function (): void {
    $saleCashback = SaleCashback::factory()->create();

    $voidSale = VoidSale::factory()->create();
    $cashMovementId = CashMovement::factory()->create()->id;

    $saleVoidCashbackQueries = new SaleVoidCashbackQueries();
    $saleVoidCashbackQueries->addNew($saleCashback->id, $voidSale->id, $cashMovementId);

    $this->assertDatabaseHas('sale_void_cashbacks', [
        'sale_cashback_id' => $saleCashback->id,
        'void_sale_id' => $voidSale->id,
        'cash_movement_id' => $cashMovementId,
    ]);
});
