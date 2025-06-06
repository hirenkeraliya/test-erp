<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id', 'batch_id', 'purchase_amount_id', 'location_id', 'affected_by_id', 'affected_by_type', 'quantity', 'user_id', 'user_type', 'happened_at', 'notes', 'closing_stock', 'serial_number_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Can be Store, Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Can be Admin, StoreManager, WarehouseManager
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function affectedBy(): MorphTo
    {
        // https://stackoverflow.com/a/63458030
        return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function getOpeningStock(): float
    {
        return (float) ($this->closing_stock - $this->quantity);
    }

    public function getHappenedAt(): string
    {
        return $this->happened_at;
    }

    public function getClosingStock(): float
    {
        return (float) $this->closing_stock;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getAffectedByType(): string
    {
        return $this->affected_by_type;
    }

    public function getAffectedById(): int
    {
        return $this->affected_by_id;
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }
}
