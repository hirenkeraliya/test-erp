<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DreamPriceProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['dream_price_id', 'product_id', 'price'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function dreamPrice(): BelongsTo
    {
        return $this->belongsTo(DreamPrice::class);
    }
}
