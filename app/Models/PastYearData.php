<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PastYearData extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'location_id',
        'brand_id',
        'date',
        'sale_amount',
        'total_sale',
        'units_sold',
        'return_amount',
        'units_return',
        'net_sales',
    ];
}
