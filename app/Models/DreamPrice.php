<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\DreamPrice\Events\DreamPriceCreateEvent;
use App\Domains\DreamPrice\Events\DreamPriceUpdateEvent;
use App\Domains\DreamPrice\Jobs\DreamPriceUpdateJob;
use App\Domains\DreamPrice\Services\DreamPriceEcommerceService;
use App\Domains\SaleItemDiscount\Interfaces\SaleItemDiscountInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DreamPrice extends Model implements SaleItemDiscountInterface
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'company_id',
        'name',
        'start_date',
        'end_date',
        'created_by_id',
        'created_by_type',
        'allow_registered_member',
        'allow_employee',
        'is_available_in_ecommerce',
        'is_available_in_pos',
        'allow_walk_in_member',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'allow_registered_member' => 'boolean',
        'status' => 'boolean',
        'allow_employee' => 'boolean',
        'allow_walk_in_member' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
        'is_available_in_pos' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function dreamPriceProducts(): HasMany
    {
        return $this->hasMany(DreamPriceProduct::class);
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')
            ->latest();
    }

    public function memberGroups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class);
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function employeeGroups(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeGroup::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function saleDiscountDreamPrice(): MorphMany
    {
        return $this->MorphMany(SaleDiscount::class, 'discountable');
    }

    public function saleItemDiscountDreamPrice(): MorphMany
    {
        return $this->MorphMany(SaleItemDiscount::class, 'discountable');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($dreamPrice): void {
            event(new DreamPriceCreateEvent($dreamPrice));
        });

        static::updated(function ($dreamPrice): void {
            if ($dreamPrice->isDirty('is_available_in_ecommerce')) {
                $oldValue = $dreamPrice->getOriginal('is_available_in_ecommerce');
                $newValue = $dreamPrice->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $dreamPriceEcommerceService = resolve(DreamPriceEcommerceService::class);
                    $dreamPriceEcommerceService->unAvailableDreamPriceInCommerce($dreamPrice->id);

                    return;
                }
            }

            if ($dreamPrice->isDirty('status')) {
                DreamPriceUpdateJob::dispatch($dreamPrice, $dreamPrice->status)->onQueue('high');
            }

            event(new DreamPriceUpdateEvent($dreamPrice));
        });
    }
}
