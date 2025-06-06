<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\Interfaces\SaleItemDiscountInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleItemPriceOverride extends Model implements SaleItemDiscountInterface
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_item_id', 'negotiator_id', 'negotiator_type', 'override_price'];

    // It can be Director, Store Manager & Cashier
    public function negotiator(): MorphTo
    {
        return $this->morphTo();
    }

    public function getName(): string
    {
        return 'Price Override';
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $this->refresh();
        $saleItemPriceOverride = $this->load([
            'saleItem:' . $saleItemQueries->getSaleIdColumn(),
            'saleItem.sale:' . $saleQueries->getOfflineSaleId(),
        ]);

        /** @var SaleItem $saleItem */
        $saleItem = $saleItemPriceOverride->saleItem;

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        return $sale->offline_sale_id;
    }
}
