<?php

declare(strict_types=1);

use App\Domains\CancelCreditSale\CancelCreditSaleQueries;
use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Models\CancelCreditSale;

test('A sale can be cancel credit sale', function (): void {
    $cancelCreditSale = CancelCreditSale::factory()->make()->toArray();

    $cancelCreditSaleData = new CancelCreditSaleData(
        store_manager_id: $cancelCreditSale['store_manager_id'],
        passcode: '123456',
        happened_at: now()->format('Y-m-d H:i:s'),
        reason: 'Test',
    );

    $cancelCreditSaleQueries = new CancelCreditSaleQueries();
    $response = $cancelCreditSaleQueries->addNew($cancelCreditSaleData, $cancelCreditSale['sale_id']);
    expect($response)->toBeObject();

    $this->assertDatabaseHas('cancel_credit_sales', [
        'sale_id' => $cancelCreditSale['sale_id'],
        'store_manager_id' => $cancelCreditSale['store_manager_id'],
        'reason' => 'Test',
    ]);
});
