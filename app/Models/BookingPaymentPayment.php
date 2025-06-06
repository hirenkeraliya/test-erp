<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingPaymentPayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_payment_id',
        'counter_update_id',
        'payment_type_id',
        'amount',
        'remarks',
        'extra_details',
        'currency_id',
        'currency_rate',
        'currency_amount',
    ];

    protected $casts = [
        'extra_details' => 'json',
    ];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creditNoteUse(): HasOne
    {
        return $this->hasOne(CreditNoteUse::class);
    }
}
