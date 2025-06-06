<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VoidSale extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_id', 'void_sale_number', 'voided_by_store_manager_id', 'void_sale_reason_id'];

    public function voidSaleReason(): BelongsTo
    {
        return $this->belongsTo(VoidSaleReason::class);
    }

    public function voidedByStoreManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class, 'voided_by_store_manager_id', 'id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function getVoidSaleNumber(string $companyPrefix): string
    {
        return $companyPrefix . $this->void_sale_number;
    }

    public function loyaltyPointUpdates(): MorphMany
    {
        return $this->morphMany(LoyaltyPointUpdate::class, 'affected_by');
    }

    public function inventoryUpdates(): MorphMany
    {
        return $this->morphMany(InventoryUpdate::class, 'affected_by');
    }
}
