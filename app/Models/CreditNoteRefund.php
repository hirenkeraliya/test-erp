<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteRefund extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'credit_note_id',
        'counter_update_id',
        'payment_type_id',
        'amount',
        'store_manager_id',
        'currency_id',
        'currency_rate',
        'currency_amount',
    ];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class);
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->paymentType;
    }

    public function getStoreManager(): ?StoreManager
    {
        return $this->storeManager;
    }
}
