<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItemExchange extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_item_id',
        'old_item_price',
        'current_item_price',
        'price_differences',
        'old_discount_amount',
        'old_item_tax',
        'current_item_tax',
        'tax_differences',
    ];
}
