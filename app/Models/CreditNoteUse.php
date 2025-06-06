<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteUse extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'credit_note_id',
        'counter_update_id',
        'sale_payment_id',
        'amount',
        'booking_payment_payment_id',
    ];

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function BookingPaymentPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPaymentPayment::class);
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }
}
