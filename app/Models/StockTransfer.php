<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StockTransfer\Enums\StatusTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class StockTransfer extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_transfer_reason_id',
        'company_id',
        'transfer_type',
        'stock_transfer_average_lead_day_id',
        'source_location_id',
        'destination_location_id',
        'transfer_date',
        'require_date',
        'received_date',
        'average_days',
        'is_transit_target_achieved',
        'attention',
        'requested_by_type',
        'requested_by_id',
        'created_by_location_id',
        'reference_number',
        'remarks',
        'status',
        'transit_location_id',
        'transfer_out_number',
        'transfer_in_number',
        'request_order_number',
        'transfer_order_number',
        'opened_at',
        'approved_at',
        'shipped_at',
        'received_at',
        'discrepancy_at',
        'closed_at',
        'cancelled_at',
        'rejected_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_transit_target_achieved' => 'boolean',
    ];

    // It can be store, warehouse
    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // It can be store, warehouse
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // It can be admin, store manager
    public function requestedBy(): MorphTo
    {
        return $this->morphTo();
    }

    // It can be store, warehouse
    public function transitLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransferTransaction::class);
    }

    public function stockTransferAverageLeadDay(): BelongsTo
    {
        return $this->belongsTo(StockTransferAverageLeadDays::class);
    }

    public function receivedBy(): HasOne
    {
        return $this->hasOne(StockTransferTransaction::class)
            ->whereIn(
                'new_status',
                [StatusTypes::RECEIVED->value, StatusTypes::DISCREPANCY->value, StatusTypes::CLOSED->value]
            );
    }

    public function stockTransferReason(): BelongsTo
    {
        return $this->belongsTo(StockTransferReason::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getSourceLocationId(): int
    {
        return $this->source_location_id;
    }

    public function getDestinationLocationId(): int
    {
        return $this->destination_location_id;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getSourceLocation(): string
    {
        /** @var Location $location */
        $location = $this->sourceLocation;

        return $location->name;
    }

    public function getDestinationLocation(): string
    {
        /** @var Location $location */
        $location = $this->destinationLocation;

        return $location->name;
    }
}
