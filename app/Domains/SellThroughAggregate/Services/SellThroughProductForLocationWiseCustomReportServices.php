<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SellThroughAggregate\Exports\SellThroughForLocationWiseCustomReportExport;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughProductForLocationWiseCustomReportServices
{
    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        if (config('app.product_variant')) {
            [$records, $columns, $locationColumns, $variantColumns] = $this->getPreparedData($filterData, $companyId);
        } else {
            [$records, $columns, $colorColumns, $sizeColumns, $locationColumns] = $this->getPreparedData(
                $filterData,
                $companyId
            );
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = '';
        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        if ($locations instanceof Location) {
            $locations = $locations['getNamesWithCodes'];
        }

        if (config('app.product_variant')) {
            return Excel::download(
                new SellThroughForLocationWiseCustomReportExport(
                    $company,
                    $records,
                    $columns,
                    $variantColumns,
                    null,
                    $locationColumns,
                    $filterData['date'],
                    $locations
                ),
                $filename
            );
        }

        return Excel::download(
            new SellThroughForLocationWiseCustomReportExport(
                $company,
                $records,
                $columns,
                $colorColumns,
                $sizeColumns,
                $locationColumns,
                $filterData['date'],
                $locations
            ),
            $filename
        );
    }

    private function getPreparedData(array $filterData, int $companyId): array
    {
        $filterData['color_ids'] = is_array(
            $filterData['color_ids']
        ) && [] !== $filterData['color_ids'] ? $filterData['color_ids'] : null;

        $filterData['location_ids'] = is_array(
            $filterData['location_ids']
        ) && [] !== $filterData['location_ids'] ? $filterData['location_ids'] : null;

        $productQueries = resolve(ProductQueries::class);

        $inventoryData = $productQueries->accumulatedSaleThroughInventoryDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $companyId
        );

        $salesData = $productQueries->accumulatedSaleThroughSalesDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $companyId
        );

        $saleReturnData = $productQueries->accumulatedSaleThroughReturnsDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $companyId
        );

        $consolidateData = $inventoryData->concat($salesData)->concat($saleReturnData);

        if ($consolidateData->isEmpty()) {
            return config('app.product_variant')
                ? [[], [], [], []]
                : [[], [], [], [], []];
        }

        /** @var array $records */
        $records = [];
        $finalTotal = [];
        $locationColumns = [];
        $variantColumns = [];
        $colorWiseTotal = [];
        $colorColumns = [];
        $sizeColumns = [];

        if (config('app.product_variant')) {
            $groupedArticleAndVariants = $consolidateData->groupBy(function ($item): string {
                $article = $item->masterProduct->article_number;
                $variantString = $item->productVariantValues
                    ->sortBy('attribute.name')
                    ->map(fn ($v): string => $v['attribute']['name'] . ':' . $v['value'])
                    ->implode(';');

                return $article . '|' . $variantString;
            });

            foreach ($groupedArticleAndVariants as $articleKey => $group) {
                $first = $group->first();

                $records[$articleKey] = [
                    'name' => $first->name,
                    'image' => $first->image ?? null,
                    'price' => $first->price ?? 0,
                    'attributes' => [],
                ];

                $attributes = $first->productVariantValues->sortBy('attribute.name')
                    ->pluck('attribute.name')
                    ->toArray();

                $attributeTotals = [];

                foreach ($group as $item) {
                    $attributePath = [];
                    $currentLevel = &$records[$articleKey]['attributes'];

                    foreach ($attributes as $index => $attrName) {
                        $attrValue = $item->productVariantValues
                            ->where('attribute.name', $attrName)
                            ->first()
                            ->value ?? 'N/A';

                        $attributePath[] = $attrValue;

                        if (! isset($currentLevel[$attrValue])) {
                            $currentLevel[$attrValue] = [
                                'received' => [],
                                'sold' => [],
                                'returned' => [],
                                'balance' => [],
                                'accumulated_sell_through' => 0,
                            ];
                        }

                        if ($index < count($attributes) - 1) {
                            /* @phpstan-ignore-next-line */
                            if (! isset($currentLevel[$attrValue]['sub_attributes'])) {
                                $currentLevel[$attrValue]['sub_attributes'] = [];
                            }

                            $currentLevel = &$currentLevel[$attrValue]['sub_attributes'];
                        }
                    }

                    $received = $item->received ?? 0;
                    $sold = $item->sold ?? 0;
                    $returned = $item->returned ?? 0;
                    $balance = $received - ($sold - $returned);

                    $attributeKey = implode('|', $attributePath);
                    if (! isset($attributeTotals[$attributeKey])) {
                        $attributeTotals[$attributeKey] = [
                            'received' => 0,
                            'sold' => 0,
                            'returned' => 0,
                            'balance' => 0,
                        ];
                    }

                    $attributeTotals[$attributeKey]['received'] += $received;
                    $attributeTotals[$attributeKey]['sold'] += $sold;
                    $attributeTotals[$attributeKey]['returned'] += $returned;
                    $attributeTotals[$attributeKey]['balance'] += $balance;

                    if (! in_array($item->location_name, $locationColumns)) {
                        $locationColumns[] = $item->location_name;
                    }
                }

                $grandTotal = [
                    'received' => 0,
                    'sold' => 0,
                    'returned' => 0,
                    'balance' => 0,
                    'accumulated_sell_through' => 0,
                ];

                foreach ($attributeTotals as $metrics) {
                    $grandTotal['received'] += $metrics['received'];
                    $grandTotal['sold'] += $metrics['sold'];
                    $grandTotal['returned'] += $metrics['returned'];
                    $grandTotal['balance'] += $metrics['balance'];
                }

                $grandTotal['accumulated_sell_through'] = $this->calculateSellThrough(
                    (float) $grandTotal['received'],
                    (float) $grandTotal['sold'],
                    (float) $grandTotal['returned']
                );

                $records[$articleKey]['grand_total'] = $grandTotal;

                if (! isset($finalTotal['summary_grand_total'])) {
                    $finalTotal['summary_grand_total'] = [
                        'received' => 0,
                        'sold' => 0,
                        'returned' => 0,
                        'balance' => 0,
                        'accumulated_sell_through' => 0,
                    ];
                }

                foreach ($locationColumns as $location) {
                    if (! isset($finalTotal[$location])) {
                        $finalTotal[$location] = [
                            'received' => 0,
                            'sold' => 0,
                            'returned' => 0,
                            'balance' => 0,
                        ];
                    }
                }
            }
        } else {
            $groupedWiseArticleNumberColorSizeAndLocationName = $consolidateData
                ->groupBy(['article_number', 'color_id', 'size_id']);

            foreach ($groupedWiseArticleNumberColorSizeAndLocationName as $articleKey => $groupWiseColorSizeAndLocationName) {
                if (! array_key_exists($articleKey, $records)) {
                    $records[$articleKey] = [
                        'name' => null,
                        'colors' => [],
                    ];
                }

                if (! array_key_exists($articleKey, $colorWiseTotal)) {
                    $colorWiseTotal[$articleKey] = [];
                }

                foreach ($groupWiseColorSizeAndLocationName as $groupWiseSizeAndLocationName) {
                    foreach ($groupWiseSizeAndLocationName as $groupWiseLocationName) {
                        foreach ($groupWiseLocationName as $location) {
                            $colorName = $location?->color?->name ?? 'N/A';
                            $sizeName = $location?->size?->name ?? 'N/A';

                            $received = $location['received'];
                            $sold = $location['sold'];
                            $returned = $location['returned'];

                            $balance = $received - ($sold - $returned);

                            if (! in_array($location['location_name'], $locationColumns)) {
                                $locationColumns[] = $location['location_name'];
                            }

                            if (! in_array($sizeName, $sizeColumns)) {
                                $sizeColumns[] = $sizeName;
                            }

                            if (! in_array($colorName, $colorColumns)) {
                                $colorColumns[] = $colorName;
                            }

                            if (! array_key_exists($colorName, $records[$articleKey]['colors'])) {
                                $records[$articleKey]['colors'][$colorName] = [];
                            }

                            if (! array_key_exists($colorName, $colorWiseTotal[$articleKey])) {
                                $colorWiseTotal[$articleKey][$colorName] = [];
                            }

                            if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName])) {
                                $records[$articleKey]['colors'][$colorName][$sizeName] = [];
                            }

                            if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName])) {
                                $records[$articleKey]['colors'][$colorName][$sizeName] = [];
                            }

                            if (! array_key_exists('color_total', $colorWiseTotal[$articleKey][$colorName])) {
                                $colorWiseTotal[$articleKey][$colorName]['color_total'] = [];
                            }

                            if (! array_key_exists(
                                $location['location_name'],
                                $colorWiseTotal[$articleKey][$colorName]['color_total']
                            )) {
                                $colorWiseTotal[$articleKey][$colorName]['color_total'][$location['location_name']] = [
                                    'received' => 0,
                                    'sold' => 0,
                                    'returned' => 0,
                                    'balance' => 0,
                                ];
                            }

                            if (! array_key_exists(
                                'summary_total',
                                $colorWiseTotal[$articleKey][$colorName]['color_total']
                            )) {
                                $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total'] = [
                                    'received' => 0,
                                    'sold' => 0,
                                    'returned' => 0,
                                    'balance' => 0,
                                    'sell_through' => 0,
                                ];
                            }

                            if (! array_key_exists($location['location_name'], $finalTotal)) {
                                $finalTotal[$location['location_name']] = [
                                    'received' => 0,
                                    'sold' => 0,
                                    'returned' => 0,
                                    'balance' => 0,
                                    'sell_through' => 0,
                                ];
                            }

                            if (! array_key_exists('summary_grand_total', $finalTotal)) {
                                $finalTotal['summary_grand_total'] = [
                                    'received' => 0,
                                    'sold' => 0,
                                    'returned' => 0,
                                    'balance' => 0,
                                    'sell_through' => 0,
                                ];
                            }

                            if (! array_key_exists('summary', $records[$articleKey]['colors'][$colorName][$sizeName])) {
                                $records[$articleKey]['colors'][$colorName][$sizeName]['summary'] = [
                                    'received' => 0,
                                    'sold' => 0,
                                    'returned' => 0,
                                    'balance' => 0,
                                    'sell_through' => 0,
                                ];
                            }

                            if (! array_key_exists(
                                $location['location_name'],
                                $records[$articleKey]['colors'][$colorName][$sizeName]
                            )) {
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']] = [];
                            }

                            if (! array_key_exists(
                                'received',
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]
                            )) {
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['received'] = 0;
                            }

                            if (! array_key_exists(
                                'sold',
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]
                            )) {
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['sold'] = 0;
                            }

                            if (! array_key_exists(
                                'returned',
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]
                            )) {
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['returned'] = 0;
                            }

                            if (! array_key_exists(
                                'balance',
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]
                            )) {
                                $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['balance'] = 0;
                            }

                            $records[$articleKey]['name'] = $location['name'];
                            $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['received'] = $received;
                            $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['sold'] = $sold;
                            $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['returned'] = $returned;
                            $records[$articleKey]['colors'][$colorName][$sizeName][$location['location_name']]['balance'] = $balance;
                            $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['received'] += $received;
                            $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['sold'] += $sold;
                            $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['returned'] += $returned;
                            $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['balance'] += $balance;
                            $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['sell_through'] = $this->calculateSellThrough(
                                (float) $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['received'],
                                (float) $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['sold'],
                                (float) $records[$articleKey]['colors'][$colorName][$sizeName]['summary']['returned']
                            );
                            $colorWiseTotal[$articleKey][$colorName]['color_total'][$location['location_name']]['received'] += $received;
                            $colorWiseTotal[$articleKey][$colorName]['color_total'][$location['location_name']]['sold'] += $sold;
                            $colorWiseTotal[$articleKey][$colorName]['color_total'][$location['location_name']]['returned'] += $returned;
                            $colorWiseTotal[$articleKey][$colorName]['color_total'][$location['location_name']]['balance'] += $balance;
                            $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['received'] += $received;
                            $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['sold'] += $sold;
                            $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['returned'] += $returned;
                            $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['balance'] += $balance;
                            $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['sell_through'] = $this->calculateSellThrough(
                                (float) $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['received'],
                                (float) $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['sold'],
                                (float) $colorWiseTotal[$articleKey][$colorName]['color_total']['summary_total']['returned']
                            );
                            $finalTotal[$location['location_name']]['received'] += $received;
                            $finalTotal[$location['location_name']]['sold'] += $sold;
                            $finalTotal[$location['location_name']]['returned'] += $returned;
                            $finalTotal[$location['location_name']]['balance'] += $balance;
                            $finalTotal['summary_grand_total']['received'] += $received;
                            $finalTotal['summary_grand_total']['sold'] += $sold;
                            $finalTotal['summary_grand_total']['returned'] += $returned;
                            $finalTotal['summary_grand_total']['balance'] += $balance;

                            $records[$articleKey]['colors'][$colorName]['color_wise_total'] = $colorWiseTotal[$articleKey][$colorName]['color_total'];
                        }
                    }
                }
            }
        }

        if (! isset($finalTotal['summary_grand_total'])) {
            $finalTotal['summary_grand_total'] = [
                'received' => 0,
                'sold' => 0,
                'returned' => 0,
                'balance' => 0,
                'sell_through' => '0',
            ];
        }

        foreach ($locationColumns as $location) {
            if (isset($finalTotal[$location])) {
                $finalTotal['summary_grand_total']['received'] += $finalTotal[$location]['received'];
                $finalTotal['summary_grand_total']['sold'] += $finalTotal[$location]['sold'];
                $finalTotal['summary_grand_total']['returned'] += $finalTotal[$location]['returned'];
                $finalTotal['summary_grand_total']['balance'] += $finalTotal[$location]['balance'];
            }
        }

        $finalTotal['summary_grand_total']['sell_through'] = $this->calculateSellThrough(
            (float) $finalTotal['summary_grand_total']['received'],
            (float) $finalTotal['summary_grand_total']['sold'],
            (float) $finalTotal['summary_grand_total']['returned']
        );

        $records['final_total'] = $finalTotal;

        $columns = [
            'received' => 'Received',
            'sold' => 'Sold',
            'returned' => 'Returned',
            'balance' => 'Balance',
        ];

        if (config('app.product_variant')) {
            return [$records, $columns, $locationColumns, $variantColumns];
        }

        foreach (array_keys($records) as $key) {
            if ('final_total' === $key) {
                continue;
            }

            foreach ($colorColumns as $colorColumn) {
                if (! array_key_exists($colorColumn, $records[$key]['colors'])) {
                    $records[$key]['colors'][$colorColumn] = [];
                }

                foreach ($sizeColumns as $sizeColumn) {
                    if (! array_key_exists($sizeColumn, $records[$key]['colors'][$colorColumn])) {
                        $records[$key]['colors'][$colorColumn][$sizeColumn] = [];
                    }

                    foreach ($locationColumns as $locationColumn) {
                        if (! array_key_exists($locationColumn, $records[$key]['colors'][$colorColumn][$sizeColumn])) {
                            $records[$key]['colors'][$colorColumn][$sizeColumn][$locationColumn] = [
                                'received' => 0,
                                'sold' => 0,
                                'returned' => 0,
                                'balance' => 0,
                            ];
                        }

                        if (! array_key_exists('color_wise_total', $records[$key]['colors'][$colorColumn])) {
                            $records[$key]['colors'][$colorColumn]['color_wise_total'] = [];
                        }

                        if (! array_key_exists(
                            $locationColumn,
                            $records[$key]['colors'][$colorColumn]['color_wise_total']
                        )) {
                            $records[$key]['colors'][$colorColumn]['color_wise_total'][$locationColumn] = [
                                'received' => 0,
                                'sold' => 0,
                                'returned' => 0,
                                'balance' => 0,
                            ];
                        }

                        if (! array_key_exists(
                            'summary_total',
                            $records[$key]['colors'][$colorColumn]['color_wise_total']
                        )) {
                            $records[$key]['colors'][$colorColumn]['color_wise_total']['summary_total'] = [
                                'received' => 0,
                                'sold' => 0,
                                'returned' => 0,
                                'balance' => 0,
                                'sell_through' => 0,
                            ];
                        }

                        if (! array_key_exists('summary', $records[$key]['colors'][$colorColumn][$sizeColumn])) {
                            $records[$key]['colors'][$colorColumn][$sizeColumn]['summary'] = [
                                'received' => 0,
                                'sold' => 0,
                                'returned' => 0,
                                'balance' => 0,
                                'sell_through' => 0,
                            ];
                        }
                    }
                }
            }
        }

        return [$records, $columns, $colorColumns, $sizeColumns, $locationColumns];
    }

    private function calculateSellThrough(float $received, float $sold, float $returned): string
    {
        if (0.0 === $received) {
            return (string) 0;
        }

        $sellThrough = (($sold - $returned) * 100 / $received);

        return CommonFunctions::truncateDecimal($sellThrough);
    }
}
