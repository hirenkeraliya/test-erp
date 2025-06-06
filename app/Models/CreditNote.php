<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CreditNote extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'counter_update_id', 'sale_return_id', 'cancel_layaway_sale_id', 'cancel_credit_sale_id', 'expiry_date', 'total_amount', 'available_amount', 'status', 'member_id', 'digital_invoice_number', 'digital_invoice_submitted',
    ];

    protected $casts = [
        'digital_invoice_submitted' => 'boolean',
    ];

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function uses(): HasMany
    {
        return $this->hasMany(CreditNoteUse::class);
    }

    public function creditNoteRefund(): HasOne
    {
        return $this->hasOne(CreditNoteRefund::class);
    }

    public function creditNoteExpiration(): HasOne
    {
        return $this->hasOne(CreditNoteExpiration::class);
    }

    public function getCreditNoteRefund(): ?CreditNoteRefund
    {
        return $this->creditNoteRefund;
    }

    public function getCreditNoteExpiration(): ?CreditNoteExpiration
    {
        return $this->creditNoteExpiration;
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function cancelLayawaySale(): BelongsTo
    {
        return $this->belongsTo(CancelLayawaySale::class);
    }

    public function cancelCreditSale(): BelongsTo
    {
        return $this->belongsTo(CancelCreditSale::class);
    }
}
