<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PurchasePlan extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'plan_number',
        'company_id',
        'vendor_id',
        'location_id',
        'total_amount',
        'remarks',
        'status',
    ];

    // Can be Store, Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchasePlanItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PurchasePlanTransaction::class);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
