<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SellThroughServices
{
    public function getOnlyTenSellThrough(
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

    public function getOnlyTenSellThroughForColor(
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

    public function getOnlyTenSellThroughUPCForColor(
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
