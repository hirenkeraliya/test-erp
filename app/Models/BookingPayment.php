<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookingPayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'offline_id',
        'counter_update_id',
        'member_id',
        'total_amount',
        'available_amount',
        'status',
        'remarks',
        'bill_reference_number',
        'authorizer_id',
        'authorizer_type',
        'happened_at',
        'digital_invoice_number',
        'digital_invoice_submitted',
    ];

    protected $casts = [
        'digital_invoice_submitted' => 'boolean',
    ];

    // It can be Store Manager
    public function authorizer(): MorphTo
    {
        return $this->morphTo();
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(BookingPaymentProduct::class);
    }

    public function bookingPaymentPayments(): HasMany
    {
        return $this->hasMany(BookingPaymentPayment::class);
    }

    public function bookingPaymentUses(): HasMany
    {
        return $this->hasMany(BookingPaymentUse::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(BookingPaymentRefund::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(BookingPaymentRefund::class);
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function bookingPaymentVoidUses(): HasMany
    {
        return $this->hasMany(BookingPaymentVoidUse::class);
    }
}
