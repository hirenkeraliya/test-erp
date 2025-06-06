<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeDeleteEvent;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeUpdateEvent;
use App\Domains\OnlineSalesCharges\Services\OnlineSalesChargeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineSalesCharges extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'shipping_zone_id',
        'shipping_charge_type_id',
        'name',
        'minimum_value',
        'maximum_value',
        'amount',
        'status',
        'is_available_in_ecommerce',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($onlineSaleCharges): void {
            if ($onlineSaleCharges->isDirty('is_available_in_ecommerce')) {
                $oldValue = $onlineSaleCharges->getOriginal('is_available_in_ecommerce');
                $newValue = $onlineSaleCharges->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $onlineSaleChargesEcommerceService = resolve(OnlineSalesChargeService::class);
                    $onlineSaleChargesEcommerceService->unAvailableOnlineSaleChargesInCommerce($onlineSaleCharges->id);

                    return;
                }

                event(new OnlineSaleChargeUpdateEvent($onlineSaleCharges));
            }
        });

        static::deleted(function ($onlineSaleCharges): void {
            event(new OnlineSaleChargeDeleteEvent($onlineSaleCharges));
        });
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function onlineSalesChargeTiers(): HasMany
    {
        return $this->hasMany(OnlineSalesChargeTier::class);
    }
}
