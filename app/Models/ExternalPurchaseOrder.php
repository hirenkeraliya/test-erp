<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExternalPurchaseOrder extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_plan_id',
        'order_number',
        'date',
        'notes',
        'fob',
        'freight_charges',
        'insurance_charges',
        'duty',
        'sst',
        'handling_charges',
        'other_charges',
        'total_amount',
        'status',
    ];

    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExternalPurchaseOrderItem::class);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }
}
