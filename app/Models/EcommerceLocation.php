<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['location_id', 'url', 'client_secret', 'inventory_deduct_order_status'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function inventoryRollbackOrderStatuses(): HasMany
    {
        return $this->hasMany(InventoryRollbackOrderStatus::class);
    }
}
