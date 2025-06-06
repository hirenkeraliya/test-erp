<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemAssemblyChildProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_item_id', 'child_product_id', 'units'];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $this->refresh();

        $saleItemAssemblyChildProduct = $this->load([
            'saleItem:' . $saleItemQueries->getSaleIdColumn(),
            'saleItem.sale:' . $saleQueries->getOfflineSaleId(),
        ]);

        /** @var SaleItem $saleItem */
        $saleItem = $saleItemAssemblyChildProduct->saleItem;

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        return $sale->offline_sale_id;
    }
}
