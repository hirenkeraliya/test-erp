<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MysteryGift extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'min_flat_amount',
        'max_flat_amount',
        'min_percentage',
        'max_percentage',
        'is_flat_amount',
        'is_percentage',
        'is_free_product',
        'start_date',
        'end_date',
        'minimum_spend',
        'minimum_spend_amount_for_free_product',
        'minimum_spend_amount_for_flat_amount',
        'minimum_spend_amount_for_percentage',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_flat_amount' => 'boolean',
        'is_percentage' => 'boolean',
        'is_free_product' => 'boolean',
        'status' => 'boolean',
    ];

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function voucherConfigurations(): HasMany
    {
        return $this->hasMany(VoucherConfiguration::class);
    }

    public function getTypeAttribute(): array
    {
        $returnTypes = [];
        if (1 == $this->is_flat_amount) {
            $returnTypes[] = 'is_flat_amount';
        }

        if (1 == $this->is_percentage) {
            $returnTypes[] = 'is_percentage';
        }

        if (1 == $this->is_free_product) {
            $returnTypes[] = 'is_free_product';
        }

        return $returnTypes;
    }

    public function mysteryGiftUsage(): HasOne
    {
        return $this->hasOne(MysteryGiftUsage::class);
    }

    public function mysteryGiftProducts(): HasMany
    {
        return $this->hasMany(MysteryGiftProduct::class);
    }
}
