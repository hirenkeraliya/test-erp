<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCollectionFilter extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_collection_id', 'filter_type_id', 'condition_operator_type_id', 'value'];

    public function types(): HasMany
    {
        return $this->hasMany(ProductCollectionFilterType::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class);
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductCollectionFilterAttributeValue::class);
    }
}
