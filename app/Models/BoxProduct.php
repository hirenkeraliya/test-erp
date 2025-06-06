<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoxProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'package_type_id',
        'units',
        'retail_price',
        'staff_price',
        'minimum_price',
        'purchase_cost',
        'wholesale_price',
    ];

    public function packageType(): BelongsTo
    {
        return $this->belongsTo(PackageType::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function boxProductLoyaltyPoints(): HasMany
    {
        return $this->hasMany(BoxProductLoyaltyPoint::class);
    }
}
