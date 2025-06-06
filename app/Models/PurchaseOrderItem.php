<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'parent_purchase_order_item_id',
        'external_purchase_order_item_id',
        'product_id',
        'quantity',
        'rejected_quantity',
        'transferred_quantity',
        'price_per_unit',
        'remarks',
        'unit_of_measure_derivative_id',
        'purchase_cost',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function derivative(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class, 'unit_of_measure_derivative_id');
    }

    public function purchaseOrderFulFillmentsItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderFulfillmentItem::class);
    }
}
