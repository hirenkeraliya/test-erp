<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloseCounterPayment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['counter_update_id', 'payment_type_id', 'total_transactions', 'total_amount'];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }
}
