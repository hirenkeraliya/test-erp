<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StockTransfer\StockTransferQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StockTransferItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'package_type_id',
        'unit_of_measure_derivative_id',
        'derivative_ratio',
        'is_extra_item',
        'package_quantity',
        'package_total_quantity',
        'quantity',
        'received_quantity',
        'discrepancy_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_extra_item' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function packageType(): BelongsTo
    {
        return $this->belongsTo(PackageType::class);
    }

    public function unitOfMeasureDerivative(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(StockTransferItemUnit::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockTransferItemBatch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransferItemTransaction::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(StockTransferItemTransaction::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('discrepancy_proof')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png', 'video/mp4', 'video/mpeg', 'video/quicktime']);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $this->refresh();
        $stockTransferItem = $this->refresh()
            ->load('stockTransfer:' . $stockTransferQueries->getReferenceNumberColumns());

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
