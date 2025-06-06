<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'happened_at',
        'delivery_order_number',
        'notes',
        'status',
        'external_purchase_order_fulfillment_id',
        'purchase_order_invoice_id',
        'created_by_company_id',
    ];

    public function getStatus(): int
    {
        return $this->status;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItem::class);
    }

    public function purchaseOrderInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderInvoice::class);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentTransaction::class);
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function partiallyReceives(): HasMany
    {
        return $this->hasMany(PartiallyReceiveFulfillment::class);
    }
}
