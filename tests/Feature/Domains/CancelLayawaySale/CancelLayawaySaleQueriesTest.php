<?php

declare(strict_types=1);

use App\Domains\CancelLayawaySale\CancelLayawaySaleQueries;
use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Models\CancelLayawaySale;

beforeEach(function (): void {
    $this->voidSaleQueries = new VoidSaleQueries();
});

test('A sale can be cancel layaway sale', function (): void {
    $cancelLayawaySale = CancelLayawaySale::factory()->make()->toArray();

    $cancelLayawaySaleData = new CancelLayawaySaleData(
        store_manager_id: $cancelLayawaySale['store_manager_id'],
        passcode: '123456',
        happened_at: now()->format('Y-m-d H:i:s'),
        reason: 'Test',
    );

    $cancelLayawaySaleQueries = new CancelLayawaySaleQueries();
    $response = $cancelLayawaySaleQueries->addNew($cancelLayawaySaleData, $cancelLayawaySale['sale_id']);
    expect($response)->toBeObject();

    $this->assertDatabaseHas('cancel_layaway_sales', [
        'sale_id' => $cancelLayawaySale['sale_id'],
        'store_manager_id' => $cancelLayawaySale['store_manager_id'],
        'reason' => 'Test',
    ]);
});
