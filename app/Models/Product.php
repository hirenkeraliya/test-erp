<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Events\EcommerceProductDeleteEvent;
use App\Domains\Product\Events\EcommerceProductUpdateEvent;
use App\Domains\Product\Events\ProductCreateEvent;
use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Http\Traits\DiskBasedFirstMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use SoftDeletes;
    use DiskBasedFirstMediaUrl;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'compound_product_name',
        'code',
        'unit_of_measure_id',
        'season_id',
        'department_id',
        'sub_department_id',
        'color_id',
        'size_id',
        'brand_id',
        'style_id',
        'retail_planning_hierarchy_id',
        'upc',
        'verification_qr_code',
        'ean',
        'custom_sku',
        'manufacturer_sku',
        'article_number',
        'type_id',
        'retail_price',
        'franchise_price_1',
        'franchise_price_2',
        'franchise_price_3',
        'wholesale_price',
        'company_or_tender_price',
        'branch_price',
        'minimum_price',
        'original_capital_price',
        'capital_price',
        'staff_price',
        'purchase_cost',
        'created_by_id',
        'created_by_type',
        'is_temporarily_unavailable',
        'has_batch',
        'status',
        'is_non_inventory',
        'is_non_selling_item',
        'is_available_in_pos',
        'is_available_in_ecommerce',
        'online_price',
        'original_created_at',
        'is_warranty',
        'warranty_month',
        'vendor_id',
        'master_product_id',
        'is_sold_as_single_item',
        'last_editor_by_id',
        'last_editor_by_type',
        'sell_item_via_derivative',
        'height',
        'width',
        'weight',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_temporarily_unavailable' => 'boolean',
        'has_batch' => 'boolean',
        'is_non_inventory' => 'boolean',
        'is_non_selling_item' => 'boolean',
        'is_available_in_pos' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
        'is_warranty' => 'boolean',
        'is_sold_as_single_item' => 'boolean',
        'sell_item_via_derivative' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // If you need to retrieve inventory for a particular location, utilize this relation and include a condition for the specific location in your with or whereHas method.
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    // If you want inventories for all locations then only use this relation
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function latestInventoryUpdate(): HasOne
    {
        return $this->hasOne(InventoryUpdate::class)->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function inventoryUpdates(): HasMany
    {
        return $this->hasMany(InventoryUpdate::class);
    }

    public function mergeProductTransactions(): HasMany
    {
        return $this->hasMany(MergeProductTransaction::class, 'new_product_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4', 'video/avi', 'video/mpeg']);

        $this->addMediaCollection('thumbnail')
                    ->singleFile()
                    ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('social_share')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', Statuses::ACTIVE->value);
    }

    public function scopeIsSellingProduct(Builder $query): void
    {
        $query->where('is_non_selling_item', false);
    }

    public function scopeIsInventoryProduct(Builder $query): void
    {
        $query->where('is_non_inventory', false);
    }

    public function scopeOnlyArchived(Builder $query): void
    {
        $query->where('status', Statuses::ARCHIVED->value);
    }

    public function scopeIsAvailableInPos(Builder $query): void
    {
        $query->where('is_available_in_pos', true);
    }

    public function scopeIsAvailableInEcommerce(Builder $query): void
    {
        $query->where('is_available_in_ecommerce', true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUpc(): string
    {
        return $this->upc;
    }

    public function getRetailPrice(): float
    {
        return (float) $this->retail_price;
    }

    public function getArticleNumber(): ?string
    {
        return $this->article_number;
    }

    public function getSubDepartmentId(): ?int
    {
        return $this->sub_department_id;
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(ProductLoyaltyPoint::class);
    }

    public function assemblyChildProducts(): HasMany
    {
        return $this->hasMany(AssemblyChildProduct::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(BoxProduct::class);
    }

    public function productCollectionProducts(): HasMany
    {
        return $this->hasMany(ProductCollectionProduct::class);
    }

    public function productChannelReferences(): HasMany
    {
        return $this->hasMany(ProductChannelReference::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function lastEditorBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function draftProductTransaction(): HasOne
    {
        return $this->hasOne(DraftProductTransaction::class);
    }

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function productVariantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function productVariantValue(): HasOne
    {
        return $this->hasOne(ProductVariantValue::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($product): void {
            if (request()->hasFile('thumbnail') && $product->hasMedia('social_share')) {
                $product->clearMediaCollection('social_share');
            }
        });
        static::updated(function ($product): void {
            if ($product->isDirty('is_available_in_ecommerce')) {
                $oldValue = $product->getOriginal('is_available_in_ecommerce');
                $newValue = $product->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $productEcommerceService = resolve(ProductEcommerceService::class);
                    $productEcommerceService->unAvailableProductInCommerce($product);

                    return;
                }
            }

            event(new EcommerceProductUpdateEvent($product));

            if ($product->isDirty('name') && $product->hasMedia('social_share')) {
                $product->clearMediaCollection('social_share');
            }

            event(new ProductUpdateEvent($product, $product->isDirty('size_id'), $product->isDirty('color_id')));
        });

        static::created(function ($product): void {
            event(new ProductCreateEvent($product));
        });

        static::deleted(function ($product): void {
            if (config('app.product_variant')) {
                event(new EcommerceProductDeleteEvent($product));
            }
        });
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    public function attachedTemplates(): MorphMany
    {
        return $this->morphMany(AttachedTemplate::class, 'model');
    }

    public function productChannelReference(): HasOne
    {
        return $this->hasOne(ProductChannelReference::class);
    }
}
