<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryWiseDailyTotal extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'location_id',
        'category_id',
        'date',
        'total_units_sold',
        'total_amount',
        'total_units_return',
        'total_amount_return',
        'counter_update_id',
    ];
}
