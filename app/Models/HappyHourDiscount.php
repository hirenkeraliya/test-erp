<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HappyHourDiscount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'location_id',
        'product_type_id',
        'name',
        'new_price',
        'start_date',
        'end_date',
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function happyHourDiscountTransaction(): HasOne
    {
        return $this->hasOne(HappyHourDiscountTransaction::class)->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function happyHourDiscountTransactions(): HasMany
    {
        return $this->hasMany(HappyHourDiscountTransaction::class);
    }
}
