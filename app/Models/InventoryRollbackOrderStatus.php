<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryRollbackOrderStatus extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['ecommerce_location_id', 'order_status'];
}
