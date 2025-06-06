<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\Interfaces\SaleItemDiscountInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemExchange extends Model implements SaleItemDiscountInterface
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_item_id',
        'old_item_price',
        'current_item_price',
        'price_difference',
        'old_discount_amount',
        'old_item_tax',
        'current_item_tax',
        'tax_difference',
    ];

    public function getName(): string
    {
        return 'Sale Item Exchange';
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
        $saleItemExchange = $this->load([
            'saleItem:' . $saleItemQueries->getSaleIdColumn(),
            'saleItem.sale:' . $saleQueries->getOfflineSaleId(),
        ]);

        /** @var SaleItem $saleItem */
        $saleItem = $saleItemExchange->saleItem;

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        return $sale->offline_sale_id;
    }
}
