<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashbackPrice extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['cashback_id', 'condition_operator_type_id', 'amount'];
}
