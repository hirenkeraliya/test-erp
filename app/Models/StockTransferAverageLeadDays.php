<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransferAverageLeadDays extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['from_location_id', 'to_location_id', 'average_days'];
}
