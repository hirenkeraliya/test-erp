<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SaleItem extends Model
{
    // If this model is being renamed/relocated, please update existing DB records as it is used as morph column.

    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id', 'product_id', 'derivative_id', 'sale_return_item_id', 'quantity', 'returned_quantity', 'original_price_per_unit', 'cart_discount_amount', 'item_discount_amount', 'total_discount_amount', 'total_tax_amount', 'price_paid_per_unit', 'total_price_paid', 'group_id', 'is_exchange', 'discount_item_sequence', 'box_product_id', 'product_box_package_type_id', 'product_box_units', 'vendor_commission_percentage', 'price_based_on_derivative', 'quantity_of_derivative', 'price_paid_of_derivative',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_exchange' => 'boolean',
    ];

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class, 'sale_item_promoter');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function derivatives(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureDerivative::class, 'derivative_id');
    }

    public function saleItemUnits(): HasMany
    {
        return $this->hasMany(SaleItemUnit::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'original_sale_item_id');
    }

    public function saleReturnItem(): BelongsTo
    {
        return $this->belongsTo(SaleReturnItem::class);
    }

    public function saleItemDiscounts(): HasMany
    {
        return $this->hasMany(SaleItemDiscount::class);
    }

    public function saleItemDiscount(): BelongsTo
    {
        return $this->belongsTo(SaleItemDiscount::class);
    }

    public function saleItemComplimentary(): HasOne
    {
        return $this->hasOne(SaleItemComplimentary::class);
    }

    public function boxProduct(): BelongsTo
    {
        return $this->belongsTo(BoxProduct::class);
    }

    public function saleItemAssemblyChildProducts(): HasMany
    {
        return $this->hasMany(SaleItemAssemblyChildProduct::class);
    }

    public function saleItemPriceOverride(): HasOne
    {
        return $this->hasOne(SaleItemPriceOverride::class);
    }

    public function promoterCommissionUpdate(): MorphOne
    {
        return $this->morphOne(PromoterCommissionUpdate::class, 'affected_by');
    }

    public function loyaltyPointUpdates(): MorphMany
    {
        return $this->morphMany(LoyaltyPointUpdate::class, 'affected_by');
    }

    public function getGrossSales(): int|float
    {
        return $this->total_price_paid + $this->total_discount_amount - $this->total_tax_amount;
    }

    public function getSubTotal(): float
    {
        return (float) ($this->original_price_per_unit * $this->quantity);
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getReturnedQuantity(): float
    {
        return (float) $this->returned_quantity;
    }

    public function getSaleId(): int
    {
        return $this->sale_id;
    }

    public function getPricePaidPerUnit(): float
    {
        return (float) $this->price_paid_per_unit;
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    public function getTotalPricePaid(): float
    {
        return (float) $this->total_price_paid;
    }

    public function getOriginalPricePerUnit(): float
    {
        return (float) $this->original_price_per_unit;
    }

    public function calculateFinalSaleItemAmount(): float
    {
        return (((float) $this->original_price_per_unit * (float) $this->quantity) - (float) $this->total_discount_amount) + (float) $this->total_tax_amount;
    }

    public function scopeIsNotExchange(Builder $query): Builder
    {
        return $query->where('is_exchange', false)
            ->whereNull('sale_return_item_id');
    }

    public function scopeIsExchange(Builder $query): Builder
    {
        return $query->where('is_exchange', true)
            ->whereNotNull('sale_return_item_id');
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $saleItem = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        return $sale->offline_sale_id;
    }
}
