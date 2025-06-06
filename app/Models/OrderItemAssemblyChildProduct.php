<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItemAssemblyChildProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['order_item_id', 'child_product_id', 'units'];
}
