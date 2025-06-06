<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAgeing extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'location_id',
        'product_created_at',
        'last_selling_date',
        'first_transfer_in',
        'first_goods_received_note',
        'quantity_sold',
        'quantity_remaining',
        'first_month_sold',
        'second_month_sold',
        'third_month_sold',
        'fourth_month_sold',
        'fifth_month_sold',
        'sixth_month_sold',
        'seventh_month_sold',
        'eighth_month_sold',
        'ninth_month_sold',
        'tenth_month_sold',
        'eleventh_month_sold',
        'twelfth_month_sold',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
