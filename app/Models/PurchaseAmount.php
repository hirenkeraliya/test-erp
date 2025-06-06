<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseAmount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'landed_cost',
        'fob',
        'freight_charges',
        'insurance_charges',
        'duty',
        'sst',
        'handling_charges',
        'other_charges',
    ];
}
