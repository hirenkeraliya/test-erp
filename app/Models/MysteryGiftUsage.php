<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MysteryGiftUsage extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'mystery_gift_id',
        'member_id',
        'sale_id',
        'voucher_id',
        'coupon_code',
        'used_at',
        'used_sale_id',
        'product_id',
        'created_at',
        'updated_at',
    ];

    public function MysteryGift(): BelongsTo
    {
        return $this->belongsTo(MysteryGift::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class, 'id', 'voucher_id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
