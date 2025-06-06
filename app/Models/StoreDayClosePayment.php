<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDayClosePayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'store_day_close_id',
        'payment_type_id',
        'total_transactions',
        'total_amount',
        'total_order_transactions',
        'total_order_amount',
    ];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function storeDayClose(): BelongsTo
    {
        return $this->belongsTo(StoreDayClose::class);
    }
}
