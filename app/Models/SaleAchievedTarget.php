<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleAchievedTarget extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_target_timeframe_id', 'targetable_id', 'targetable_type', 'target_value', 'achieved_value',
    ];

    public function saleTargetTimeframe(): BelongsTo
    {
        return $this->belongsTo(SaleTargetTimeframe::class);
    }

    // Can be Store, Promoter, Company
    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}
