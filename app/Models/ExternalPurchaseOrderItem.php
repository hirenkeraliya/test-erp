<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPurchaseOrderItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_purchase_order_id',
        'purchase_plan_item_id',
        'product_id',
        'quantity',
        'received_quantity',
        'cost_price',
        'charge_per_unit',
        'total_price',
        'remarks',
        'unit_of_measure_derivative_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchasePlanItem(): BelongsTo
    {
        return $this->belongsTo(PurchasePlanItem::class);
    }

    public function derivative(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class, 'unit_of_measure_derivative_id');
    }
}
