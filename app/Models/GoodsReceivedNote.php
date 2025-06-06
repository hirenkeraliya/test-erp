<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 'vendor_id', 'location_id', 'grn_reference', 'purchase_order_reference', 'delivery_order_reference', 'notes', 'created_by_type', 'created_by_id', 'cancelled_at', 'cancelled_by_type', 'cancelled_by_id',
    ];

    // Can be Store, Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function goodsReceivedNoteProducts(): HasMany
    {
        return $this->hasMany(GoodsReceivedNoteProduct::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')
            ->latest();
    }

    public function cancelledBy(): MorphTo
    {
        return $this->morphTo();
    }
}
