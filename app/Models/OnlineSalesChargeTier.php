<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnlineSalesChargeTier extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['online_sales_charges_id', 'min_weight', 'max_weight', 'amount'];
}
