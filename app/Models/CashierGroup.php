<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierGroup extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'price_override_type',
        'price_override_limit_percentage_for_item',
        'price_override_limit_percentage_for_cart',
        'created_by_id',
        'created_by_type',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(CashierGroupPermission::class);
    }
}
