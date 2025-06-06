<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\Events\VoucherCreateEvent;
use App\Domains\Voucher\Events\VoucherUpdateEvent;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Voucher extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'voucher_configuration_id', 'member_id', 'generated_by_sale_id', 'created_by_location_id', 'discount_type', 'number', 'minimum_spend_amount', 'percentage', 'flat_amount', 'used_at', 'expiry_date', 'cancelled_at', 'dream_price_applicable', 'item_wise_promotion_applicable', 'cart_wide_promotion_applicable', 'status', 'generated_by_order_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'dream_price_applicable' => 'boolean',
        'item_wise_promotion_applicable' => 'boolean',
        'cart_wide_promotion_applicable' => 'boolean',
    ];

    public function voucherConfiguration(): BelongsTo
    {
        return $this->belongsTo(VoucherConfiguration::class);
    }

    public function createdByLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'created_by_location_id');
    }

    public function voucherTransactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'generated_by_sale_id');
    }

    public function saleDiscounts(): MorphMany
    {
        return $this->morphMany(SaleDiscount::class, 'discountable');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function getDiscountValue(int $discountTypeId): float
    {
        if (DiscountTypes::PERCENTAGE->value === $discountTypeId) {
            return (float) $this->percentage;
        }

        return (float) $this->flat_amount;
    }

    public function getVoucherTransactions(): Collection
    {
        return $this->voucherTransactions->map(function (VoucherTransaction $voucherTransaction): array {
            $voucherLocation = $voucherTransaction->location;
            $voucherSale = $voucherTransaction->sale;
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $voucherTransaction->happened_at);

            return [
                'offline_sale_id' => $voucherSale ? $voucherSale->offline_sale_id . ' (' . SaleStatus::getFormattedCaseName(
                    $voucherSale->status
                ) . ')' : 'N/A',
                'action_type' => VoucherTransactionActionTypes::getFormattedCaseName(
                    $voucherTransaction->action_type_id
                ),
                'store' => $voucherLocation ? $voucherLocation->name . ' (' . $voucherLocation->code . ')' : 'N/A',
                'location' => $voucherLocation ? $voucherLocation->name . ' (' . $voucherLocation->code . ')' : 'N/A',
                'date' => $date->format('Y-m-d h:i:s A'),
            ];
        });
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', VoucherStatusTypes::ACTIVE->value);
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($voucher): void {
            event(new VoucherUpdateEvent($voucher));
        });

        static::created(function ($voucher): void {
            event(new VoucherCreateEvent($voucher));
        });
    }
}
