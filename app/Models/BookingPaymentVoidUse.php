<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPaymentVoidUse extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['booking_payment_id', 'booking_payment_uses_id', 'void_sale_id', 'amount'];

    public function voidSale(): BelongsTo
    {
        return $this->belongsTo(VoidSale::class);
    }
}
