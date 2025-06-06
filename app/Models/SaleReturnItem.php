<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\SaleReturn\SaleReturnQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleReturnItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_return_id', 'original_sale_item_id', 'product_id', 'quantity', 'total_price_paid', 'cart_discount_amount', 'item_discount_amount', 'total_discount_amount', 'total_tax_amount', 'sale_return_reason_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleReturnReason(): BelongsTo
    {
        return $this->belongsTo(SaleReturnReason::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'original_sale_item_id');
    }

    public function exchangeItem(): HasOne
    {
        return $this->hasOne(SaleItem::class, 'sale_return_item_id');
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
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

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $this->refresh();
        $saleReturnItem = $this->load('saleReturn:' . $saleReturnQueries->getOfflineColumn());

        /** @var SaleReturn $saleReturn */
        $saleReturn = $saleReturnItem->saleReturn;

        return $saleReturn->offline_sale_return_id;
    }
}
