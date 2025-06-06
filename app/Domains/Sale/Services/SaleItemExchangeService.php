<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemExchange\SaleItemExchangeQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;

class SaleItemExchangeService
{
    public function saveSaleItemAndReturnItemDetails(SaleItem $saleItem, int $saleReturnItemId): void
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleItemExchangeQueries = resolve(SaleItemExchangeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);

        /** @var SaleReturnItem $saleReturnItem */
        $saleReturnItem = $saleReturnItemQueries->getByIdWithRelation($saleReturnItemId);

        /** @var SaleItem $oldSaleItem */
        $oldSaleItem = $saleReturnItem->saleItem;

        $oldItemTax = $oldSaleItem->total_tax_amount / $oldSaleItem->quantity;
        $currentItemTax = $saleItem->total_tax_amount;
        $taxDifferences = ($oldItemTax * $saleItem->quantity) - $currentItemTax;
        $oldItemPrice = $oldSaleItem->original_price_per_unit;
        $currentItemPrice = $saleItem->original_price_per_unit;
        $priceDifferences = ($saleItem->original_price_per_unit - $oldSaleItem->original_price_per_unit) * $saleItem->quantity;

        $oldDiscountAmount = ($oldSaleItem->total_discount_amount / $oldSaleItem->quantity) * $saleItem->quantity;

        $saleItemExchangeData = [
            'sale_item_id' => $saleItem->id,
            'old_item_price' => $oldItemPrice,
            'current_item_price' => $currentItemPrice,
            'price_difference' => $priceDifferences,
            'old_discount_amount' => $oldDiscountAmount,
            'old_item_tax' => $oldItemTax,
            'current_item_tax' => $currentItemTax,
            'tax_difference' => $taxDifferences,
        ];

        $saleItemExchangeId = $saleItemExchangeQueries->addNew($saleItemExchangeData);

        $discountAmount = $priceDifferences + $oldDiscountAmount;

        $totalDiscountAmount = $this->getTotalDiscountAmount($discountAmount, $taxDifferences);

        if (0.00 !== $totalDiscountAmount) {
            $saleItemDiscountQueries->addNew(
                $saleItem->id,
                $saleItemExchangeId,
                ModelMapping::SALE_ITEM_EXCHANGE->name,
                $totalDiscountAmount
            );
        }

        $saleItemQueries->update(
            $saleItem->id,
            (float) $taxDifferences,
            (float) $currentItemTax,
            $totalDiscountAmount,
            $oldSaleItem
        );
    }

    public function getTotalDiscountAmount(float $discountAmount, float $taxDifferences): float
    {
        $signed = '';
        if ($discountAmount < 0) {
            $signed = '-';
        }

        // It is imperative to employ the abs() function in order to accurately calculate tax as negative values may arise. The minus minus plus rule must be applied in such cases to ensure correct financial reporting and compliance.
        return (float) ($signed . (abs($discountAmount) + abs($taxDifferences)));
    }
}
