<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PurchaseOrderInvoice extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'company_id',
        'created_by_company_id',
        'external_purchase_order_invoice_id',
        'invoice_number',
        'status',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillment::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PurchaseOrderInvoiceTransaction::class);
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }
}
