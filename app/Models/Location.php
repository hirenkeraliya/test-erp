<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Interfaces\InventoryLocationsInterface;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\Events\LocationCreateEvent;
use App\Domains\Location\Events\LocationUpdateEvent;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nnjeim\World\Models\Country;

class Location extends Model implements InventoryLocationsInterface
{
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'type_id',
        'name',
        'code',
        'company_id',
        'region_id',
        'country_id',
        'state_id',
        'city_id',
        'registration_number',
        'sst_number',
        'email',
        'phone',
        'mobile',
        'fax',
        'address_line_1',
        'address_line_2',
        'area_code',
        'web_site',
        'sales_tax_percentage',
        'sales_return_days_limit',
        'credit_note_expiration_days',
        'loyalty_point_expiration_days',
        'is_automatic_day_close',
        'automatic_day_close_time',
        'receipt_footer',
        'disclaimer',
        'cash_out_limit_info',
        'cash_out_limit_warning',
        'cash_out_limit_restrict',
        'enable_ioi_city_mall_data_sharing',
        'ioi_city_mall_machine_id',
        'enable_trx_mall_data_sharing',
        'trx_mall_machine_id',
        'price_fall_down_percentage',
        'share_inventory_to_external_companies',
        'open_time',
        'close_time',
        'status',
        'ref_id',
        'ref_type',
        'uuid',
        'minimum_stock_threshold',
        'maximum_stock_threshold',
        'latitude',
        'longitude',
        'is_email_verified',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_automatic_day_close' => 'boolean',
        'enable_ioi_city_mall_data_sharing' => 'boolean',
        'enable_trx_mall_data_sharing' => 'boolean',
        'share_inventory_to_external_companies' => 'boolean',
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddressLine1(): string
    {
        return $this->address_line_1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->address_line_2;
    }

    public function getAreaCode(): string
    {
        return $this->area_code;
    }

    public function getReceiptFooter(): ?string
    {
        return $this->receipt_footer;
    }

    public function getDisclaimer(): ?string
    {
        return $this->disclaimer;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getNameWithCode(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function getNameWithType(): string
    {
        return $this->name . ' (' . LocationTypes::getFormattedCaseName($this->type_id) . ')';
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function storeManagers(): BelongsToMany
    {
        return $this->belongsToMany(StoreManager::class);
    }

    public function warehouseManagers(): BelongsToMany
    {
        return $this->belongsToMany(WarehouseManager::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function ecommerceLocation(): HasOne
    {
        return $this->hasOne(EcommerceLocation::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($location): void {
            if ($location->isDirty('email')) {
                $location->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($location->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }

            event(new LocationUpdateEvent($location));
        });

        static::created(function ($location): void {
            EmailVerificationJob::dispatch($location)->delay(now()->addSeconds(10))->onQueue('high');
            event(new LocationCreateEvent($location));
        });
    }
}
