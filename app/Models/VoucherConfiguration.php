<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\VoucherConfiguration\Events\VoucherConfigurationCreateEvent;
use App\Domains\VoucherConfiguration\Events\VoucherConfigurationUpdateEvent;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationSaleChannelService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VoucherConfiguration extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 'mystery_gift_id', 'restricted_by_type', 'voucher_type', 'exclude_by_type', 'issue_minimum_spend_amount', 'use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date', 'created_by_id', 'created_by_type', 'dream_price_applicable', 'item_wise_promotion_applicable', 'cart_wide_promotion_applicable', 'redemption_foot_note', 'handover_foot_note', 'title', 'description', 'terms_and_conditions', 'status', 'is_available_in_ecommerce',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'dream_price_applicable' => 'boolean',
        'item_wise_promotion_applicable' => 'boolean',
        'cart_wide_promotion_applicable' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'voucher_configuration_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'voucher_configuration_category');
    }

    public function voucherConfigurationTiers(): HasMany
    {
        return $this->hasMany(VoucherConfigurationTier::class);
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class, 'membership_voucher_configuration');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class, 'voucher_configuration_sale_channel');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($voucherConfiguration): void {
            if ($voucherConfiguration->isDirty('is_available_in_ecommerce')) {
                $oldValue = $voucherConfiguration->getOriginal('is_available_in_ecommerce');
                $newValue = $voucherConfiguration->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $voucherConfigurationSaleChannelService = resolve(VoucherConfigurationSaleChannelService::class);
                    $voucherConfigurationSaleChannelService->unAvailableVoucherConfiguration($voucherConfiguration->id);

                    return;
                }
            }

            event(new VoucherConfigurationUpdateEvent($voucherConfiguration));
        });

        static::created(function ($voucherConfiguration): void {
            event(new VoucherConfigurationCreateEvent($voucherConfiguration));
        });
    }
}
