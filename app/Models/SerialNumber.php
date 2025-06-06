<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SerialNumber extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'product_id', 'serial_number', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inventoryUnit(): HasOne
    {
        return $this->hasOne(InventoryUnit::class);
    }

    public function saleItemUnit(): HasOne
    {
        return $this->hasOne(SaleItemUnit::class);
    }
}
