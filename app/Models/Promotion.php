<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Events\PromotionCreateEvent;
use App\Domains\Promotion\Events\PromotionUpdateEvent;
use App\Domains\Promotion\Services\PromotionEcommerceService;
use App\Domains\SaleItemDiscount\Interfaces\SaleItemDiscountInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Promotion extends Model implements SaleItemDiscountInterface
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mystery_gift_id',
        'company_id',
        'promotion_applicable_type_id',
        'discount_type_id',
        'cart_wide_promotion_type_id',
        'item_wise_promotion_type_id',
        'timeframe_type_id',
        'percentage',
        'flat_amount',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'allow_registered_member',
        'allow_employee',
        'is_membership_required',
        'allow_walk_in_member',
        'created_by_id',
        'created_by_type',
        'status',
        'dream_price_applicable',
        'is_automatic',
        'usage_type',
        'is_available_in_pos',
        'is_available_in_ecommerce',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'allow_registered_member' => 'boolean',
        'allow_employee' => 'boolean',
        'allow_walk_in_member' => 'boolean',
        'dream_price_applicable' => 'boolean',
        'is_automatic' => 'boolean',
        'is_available_in_pos' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
        'is_membership_required' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function regularProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->wherePivot('type', ProductUploadTypes::REGULAR->value);
    }

    public function buyProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->wherePivot('type', ProductUploadTypes::BUY_PRODUCT->value);
    }

    public function getProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->wherePivot('type', ProductUploadTypes::GET_PRODUCT->value);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function productCollections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class);
    }

    public function monthly(): HasMany
    {
        return $this->hasMany(PromotionMonthDate::class);
    }

    public function promotionTiers(): HasMany
    {
        return $this->hasMany(PromotionTier::class);
    }

    public function weekly(): HasMany
    {
        return $this->hasMany(PromotionWeekDay::class);
    }

    public function promotionPromoCodes(): HasMany
    {
        return $this->hasMany(PromotionPromoCode::class);
    }

    public function saleItemDiscountPromotionPromoCodes(): MorphMany
    {
        return $this->MorphMany(SaleItemDiscount::class, 'discountable')
            ->whereNotNull('promo_code');
    }

    public function saleDiscountPromotionPromoCodes(): MorphMany
    {
        return $this->MorphMany(SaleDiscount::class, 'discountable')
            ->whereNotNull('promo_code');
    }

    public function saleDiscountPromotion(): MorphMany
    {
        return $this->MorphMany(SaleDiscount::class, 'discountable');
    }

    public function saleItemDiscountPromotion(): MorphMany
    {
        return $this->MorphMany(SaleItemDiscount::class, 'discountable');
    }

    public function uploadedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->wherePivotNull('type');
    }

    public function memberGroups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class);
    }

    public function employeeGroups(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeGroup::class);
    }

    public function getIsMemberRequired(): bool
    {
        return $this->allow_registered_member;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', true);
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function paymentTypes(): BelongsToMany
    {
        return $this->belongsToMany(PaymentType::class);
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($promotion): void {
            event(new PromotionCreateEvent($promotion));
        });

        static::updated(function ($promotion): void {
            if ($promotion->isDirty('is_available_in_ecommerce')) {
                $oldValue = $promotion->getOriginal('is_available_in_ecommerce');
                $newValue = $promotion->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $promotionEcommerceService = resolve(PromotionEcommerceService::class);
                    $promotionEcommerceService->unAvailablePromotionInCommerce($promotion->id);

                    return;
                }
            }

            event(new PromotionUpdateEvent($promotion));
        });
    }
}
