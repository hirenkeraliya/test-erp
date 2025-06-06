<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Cashback extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'exclude_by_type',
        'name',
        'discount_type_id',
        'discount_value',
        'minimum_spend_amount',
        'start_date',
        'end_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function saleDiscountCashback(): MorphMany
    {
        return $this->MorphMany(SaleDiscount::class, 'discountable');
    }

    public function saleItemDiscountCashback(): MorphMany
    {
        return $this->MorphMany(SaleItemDiscount::class, 'discountable');
    }

    public function cashbackPrices(): HasMany
    {
        return $this->hasMany(CashbackPrice::class);
    }
}
