<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Enums\ModelMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoterCommissionUpdate extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'promoter_commission_id',
        'affected_by_id',
        'affected_by_type',
        'department_id',
        'brand_id',
        'location_id',
        'amount',
        'total_price_paid',
        'commission_amount',
        'commission_percentage',
        'flat_commission',
        'discount_type',
    ];

    public function promoterCommission(): BelongsTo
    {
        return $this->belongsTo(PromoterCommission::class);
    }

    public function affected_by(): MorphTo
    {
        return $this->morphTo();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getOfflineId(string $affectedByType): string
    {
        if ($affectedByType === ModelMapping::SALE_ITEM->name) {
            return $this->affected_by->sale->offline_sale_id;
        }

        return $this->affected_by->saleReturn->offline_sale_return_id;
    }
}
