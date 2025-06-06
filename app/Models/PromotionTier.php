<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionTier extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['promotion_id', 'buy_value', 'get_value', 'get_quantity', 'max_value'];
}
