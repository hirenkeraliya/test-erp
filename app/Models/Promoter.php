<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Promoter extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use LogsActivity;
    use CaseSensitiveConditionals;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'group_id',
        'username',
        'password',
        'monthly_sales_target',
        'code',
        'created_by_id',
        'created_by_type',
        'default_commission_amount_percentage',
        'monthly_target_commission_percentage',
        'fcm_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(
                [
                    'employee_id',
                    'group_id',
                    'username',
                    'monthly_sales_target',
                    'code',
                    'default_commission_amount_percentage',
                    'monthly_target_commission_percentage',
                ]
            )
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function saleItems(): BelongsToMany
    {
        return $this->belongsToMany(SaleItem::class, 'sale_item_promoter');
    }

    public static function getPromoters(SaleItem $saleItem): string
    {
        /** @var Collection $promoters */
        $promoters = $saleItem->promoters;

        return $promoters->map(function ($promoter): string {
            /** @var Promoter $saleItemPromoter */
            $saleItemPromoter = $promoter;
            /** @var Employee $employee */
            $employee = $saleItemPromoter->employee;

            return $employee->getFullName();
        })->implode(', ');
    }

    public static function getOrderPromoters(OrderItem $orderItem): string
    {
        /** @var Collection $promoters */
        $promoters = $orderItem->promoters;

        return $promoters->map(function ($promoter): string {
            /** @var Promoter $orderItemPromoter */
            $orderItemPromoter = $promoter;
            /** @var Employee $employee */
            $employee = $orderItemPromoter->employee;

            return $employee->getFullName();
        })->implode(', ');
    }

    public function promoterGroup(): BelongsTo
    {
        return $this->belongsTo(PromoterGroup::class, 'group_id');
    }

    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function targetable(): MorphMany
    {
        return $this->morphMany(SaleAchievedTarget::class, 'targetable');
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
