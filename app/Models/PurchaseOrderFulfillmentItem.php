<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PurchaseOrderFulfillmentItem extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_fulfillment_id',
        'purchase_order_item_id',
        'product_id',
        'transfer_quantity',
        'received_quantity',
        'package_type_id',
        'package_quantity',
        'package_total_quantity',
        'remarks',
        'external_purchase_order_fulfillment_item_id',
        'is_extra_item',
        'discrepancy_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_extra_item' => 'boolean',
    ];

    public function purchaseOrderFulfillment(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderFulfillment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('discrepancy_proof')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    public function packageType(): BelongsTo
    {
        return $this->belongsTo(PackageType::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function itemBatches(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItemBatch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItemTransaction::class);
    }

    public function partialReceivedItems(): HasMany
    {
        return $this->hasMany(PartiallyReceiveFulfillmentItem::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItemUnit::class);
    }

    public function inventoryUpdates(): MorphMany
    {
        return $this->morphMany(InventoryUpdate::class, 'affected_by');
    }
}
