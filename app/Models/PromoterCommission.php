<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoterCommission extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'promoter_id',
        'commission_amount',
        'commission_amount_rounding',
        'total_sales_amount',
        'total_sales_amount_rounding',
        'total_return_sales_amount',
        'total_return_sales_amount_rounding',
        'monthly_sales_target',
        'commission_date',
    ];

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Promoter::class);
    }

    public function promoterCommissionUpdates(): HasMany
    {
        return $this->hasMany(PromoterCommissionUpdate::class);
    }
}
