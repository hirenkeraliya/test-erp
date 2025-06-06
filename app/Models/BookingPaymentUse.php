<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPaymentUse extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['booking_payment_id', 'counter_update_id', 'sale_payment_id', 'amount'];

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class);
    }
}
