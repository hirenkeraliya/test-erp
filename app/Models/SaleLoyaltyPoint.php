<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SaleLoyaltyPoint extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_id', 'sale_id', 'loyalty_points', 'amount'];

    public function getName(): string
    {
        return 'Sale Loyalty Point';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItemDiscount(): MorphOne
    {
        return $this->morphOne(SaleItemDiscount::class, 'discountable');
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);

        $this->refresh();
        if ($this->sale_id) {
            $saleLoyaltyPoint = $this->load('sale:' . $saleQueries->getOfflineSaleId());

            /** @var Sale $sale */
            $sale = $saleLoyaltyPoint->sale;

            return $sale->offline_sale_id;
        }

        $saleLoyaltyPoint = $this->load([
            'saleItemDiscount:' . $saleItemDiscountQueries->getBasicColumnNames(),
            'saleItemDiscount.saleItem:' . $saleItemQueries->getSaleIdColumn(),
            'saleItemDiscount.saleItem.sale:' . $saleQueries->getOfflineSaleId(),
        ]);

        $saleItemDiscount = $saleLoyaltyPoint->saleItemDiscount;
        if (! $saleItemDiscount) {
            return null;
        }

        $saleItem = $saleItemDiscount->saleItem;
        if (! $saleItem) {
            return null;
        }

        $sale = $saleItem->sale;
        if (! $sale) {
            return null;
        }

        return $sale->offline_sale_id;
    }
}
