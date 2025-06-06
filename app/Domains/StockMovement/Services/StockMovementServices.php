<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StockMovementServices
{
    public function getOnlyTenStockMovement(
        array $labels,
        array $saleThrough,
        float $totalSaleThroughAfterTen
    ): array {
        $newLabels = null;
        $newTotalSales = null;
        $labelsSlice = array_slice($labels, 0, 10);
        $saleThroughSlice = array_slice($saleThrough, 0, 10);

        $otherLabel = 'Other';
        $otherTotalSales = $totalSaleThroughAfterTen;

        if (0.0 !== $otherTotalSales) {
            $newLabels = [...$labelsSlice, $otherLabel];
            $newTotalSales = [...$saleThroughSlice, $otherTotalSales];
        }

        return [
            'labels' => 0.0 !== $otherTotalSales ? $newLabels : $labelsSlice,
            'sell_through' => 0.0 !== $otherTotalSales ? $newTotalSales : $saleThroughSlice,
        ];
    }

    public function getOnlyTenStockMovementForColor(
        array $labels,
        array $saleThrough,
        float $totalSaleThroughAfterTen
    ): array {
        /** @var string $jsonData */
        $jsonData = file_get_contents(base_path('/resources/js/common/vendor/corporaColorXkcd.json'));
        $colorCodes = json_decode($jsonData, true);

        $newLabels = null;
        $newTotalSales = null;
        $labelsSlice = array_slice($labels, 0, 10);
        $saleThroughSlice = array_slice($saleThrough, 0, 10);

        $colorHexCodes = $this->preparedColors(collect($labelsSlice), $colorCodes);
        $saleThroughSlice = $this->preparedDataWithHexCodes(collect($saleThroughSlice), $colorHexCodes);

        $otherLabel = 'Other';
        $otherTotalSales = $totalSaleThroughAfterTen;

        if (0.0 !== $otherTotalSales) {
            $newLabels = [...$labelsSlice, $otherLabel];
            $newTotalSales = [...$saleThroughSlice, $otherTotalSales];
        }

        return [
            'labels' => 0.0 !== $otherTotalSales ? $newLabels : $labelsSlice,
            'sell_through' => 0.0 !== $otherTotalSales ? $newTotalSales : $saleThroughSlice,
        ];
    }

    public function getOnlyTenStockMovementUPCForColor(
        Collection $productRecords,
        float $totalSaleThroughAfterTen
    ): array {
        $labels = $productRecords->pluck('upc')->toArray();
        $colorName = $productRecords->pluck('color_name')->toArray();
        $saleThrough = $productRecords->pluck('sell_through')->toArray();
        /** @var string $jsonData */
        $jsonData = file_get_contents(base_path('/resources/js/common/vendor/corporaColorXkcd.json'));
        $colorCodes = json_decode($jsonData, true);

        $newLabels = null;
        $newTotalSales = null;
        $labelsSlice = array_slice($labels, 0, 10);
        $saleThroughSlice = array_slice($saleThrough, 0, 10);

        $colorHexCodes = $this->preparedColors(collect($colorName), $colorCodes);
        $saleThroughSlice = $this->preparedDataWithHexCodes(collect($saleThroughSlice), $colorHexCodes);

        $otherLabel = 'Other';
        $otherTotalSales = $totalSaleThroughAfterTen;

        if (0.0 !== $otherTotalSales) {
            $newLabels = [...$labelsSlice, $otherLabel];
            $newTotalSales = [...$saleThroughSlice, $otherTotalSales];
        }

        return [
            'labels' => 0.0 !== $otherTotalSales ? $newLabels : $labelsSlice,
            'sell_through' => 0.0 !== $otherTotalSales ? $newTotalSales : $saleThroughSlice,
        ];
    }

    public function getColorNames(Collection $colorNames): array
    {
        /** @var string $jsonData */
        $jsonData = file_get_contents(base_path('/resources/js/common/vendor/corporaColorXkcd.json'));
        $colorCodes = json_decode($jsonData, true);

        return $this->preparedColors($colorNames, $colorCodes);
    }

    public function getBadgesTotal(Collection $data): array
    {
        return [
            'sold' => $data->sum('sold'),
            'remaining' => $data->sum('balance'),
            'grn_in' => $data->sum('goods_receive_note_in_balance'),
            'grn_out' => $data->sum('goods_receive_note_out_balance'),
            'adjustment_in' => $data->sum('stock_adjustment_in_balance'),
            'adjustment_out' => $data->sum('stock_adjustment_out_balance'),
            'transfer_in' => $data->sum('stock_transfer_in_balance'),
            'transfer_out' => $data->sum('stock_transfer_out_balance'),
            'delivery_in' => $data->sum('delivery_order_in_balance'),
            'delivery_out' => $data->sum('delivery_order_out_balance'),
        ];
    }

    private function preparedColors(Collection $colorNames, array $colorCodes): array
    {
        return $colorNames->map(function ($colorName) use ($colorCodes) {
            $index = array_search(Str::lower($colorName), array_column($colorCodes, 'color'), true);
            $color = $index ? $colorCodes[$index] : null;

            return $color ? Str::upper($color['hex']) : '#c1c1c1';
        })->toArray();
    }

    private function preparedDataWithHexCodes(Collection $records, array $colorHexCodes): array
    {
        return $records->transform(fn ($saleAmount, $index): array => [
            'value' => $saleAmount,
            'itemStyle' => [
                'color' => $colorHexCodes[$index],
            ],
        ])->toArray();
    }
}
