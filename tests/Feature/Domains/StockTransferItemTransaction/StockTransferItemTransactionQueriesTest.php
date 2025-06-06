<?php

declare(strict_types=1);

use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransferItemTransaction\StockTransferItemTransactionQueries;
use App\Models\Admin;
use App\Models\StockTransferItem;

test('stock transfer item transaction can be added', function (): void {
    $stockTransferItemTransactionQueries = new StockTransferItemTransactionQueries();

    $stockTransferItem = StockTransferItem::factory()->create();
    $admin = Admin::factory()->create();
    $status = StatusTypes::DRAFT->value;

    $itemDetails = [
        'stock_transfer_item_id' => $stockTransferItem->id,
        'status' => $status,
        'remarks' => 'test_remarks',
        'user_id' => $admin->id,
    ];

    $stockTransferItemTransactionQueries->addNew($stockTransferItem->id, $itemDetails['remarks'], $status, $admin);

    $this->assertDatabaseHas('stock_transfer_item_transactions', $itemDetails);
});
