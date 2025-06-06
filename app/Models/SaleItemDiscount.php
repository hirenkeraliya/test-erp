<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleItemDiscount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_item_id', 'discountable_type', 'discountable_id', 'amount', 'promo_code'];

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function getDiscountableType(): string
    {
        return $this->discountable_type;
    }

    // Can be Promotion, DreamPrice, Complimentary Item, Price Override, Sale Item Exchange, Happy Hour Discount
    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $this->refresh();
        $saleItemDiscount = $this->load([
            'saleItem:' . $saleItemQueries->getSaleIdColumn(),
            'saleItem.sale:' . $saleQueries->getOfflineSaleId(),
        ]);

        /** @var SaleItem $saleItem */
        $saleItem = $saleItemDiscount->saleItem;

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        return $sale->offline_sale_id;
    }
}
