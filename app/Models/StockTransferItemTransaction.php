<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransferItemTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['stock_transfer_item_id', 'remarks', 'status', 'user_id', 'user_type'];

    // Can be Admin, StoreManager, WarehouseManager
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function stockTransferItem(): BelongsTo
    {
        return $this->belongsTo(StockTransferItem::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $this->refresh();
        $stockTransferItemTransaction = $this->load([
            'stockTransferItem:' . $stockTransferItemQueries->getStockTransferIdColumn(),
            'stockTransferItem.stockTransfer:' .$stockTransferQueries->getReferenceNumberColumns(),
        ]);

        /** @var StockTransferItem $stockTransferItem */
        $stockTransferItem = $stockTransferItemTransaction->stockTransferItem;

        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $stockTransferItem->stockTransfer;

        return implode('|', array_filter([
            $stockTransfer->transfer_order_number,
            $stockTransfer->request_order_number,
            $stockTransfer->transfer_in_number,
            $stockTransfer->transfer_out_number,
        ])
        );
    }
}
