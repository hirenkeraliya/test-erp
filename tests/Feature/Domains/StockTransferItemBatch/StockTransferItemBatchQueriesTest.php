<?php

declare(strict_types=1);

use App\Domains\StockTransferItemBatch\StockTransferItemBatchQueries;
use App\Models\Batch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;

test('stock transfer item batch can be added', function (): void {
    $stockTransferItemBatchQueries = new StockTransferItemBatchQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
    ]);
    $batch = Batch::factory()->create();

    $batchDetails = [
        'stock_transfer_item_id' => $stockTransferItem->id,
        'batch_id' => $batch->id,
        'quantity' => 10.22,
    ];

    $stockTransferItemBatchQueries->addNew($batchDetails);

    $this->assertDatabaseHas('stock_transfer_item_batches', $batchDetails);
});
