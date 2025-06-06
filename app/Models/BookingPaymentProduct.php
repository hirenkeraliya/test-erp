<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPaymentProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_payment_id',
        'product_id',
        'quantity',
        'box_product_id',
        'product_box_package_type_id',
        'product_box_units',
        'price',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class);
    }

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class);
    }

    public function boxProduct(): BelongsTo
    {
        return $this->belongsTo(BoxProduct::class);
    }
}
