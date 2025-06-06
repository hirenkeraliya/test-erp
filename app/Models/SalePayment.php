<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class SalePayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'payment_type_id',
        'counter_update_id',
        'amount',
        'happened_at',
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

    public function bookingPaymentUse(): HasOne
    {
        return $this->hasOne(BookingPaymentUse::class);
    }

    public function creditNoteUse(): HasOne
    {
        return $this->hasOne(CreditNoteUse::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    /**
     * @return mixed[]
     */
    public static function getPreparedPayments(?Collection $salePayments): array
    {
        if (! $salePayments instanceof Collection) {
            return [];
        }

        return $salePayments->map(function ($payment): array {
            /** @var SalePayment $salePayment */
            $salePayment = $payment;

            /** @var PaymentType $paymentType */
            $paymentType = $salePayment->paymentType;

            return [
                'id' => $salePayment->getKey(),
                'payment_type' => $paymentType->getName(),
                'amount' => $salePayment->getAmount(),
            ];
        })->toArray();
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $salePayment = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $salePayment->sale;

        return $sale->offline_sale_id;
    }
}
