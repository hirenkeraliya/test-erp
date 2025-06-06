<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionPromoCode extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['promotion_id', 'promo_code'];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
