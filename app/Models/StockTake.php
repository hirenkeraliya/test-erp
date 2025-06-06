<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTake extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_record_date',
        'company_id',
        'requested_by_id',
        'requested_by_type',
        'location_id',
        'submitted_by_id',
        'submitted_by_type',
        'submitted_at',
        'compare_stock_date',
        'notes',
        'is_uploaded_products',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_uploaded_products' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(StockTakeProduct::class);
    }

    // can be StoreManager OR WarehouseManager
    public function requestedBy(): MorphTo
    {
        return $this->morphTo();
    }

    // can be StoreManager OR WarehouseManager
    public function submittedBy(): MorphTo
    {
        return $this->morphTo();
    }

    // can be Store OR Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')->latest();
    }

    public function getStockCompareDate(): Carbon|string
    {
        /** @var Carbon|string $compareStockDate */
        $compareStockDate = 'N/A';

        if ($this->compare_stock_date) {
            /** @var Carbon $compareStockDateFormat */
            $compareStockDateFormat = Carbon::createFromFormat('Y-m-d', $this->compare_stock_date);
            $compareStockDate = $compareStockDateFormat->format('d-m-Y');
        }

        return $compareStockDate;
    }
}
