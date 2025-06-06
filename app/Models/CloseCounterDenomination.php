<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CloseCounterDenomination extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['counter_update_id', 'denomination', 'quantity'];
}
