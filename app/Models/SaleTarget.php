<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleTarget extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'amount',
        'percentage',
        'amount_type',
        'target_type',
        'time_interval_type',
        'status',
        're_generate_target',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        're_generate_target' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class);
    }

    public function saleTargetTimeframes(): HasMany
    {
        return $this->hasMany(SaleTargetTimeframe::class);
    }

    public function getTargetType(): int
    {
        return $this->target_type;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }
}
