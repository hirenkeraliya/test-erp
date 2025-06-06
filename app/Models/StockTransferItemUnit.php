<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockTransferItemUnit extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['stock_transfer_item_id', 'inventory_id', 'purchase_amount_id', 'batch_id', 'quantity'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function stockTransferItem(): BelongsTo
    {
        return $this->belongsTo(StockTransferItem::class);
    }

    public function stockTransferItemWithTrashed(): BelongsTo
    {
        return $this->belongsTo(StockTransferItem::class, 'stock_transfer_item_id')->withoutGlobalScope(
            SoftDeletingScope::class
        );
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $this->refresh();

        $stockTransferItemUnit = $this->load([
            'stockTransferItemWithTrashed:' . $stockTransferItemQueries->getStockTransferIdColumn(),
            'stockTransferItemWithTrashed.stockTransfer:' .$stockTransferQueries->getReferenceNumberColumns(),
        ]);

        /** @var StockTransferItem $stockTransferItem */
        $stockTransferItem = $stockTransferItemUnit->stockTransferItemWithTrashed;

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
