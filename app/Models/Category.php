<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Category\Events\CategoryCreateEvent;
use App\Domains\Category\Events\CategoryUpdateEvent;
use App\Domains\Category\Services\CategoryEcommerceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'parent_category_id',
        'code',
        'description',
        'status',
        'is_available_in_ecommerce',
        'is_display_on_menu',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
        'is_display_on_menu' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(self::class, 'parent_category_id')->select(
            ['id', 'name', 'code', 'parent_category_id', 'company_id']
        );
    }

    public function categoryChannelReferences(): HasMany
    {
        return $this->hasMany(CategoryChannelReference::class);
    }

    public function children(): HasMany
    {
        return $this->categories()->with('children');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', true);
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

        static::updated(static function ($category): void {
            if ($category->isDirty('is_available_in_ecommerce')) {
                $oldValue = $category->getOriginal('is_available_in_ecommerce');
                $newValue = $category->is_available_in_ecommerce;

                if (true === $oldValue && false === $newValue) {
                    $categoryEcommerceService = resolve(CategoryEcommerceService::class);
                    $categoryEcommerceService->unAvailableCategoryInCommerce($category->id);

                    return;
                }
            }

            event(new CategoryUpdateEvent($category));
        });

        static::created(static function ($category): void {
            event(new CategoryCreateEvent($category));
        });
    }
}
