<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['stock_take_id', 'product_id', 'actual_stock', 'submitted_stock'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }
}
