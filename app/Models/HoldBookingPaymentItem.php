<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoldBookingPaymentItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['hold_sale_detail_id', 'product_id', 'quantity'];

    public function holdSaleDetail(): BelongsTo
    {
        return $this->belongsTo(HoldSaleDetail::class);
    }
}
