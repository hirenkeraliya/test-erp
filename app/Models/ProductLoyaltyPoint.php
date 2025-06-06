<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLoyaltyPoint extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_id', 'membership_id', 'points'];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
