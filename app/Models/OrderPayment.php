<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class OrderPayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['order_id', 'store_manager_id', 'location_id', 'payment_type_id', 'amount', 'notes'];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->paymentType;
    }

    public static function getPreparedPayments(?Collection $orderPayments): array
    {
        if (! $orderPayments instanceof Collection) {
            return [];
        }

        return $orderPayments->map(function ($payment): array {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $payment;
            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->paymentType;

            return [
                'id' => $orderPayment->getKey(),
                'payment_type' => $paymentType->getName(),
                'amount' => $orderPayment->getAmount(),
            ];
        })->toArray();
    }
}
