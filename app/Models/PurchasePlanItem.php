<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePlanItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_plan_id',
        'product_id',
        'unit_of_measure_derivative_id',
        'quantity',
        'transferred_quantity',
        'cost_price',
        'total_price',
        'is_product_purchase_cost',
        'remarks',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class);
    }

    public function derivative(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class, 'unit_of_measure_derivative_id');
    }
}
