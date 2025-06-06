<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Inventory\Events\InventoryCreateEvent;
use App\Domains\Inventory\Events\InventoryUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Inventory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_id', 'location_id', 'stock', 'reserved_stock'];

    public function inventoryUnits(): HasMany
    {
        return $this->hasMany(InventoryUnit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservedStocks(): HasMany
    {
        return $this->hasMany(ReservedStock::class);
    }

    public function transitStocks(): HasMany
    {
        return $this->hasMany(TransitStock::class);
    }

    public function reservedStocksWithDeleted(): HasMany
    {
        return $this->hasMany(ReservedStock::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function transitStocksWithDeleted(): HasMany
    {
        return $this->hasMany(TransitStock::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function saleItemUnits(): HasMany
    {
        return $this->hasMany(SaleItemUnit::class);
    }

    public function orderItemUnits(): HasMany
    {
        return $this->hasMany(OrderItemUnit::class);
    }

    public function productChannelReferences(): HasMany
    {
        return $this->hasMany(ProductChannelReference::class, 'product_id', 'product_id');
    }

    public function stockTransferItemUnits(): HasMany
    {
        return $this->hasMany(StockTransferItemUnit::class);
    }

    public function purchaseOrderFulfillmentItemUnits(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItemUnit::class);
    }

    public function stockTransferItemUnitsWithDeleted(): HasMany
    {
        return $this->hasMany(StockTransferItemUnit::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    // Can be Store, Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($inventory): void {
            event(new InventoryUpdateEvent($inventory));
        });

        static::created(function ($inventory): void {
            event(new InventoryCreateEvent($inventory));
        });
    }
}
