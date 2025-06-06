<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Illuminate\Support\Collection;

class SellThroughAggregateService
{
    public function addNewEntryForSellThrough(string $date): void
    {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdates = $inventoryUpdateQueries->getDataForSellThroughAggregate($date);

        foreach ($inventoryUpdates as $productId => $inventoryUpdateWithLocationIds) {
            foreach ($inventoryUpdateWithLocationIds as $locationId => $inventoryUpdates) {
                $salesUnitsSoldAndFocUnitsSold = $this->getSalesUnitsSoldAndFocUnitsSold(
                    $productId,
                    $locationId,
                    $date
                );

                $saleReturn = $this->getSaleReturnQuantity($productId, $locationId, $date);
                $ordersUnitsSold = $this->getOrdersUnitsSold($productId, $locationId, $date);

                $sellThroughData = $this->prepareProductInventoryData(
                    $inventoryUpdates,
                    $salesUnitsSoldAndFocUnitsSold,
                    $saleReturn,
                    $ordersUnitsSold,
                    $productId,
                    $locationId,
                    $date
                );

                if ([] !== $sellThroughData) {
                    $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
                    $sellThroughAggregateQueries->updateOrCreate($sellThroughData);
                }
            }
        }
    }

    private function getSalesUnitsSoldAndFocUnitsSold(int $productId, int $locationId, string $date): Collection
    {
        $saleQueries = resolve(SaleQueries::class);

        return $saleQueries->getSalesUnitsSoldAndFocUnitsSold($productId, $locationId, $date);
    }

    private function getSaleReturnQuantity(int $productId, int $locationId, string $date): Collection
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return $saleReturnQueries->getSaleReturnQuantity($productId, $locationId, $date);
    }

    private function getOrdersUnitsSold(int $productId, int $locationId, string $date): Collection
    {
        $orderQueries = resolve(OrderQueries::class);

        return $orderQueries->getOrdersUnitsSold($productId, $locationId, $date);
    }

    private function prepareProductInventoryData(
        Collection $inventoryUpdates,
        Collection $salesUnitsSoldAndFocUnitsSold,
        Collection $saleReturn,
        Collection $ordersUnitsSold,
        int $productId,
        int $locationId,
        string $date
    ): array {
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $inventoryUpdateClosingStock = $inventoryUpdateQueries->getLatestClosingStockForSellThroughAggregate(
            $date,
            $productId,
            $locationId
        );

        return [
            'date' => $date,
            'product_id' => $productId,
            'location_id' => $locationId,
            'goods_receive_note_in' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
            )->where('quantity', '>', 0)->sum('quantity'),
            'goods_receive_note_out' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
            )->where('quantity', '<', 0)->sum('quantity'),
            'stock_adjustment_in' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::STOCK_ADJUSTMENT_ITEM->name
            )->where('quantity', '>', 0)->sum('quantity'),
            'stock_adjustment_out' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::STOCK_ADJUSTMENT_ITEM->name
            )->where('quantity', '<', 0)->sum('quantity'),
            'stock_transfer_in' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::STOCK_TRANSFER_ITEM->name
            )->where('quantity', '>', 0)->sum('quantity'),
            'stock_transfer_out' => $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::STOCK_TRANSFER_ITEM->name
            )->where('quantity', '<', 0)->sum('quantity'),
            'delivery_order_in' => ($inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
            )->where('quantity', '>', 0)->sum('quantity') +
            $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->name
            )->where('quantity', '>', 0)->sum('quantity')),
            'delivery_order_out' => (
                $inventoryUpdates->where(
                    'affected_by_type',
                    ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                )->where('quantity', '<', 0)->sum('quantity') +
            $inventoryUpdates->where(
                'affected_by_type',
                ModelMapping::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->name
            )->where('quantity', '<', 0)->sum('quantity')
            ),
            'foc_sold' => $salesUnitsSoldAndFocUnitsSold->sum('foc_units_sold'),
            'sold' => $salesUnitsSoldAndFocUnitsSold->sum('units_sold'),
            'total_amount' => $salesUnitsSoldAndFocUnitsSold->sum('total_price_paid'),
            'sold_online' => $ordersUnitsSold->sum('units_sold'),
            'foc_sold_online' => $ordersUnitsSold->sum('foc_units_sold'),
            'total_online_sold_amount' => $ordersUnitsSold->sum('total_price_paid'),
            'return' => $saleReturn->sum('return_units'),
            'total_return_amount' => $saleReturn->sum('total_return_amount'),
            'balance' => $inventoryUpdateClosingStock->sum('quantity'),
        ];
    }

    public function getColumnTotalsAndColSpan(Collection $consolidateSalesData, Collection $exportColumnsData): array
    {
        $totals = [
            'received' => $consolidateSalesData->sum('received'),
            'sold' => $consolidateSalesData->sum('sold'),
            'online_sold' => $consolidateSalesData->sum('online_sold'),
            'net_sale_amount' => $consolidateSalesData->sum('net_sale_amount'),
            'online_sale_amount' => $consolidateSalesData->sum('online_sale_amount'),
            'balance' => $consolidateSalesData->sum('balance'),
            'sell_through' => $this->getTotalSellThrough($consolidateSalesData),
        ];

        $columns = CommonFunctions::getExportColumnsArray($exportColumnsData);

        $totalsKeys = array_keys($totals);

        $colspan = collect($columns)
            ->pluck('key')
            ->reject(fn ($key): bool => in_array($key, $totalsKeys))
            ->count();

        return [$columns, $totals, $colspan];
    }

    private function getTotalSellThrough(Collection $consolidateSalesData): float
    {
        if ((float) $consolidateSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateSalesData->sum('sold') + $consolidateSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateSalesData->sum('received')
        );
    }
}
