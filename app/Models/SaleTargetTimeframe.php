<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleTargetTimeframe extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_target_id', 'start_date', 'end_date', 'target_label', 'amount', 'percentage'];

    public function saleTarget(): BelongsTo
    {
        return $this->belongsTo(SaleTarget::class);
    }

    public function saleAchievedTargets(): HasMany
    {
        return $this->hasMany(SaleAchievedTarget::class);
    }
}
