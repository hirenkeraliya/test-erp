<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\Jobs\SaleChannelSyncTriggerJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SaleChannel extends Authenticatable
{
    use HasFactory;
    use CaseSensitiveConditionals;
    use HasApiTokens;
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'company_id',
        'default_location_id',
        'type_id',
        'url',
        'secret',
        'inventory_deduct_order_status',
        'status',
        'display_variants',
        'display_dynamic_menus',
        'round_off_configuration',
    ];

    protected $casts = [
        'type_id' => SaleChannelTypes::class,
        'inventory_deduct_order_status' => OrderStatus::class,
        'status' => 'boolean',
        'display_variants' => 'boolean',
        'display_dynamic_menus' => 'boolean',
    ];

    // Relations
    public function saleChannelInventoryRollbackOrderStatus(): HasMany
    {
        return $this->hasMany(SaleChannelInventoryRollbackOrderStatus::class);
    }

    public function saleChannelWebhookUrls(): HasMany
    {
        return $this->hasMany(SaleChannelWebhookUrl::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function syncTransactions(): HasMany
    {
        return $this->hasMany(SyncTransaction::class);
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function getDefaultLocationId(): int
    {
        return $this->default_location_id;
    }

    public function getType(): SaleChannelTypes
    {
        return $this->type_id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getInventoryDeductOrderStatus(): OrderStatus
    {
        return $this->inventory_deduct_order_status;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($saleChannel): void {
            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                SaleChannelSyncTriggerJob::dispatch($saleChannel);
            }
        });

        static::created(function ($saleChannel): void {
            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                SaleChannelSyncTriggerJob::dispatch($saleChannel);
            }
        });
    }
}
