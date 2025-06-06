<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Batch extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'product_id', 'number', 'expiry_date', 'notes', 'external_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryUnit(): HasOne
    {
        return $this->hasOne(InventoryUnit::class);
    }
}
