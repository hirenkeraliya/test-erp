<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class HoldSale extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'counter_update_id',
        'offline_id',
        'type_id',
        'complete_sale_id',
        'complete_offline_id',
        'complete_sale_return_id',
        'complete_at',
        'cancelled_at',
    ];

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function holdSaleDetails(): HasMany
    {
        return $this->hasMany(HoldSaleDetail::class);
    }

    public function getHoldSaleDetails(): Collection
    {
        return $this->holdSaleDetails;
    }
}
