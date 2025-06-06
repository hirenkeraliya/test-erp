<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Sale\Services\SaleItemExchangeService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemExchange\SaleItemExchangeQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;

test(
    'Saving Exchange Details for Sale Item Exchanges',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $oldSaleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $saleReturnItem = SaleReturnItem::factory()->make([
            'sale_return_id' => 1,
            'original_sale_item_id' => 1,
            'product_id' => 1,
            'sale_return_reason_id' => 1,
        ]);

        $saleReturnItem->saleItem = $oldSaleItem;

        $oldItemTax = $oldSaleItem->total_tax_amount / $oldSaleItem->quantity;
        $oldDiscountAmount = ($oldSaleItem->total_discount_amount / $oldSaleItem->quantity) * $saleItem->quantity;

        $saleExchangeItem = [
            'sale_item_id' => $saleItem->id,
            'old_item_price' => $oldSaleItem->original_price_per_unit,
            'current_item_price' => $saleItem->original_price_per_unit,
            'price_difference' => ($saleItem->original_price_per_unit - $oldSaleItem->original_price_per_unit) * $saleItem->quantity,
            'old_discount_amount' => $oldDiscountAmount,
            'old_item_tax' => $oldItemTax,
            'current_item_tax' => $saleItem->total_tax_amount,
            'tax_difference' => ($oldItemTax * $saleItem->quantity) - $saleItem->total_tax_amount,
        ];

        $discountAmount = (($saleItem->original_price_per_unit - $oldSaleItem->original_price_per_unit) * $saleItem->quantity) + $oldDiscountAmount;

        $signed = '';
        if ($discountAmount < 0) {
            $signed = '-';
        }

        // The use of the abs() function is necessary because In some cases tax can be negative And because of that minus minus plus rule can be applied.
        $discountAmountWithTax = (float) ($signed . (abs($discountAmount) + abs(
            ($oldItemTax * $saleItem->quantity) - $saleItem->total_tax_amount
        )));

        $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnItem): void {
            $mock->shouldReceive('getByIdWithRelation')
                ->once()
                ->andReturn($saleReturnItem);
        });

        $this->mock(SaleItemExchangeQueries::class, function ($mock) use ($saleExchangeItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($saleExchangeItem)
                ->andReturn(1);
        });

        $this->mock(SaleItemDiscountQueries::class, function ($mock) use ($saleItem, $discountAmountWithTax): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($saleItem->id, 1, ModelMapping::SALE_ITEM_EXCHANGE->name, $discountAmountWithTax);
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('update')
                ->once();
        });

        $saleItemExchangeService = new SaleItemExchangeService();
        $saleItemExchangeService->saveSaleItemAndReturnItemDetails($saleItem, 1);
    }
);
