<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StockTransfer\StockTransferQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransferTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['stock_transfer_id', 'old_status', 'new_status', 'user_id', 'user_type', 'remarks'];

    // Can be Admin, StoreManager, WarehouseManager
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $this->refresh();
        $stockTransferTransaction = $this->load('stockTransfer:' .$stockTransferQueries->getReferenceNumberColumns());

        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $stockTransferTransaction->stockTransfer;

        return implode('|', array_filter([
            $stockTransfer->transfer_order_number,
            $stockTransfer->request_order_number,
            $stockTransfer->transfer_in_number,
            $stockTransfer->transfer_out_number,
        ])
        );
    }
}
