<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_purchase_order_id',
        'parent_purchase_order_id',
        'external_company_id',
        'external_location_id',
        'created_by_company_id',
        'company_id',
        'location_id',
        'reference_number',
        'remarks',
        'require_date',
        'attention',
        'status',
        'order_number',
        'external_order_number',
        'order_type',
    ];

    public function externalCompany(): BelongsTo
    {
        return $this->belongsTo(ExternalCompany::class);
    }

    public function externalLocation(): BelongsTo
    {
        return $this->belongsTo(ExternalLocation::class);
    }

    public function parentPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_purchase_order_id');
    }

    public function childPurchaseOrder(): HasOne
    {
        return $this->hasOne(self::class, 'parent_purchase_order_id');
    }

    // Can be Store, Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getOrderType(): int
    {
        return $this->order_type;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillment::class);
    }

    public function getFulfillments(): Collection
    {
        return $this->fulfillments;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PurchaseOrderTransaction::class);
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }
}
