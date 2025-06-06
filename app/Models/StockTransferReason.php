<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransferReason extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'name', 'code'];
}
