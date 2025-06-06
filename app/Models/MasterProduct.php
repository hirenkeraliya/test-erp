<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\MasterProduct\Events\MasterProductCreateEvent;
use App\Domains\MasterProduct\Events\MasterProductUpdateEvent;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Http\Traits\DiskBasedFirstMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MasterProduct extends Model implements HasMedia
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
        'brand_id',
        'name',
        'code',
        'description',
        'vendor_id',
        'unit_of_measure_id',
        'article_number',
        'type_id',
        'has_batch',
        'is_non_inventory',
        'is_non_selling_item',
        'original_created_at',
        'created_by_id',
        'created_by_type',
        'status',
        'variant_template_id',
        'department_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'has_batch' => 'boolean',
        'is_non_inventory' => 'boolean',
        'is_non_selling_item' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function variantTemplate(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function assemblyChildMasterProducts(): HasMany
    {
        return $this->hasMany(AssemblyChildMasterProduct::class);
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    public function attachedTemplates(): MorphMany
    {
        return $this->morphMany(AttachedTemplate::class, 'model');
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', Statuses::ACTIVE->value);
    }

    public function scopeOnlyArchived(Builder $query): void
    {
        $query->where('status', Statuses::ARCHIVED->value);
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
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function masterProductChannelReferences(): HasMany
    {
        return $this->hasMany(MasterProductChannelReference::class);
    }

    public function canSyncToEcommerce(): bool
    {
        return (int) $this->type_id === ProductTypes::REGULAR_PRODUCT->value &&
            null === $this->unit_of_measure_id &&
            false === $this->has_batch &&
            false === $this->is_non_inventory &&
            false === $this->is_non_selling_item;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($masterProduct): void {
            event(new MasterProductUpdateEvent($masterProduct));
        });

        static::created(function ($masterProduct): void {
            if ($masterProduct->canSyncToEcommerce()) {
                event(new MasterProductCreateEvent($masterProduct));
            }
        });
    }
}
