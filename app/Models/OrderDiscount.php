<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderDiscount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['order_id', 'discountable_id', 'discountable_type', 'amount', 'promo_code'];

    // Can be Promotion, Voucher, Cashback
    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }
}
