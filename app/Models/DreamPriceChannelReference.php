<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DreamPriceChannelReference extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_channel_id', 'dream_price_id', 'external_dream_price_id'];
}
