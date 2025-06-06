<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransitStock extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_id',
        'inventory_unit_id',
        'affected_by_id',
        'affected_by_type',
        'quantity',
        'completed_quantity',
        'notes',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function inventoryUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class);
    }

    public function affectedBy(): MorphTo
    {
        // https://stackoverflow.com/a/63458030
        return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function getAffectedById(): int
    {
        return $this->affected_by_id;
    }
}
