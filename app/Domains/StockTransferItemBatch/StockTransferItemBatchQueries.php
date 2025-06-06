<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItemBatch;

use App\Models\StockTransferItemBatch;

class StockTransferItemBatchQueries
{
    public function addNew(array $batchDetails): StockTransferItemBatch
    {
        return StockTransferItemBatch::create($batchDetails);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,stock_transfer_item_id,batch_id,quantity';
    }

    public function decreaseQuantity(StockTransferItemBatch $stockTransferItemBatch, float $quantity): void
    {
        $stockTransferItemBatch->quantity -= $quantity;
        $stockTransferItemBatch->save();
    }

    public function increaseQuantity(StockTransferItemBatch $stockTransferItemBatch, float $quantity): void
    {
        $stockTransferItemBatch->quantity += $quantity;
        $stockTransferItemBatch->save();
    }
}
