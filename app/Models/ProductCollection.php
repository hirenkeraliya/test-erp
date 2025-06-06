<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\Events\ProductCollectionDeleteEvent;
use App\Domains\ProductCollection\Events\ProductCollectionUpdateEvent;
use App\Domains\ProductCollection\Services\ProductCollectionEcommerceService;
use App\Http\Traits\DiskBasedFirstMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductCollection extends Model implements HasMedia
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
        'number_of_products',
        'pending_products',
        'logical_connector_type_id',
        'last_sync_at',
        'status',
        'created_by_type',
        'created_by_id',
        'is_available_in_ecommerce',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'logical_connector_type_id' => LogicalConnectorTypes::class,
        'is_available_in_ecommerce' => 'boolean',
    ];

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function productCollectionFilter(): HasMany
    {
        return $this->hasMany(ProductCollectionFilter::class);
    }

    public function productCollectionProducts(): HasMany
    {
        return $this->hasMany(ProductCollectionProduct::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')->latest();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('portrait_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('landscape_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('square_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($productCollection): void {
            if ($productCollection->isDirty('is_available_in_ecommerce')) {
                $oldValue = $productCollection->getOriginal('is_available_in_ecommerce');
                $newValue = $productCollection->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $productCollectionEcommerceService = resolve(ProductCollectionEcommerceService::class);
                    $productCollectionEcommerceService->unAvailableProductCollectionInCommerce($productCollection->id);

                    return;
                }
            }

            event(new ProductCollectionUpdateEvent($productCollection));
        });

        static::deleted(function ($productCollection): void {
            event(new ProductCollectionDeleteEvent($productCollection));
        });
    }
}
