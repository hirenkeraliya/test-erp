<?php

declare(strict_types=1);

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Models\Cashback;
use App\Models\CashMovement;
use App\Models\CounterUpdate;
use App\Models\Sale;
use App\Models\SaleCashback;
use Carbon\Carbon;

test('Sale cashback can be added', function (): void {
    $sale = Sale::factory()->create();

    $cashback = Cashback::factory()->create();
    $cashMovementId = CashMovement::factory()->create()->id;

    $saleCashbackQueries = new SaleCashbackQueries();
    $saleCashbackQueries->addNew(
        $sale->id,
        $cashback->id,
        100,
        0.5,
        Carbon::now()->format('Y-m-d H:i:s'),
        $cashMovementId
    );

    $this->assertDatabaseHas('sale_cashbacks', [
        'sale_id' => $sale->id,
        'cashback_id' => $cashback->id,
        'amount' => 100,
    ]);
});

test('getByCounterUpdateId method returns the list of cashbacks', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $cashMovement = CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $cashMovement = CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    SaleCashback::factory()->create([
        'sale_id' => $sale->id,
        'cash_movement_id' => $cashMovement->id,
    ]);

    $saleCashbackQueries = new SaleCashbackQueries();
    $response = $saleCashbackQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'sale_id', 'amount']);
});
