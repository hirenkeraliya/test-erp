<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoldSaleItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hold_sale_detail_id', 'product_id', 'derivative_id', 'quantity', 'original_sale_item_id', 'returned_quantity', 'original_price_per_unit', 'cart_discount_amount', 'item_discount_amount', 'total_discount_amount', 'total_tax_amount', 'price_paid_per_unit', 'total_price_paid', 'group_id', 'is_exchange',
    ];

    protected $casts = [
        'is_exchange' => 'boolean',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'original_sale_item_id');
    }
}
