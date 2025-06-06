<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionMonthDate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['promotion_id', 'month_date'];
}
