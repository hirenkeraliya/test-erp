<?php

declare(strict_types=1);

use App\Domains\PurchaseAmount\PurchaseAmountQueries;

test('Purchase Amount can be added', function (): void {
    $purchaseAmountQueries = new PurchaseAmountQueries();

    $goodsReceivedNoteProduct = [
        'fob' => 1,
        'freight_charges' => 1,
        'insurance_charges' => 1,
        'duty' => 1,
        'sst' => 1,
        'handling_charges' => 1,
        'other_charges' => 1,
    ];
    $landedCost = 7;

    $purchaseAmountQueries->addNewAndGetId($goodsReceivedNoteProduct);

    $goodsReceivedNoteProduct['landed_cost'] = $landedCost;

    $this->assertDatabaseHas('purchase_amounts', $goodsReceivedNoteProduct);
});

test('Purchase Amount can be added by Landed Cost only', function (): void {
    $purchaseAmountQueries = new PurchaseAmountQueries();

    $purchaseAmountQueries->addBlankRecord();

    $this->assertDatabaseHas('purchase_amounts', [
        'landed_cost' => 0.00,
    ]);
});
