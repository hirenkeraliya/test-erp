<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoldSaleReturnItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hold_sale_detail_id',
        'sale_item_id',
        'product_id',
        'quantity',
        'sale_return_reason_id',
        'total_price_paid',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_tax_amount',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
