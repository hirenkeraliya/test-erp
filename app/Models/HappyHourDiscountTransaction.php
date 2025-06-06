<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HappyHourDiscountTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'happy_hour_discount_id',
        'counter_update_id',
        'offline_id',
        'authorizer_id',
        'authorizer_type',
        'happened_at',
    ];

    // It can be Director, Store Manager
    public function authorizer(): MorphTo
    {
        return $this->morphTo();
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function happyHourDiscount(): BelongsTo
    {
        return $this->belongsTo(HappyHourDiscount::class);
    }
}
