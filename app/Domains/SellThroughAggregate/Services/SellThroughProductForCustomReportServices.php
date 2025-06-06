<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SellThroughAggregate\Exports\SellThroughForCustomReportExport;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughProductForCustomReportServices
{
    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        [$records, $columns, $mainColumns] = $this->getPreparedData($filterData, $companyId);

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

        return Excel::download(
            new SellThroughForCustomReportExport(
                $company,
                $records,
                $columns,
                $mainColumns,
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

        $consolidateProductSalesData = $productQueries->accumulatedSaleThroughSalesAndReturnsDataByProductUpcForCustomReport(
            $filterData,
            $companyId
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        if (config('app.product_variant')) {
            $groupedByArticleAndVariants = $consolidateProductSalesData->groupBy(function ($item): string {
                $article = $item->masterProduct->article_number;
                $variantString = $item->productVariantValues
                    ->sortBy('attribute.name')
                    ->map(fn ($v): string => $v['attribute']['name'] . ':' . $v['value'])
                    ->implode(';');

                return $article . '|' . $variantString;
            });
        } else {
            $groupedByArticleNumberAndColor = $consolidateProductSalesData
                ->groupBy(['article_number', 'color_id']);
        }

        /** @var array $records */
        $records = [];

        $columns = [];
        $mainColumns = ['Image', 'Name', 'Price (' . $currency->getSymbol() . ')'];

        /** @var array $total */
        $total = [];

        if (config('app.product_variant')) {
            foreach ($groupedByArticleAndVariants as $key => $group) {
                $first = $group->first();
                $variantMap = $first->productVariantValues->mapWithKeys(fn ($v) => [
                    $v['attribute']['name'] => $v['value'],
                ]);

                $groupingAttribute = $variantMap->keys()->first();
                $columnAttributes = $variantMap->keys()->reject(fn ($attr): bool => $attr === $groupingAttribute);

                $records[$key] = [
                    'image' => $first->getLocalFilePath('images'),
                    'name' => $first->name,
                    'price' => $first->price,
                    'grouped_by' => $groupingAttribute,
                    'variants' => [],
                ];

                $total = [];

                foreach ($group as $item) {
                    $variantMap = $item->productVariantValues->mapWithKeys(fn ($v) => [
                        $v['attribute']['name'] => $v['value'],
                    ]);
                    $groupValue = $variantMap[$groupingAttribute] ?? 'N/A';
                    $columnKey = $columnAttributes->map(fn ($attr) => $variantMap[$attr] ?? '')->implode(' | ');

                    foreach (['received', 'sold', 'returned', 'balance'] as $metric) {
                        $value = $item->{$metric} ?? 0;

                        $records[$key]['variants'][$groupValue][$metric][$columnKey] = [
                            'label' => $columnKey,
                            'value' => $value,
                        ];

                        $records[$key]['variants'][$groupValue][$metric]['total'] =
                            ($records[$key]['variants'][$groupValue][$metric]['total'] ?? 0) + $value;

                        $total[$metric][$columnKey] = ($total[$metric][$columnKey] ?? 0) + $value;
                        $total[$metric]['total'] = ($total[$metric]['total'] ?? 0) + $value;
                    }

                    if (! in_array($columnKey, $columns)) {
                        $columns[] = $columnKey;
                    }
                }

                foreach ($records[$key]['variants'] as $groupValue => $metrics) {
                    foreach ($columns as $colKey) {
                        $received = (float) ($metrics['received'][$colKey]['value'] ?? ($metrics['received'][$colKey] ?? 0));
                        $sold = (float) ($metrics['sold'][$colKey]['value'] ?? ($metrics['sold'][$colKey] ?? 0));
                        $returned = (float) ($metrics['returned'][$colKey]['value'] ?? ($metrics['returned'][$colKey] ?? 0));

                        $records[$key]['variants'][$groupValue]['accumulated_sell_through'][$colKey] = [
                            'label' => $colKey,
                            'value' => $this->calculateSellThrough($received, $sold, $returned),
                        ];
                    }
                }

                $records[$key]['variants']['grand_total'] = [];
                foreach (['received', 'sold', 'returned', 'balance'] as $metric) {
                    $records[$key]['variants']['grand_total'][$metric] = $total[$metric];
                }

                $records[$key]['variants']['grand_total']['accumulated_sell_through'] = [];
                foreach ($columns as $colKey) {
                    $received = (float) ($total['received'][$colKey] ?? 0);
                    $sold = (float) ($total['sold'][$colKey] ?? 0);
                    $returned = (float) ($total['returned'][$colKey] ?? 0);

                    $records[$key]['variants']['grand_total']['accumulated_sell_through'][$colKey] = [
                        'label' => $colKey,
                        'value' => $this->calculateSellThrough($received, $sold, $returned),
                    ];
                }
            }
        } else {
            foreach ($groupedByArticleNumberAndColor as $articleKey => $groupedByColors) {
                if (! array_key_exists($articleKey, $records)) {
                    $records[$articleKey] = [];
                }

                if (! array_key_exists($articleKey, $total)) {
                    $total[$articleKey] = [];
                }

                if (! array_key_exists('grand_total', $total[$articleKey])) {
                    $total[$articleKey]['grand_total'] = [];
                }

                $firstProduct = $groupedByColors->first()->first();

                $records[$articleKey] = [
                    'image' => $firstProduct->getLocalFilePath('images'),
                    'name' => $firstProduct->name,
                    'price' => $firstProduct->price,
                    'colors' => [],
                ];

                foreach ($groupedByColors as $groupedByColor) {
                    foreach ($groupedByColor as $record) {
                        $record = $record->toArray();
                        $colorName = array_key_exists(
                            'color',
                            $record
                        ) && null !== $record['color'] ? $record['color']['name'] : 'N/A';

                        $sizeName = array_key_exists(
                            'size',
                            $record
                        ) && null !== $record['size'] ? $record['size']['name'] : 'N/A';

                        if (! array_key_exists('colors', $records[$articleKey])) {
                            $records[$articleKey]['colors'] = [];
                        }

                        if (! array_key_exists($colorName, $records[$articleKey]['colors'])) {
                            $records[$articleKey]['colors'][$colorName] = [];
                        }

                        if (! array_key_exists('received', $records[$articleKey]['colors'][$colorName])) {
                            $records[$articleKey]['colors'][$colorName]['received'] = [];
                        }

                        if (! array_key_exists('received', $total[$articleKey]['grand_total'])) {
                            $total[$articleKey]['grand_total']['received'] = [];
                        }

                        if (! array_key_exists('sold', $total[$articleKey]['grand_total'])) {
                            $total[$articleKey]['grand_total']['sold'] = [];
                        }

                        if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName]['received'])) {
                            $records[$articleKey]['colors'][$colorName]['received'][$sizeName] = [];
                        }

                        if (! array_key_exists($sizeName, $total[$articleKey]['grand_total']['received'])) {
                            $total[$articleKey]['grand_total']['received'][$sizeName] = [
                                'size_name' => $sizeName,
                                'received' => 0,
                            ];
                        }

                        if (! array_key_exists('total', $records[$articleKey]['colors'][$colorName]['received'])) {
                            $records[$articleKey]['colors'][$colorName]['received']['total'] = 0;
                        }

                        if (! array_key_exists('total', $total[$articleKey]['grand_total']['received'])) {
                            $total[$articleKey]['grand_total']['received']['total'] = 0;
                        }

                        if (! array_key_exists('sold', $records[$articleKey]['colors'][$colorName])) {
                            $records[$articleKey]['colors'][$colorName]['sold'] = [];
                        }

                        if (! array_key_exists('sold', $total[$articleKey]['grand_total'])) {
                            $total[$articleKey]['grand_total']['sold'] = [];
                        }

                        if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName]['sold'])) {
                            $records[$articleKey]['colors'][$colorName]['sold'][$sizeName] = [];
                        }

                        if (! array_key_exists($sizeName, $total[$articleKey]['grand_total']['sold'])) {
                            $total[$articleKey]['grand_total']['sold'][$sizeName] = [
                                'size_name' => $sizeName,
                                'units_sold' => 0,
                            ];
                        }

                        if (! array_key_exists('total', $total[$articleKey]['grand_total']['sold'])) {
                            $total[$articleKey]['grand_total']['sold']['total'] = 0;
                        }

                        if (! array_key_exists('total', $records[$articleKey]['colors'][$colorName]['sold'])) {
                            $records[$articleKey]['colors'][$colorName]['sold']['total'] = 0;
                        }

                        if (! array_key_exists('returned', $records[$articleKey]['colors'][$colorName])) {
                            $records[$articleKey]['colors'][$colorName]['returned'] = [];
                        }

                        if (! array_key_exists('returned', $total[$articleKey]['grand_total'])) {
                            $total[$articleKey]['grand_total']['returned'] = [];
                        }

                        if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName]['returned'])) {
                            $records[$articleKey]['colors'][$colorName]['returned'][$sizeName] = [];
                        }

                        if (! array_key_exists($sizeName, $total[$articleKey]['grand_total']['returned'])) {
                            $total[$articleKey]['grand_total']['returned'][$sizeName] = [
                                'size_name' => $sizeName,
                                'units_returned' => 0,
                            ];
                        }

                        if (! array_key_exists('total', $total[$articleKey]['grand_total']['returned'])) {
                            $total[$articleKey]['grand_total']['returned']['total'] = 0;
                        }

                        if (! array_key_exists('total', $records[$articleKey]['colors'][$colorName]['returned'])) {
                            $records[$articleKey]['colors'][$colorName]['returned']['total'] = 0;
                        }

                        if (! array_key_exists('balance', $records[$articleKey]['colors'][$colorName])) {
                            $records[$articleKey]['colors'][$colorName]['balance'] = [];
                        }

                        if (! array_key_exists('balance', $total[$articleKey]['grand_total'])) {
                            $total[$articleKey]['grand_total']['balance'] = [];
                        }

                        if (! array_key_exists($sizeName, $records[$articleKey]['colors'][$colorName]['balance'])) {
                            $records[$articleKey]['colors'][$colorName]['balance'][$sizeName] = [];
                        }

                        if (! array_key_exists($sizeName, $total[$articleKey]['grand_total']['balance'])) {
                            $total[$articleKey]['grand_total']['balance'][$sizeName] = [
                                'size_name' => $sizeName,
                                'balance' => 0,
                            ];
                        }

                        if (! array_key_exists('total', $total[$articleKey]['grand_total']['balance'])) {
                            $total[$articleKey]['grand_total']['balance']['total'] = 0;
                        }

                        if (! array_key_exists('total', $records[$articleKey]['colors'][$colorName]['balance'])) {
                            $records[$articleKey]['colors'][$colorName]['balance']['total'] = 0;
                        }

                        if (! array_key_exists(
                            'accumulated_sell_through',
                            $records[$articleKey]['colors'][$colorName]
                        )) {
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through'] = [];
                        }

                        if (! array_key_exists(
                            'accumulated_sell_through',
                            $records[$articleKey]['colors'][$colorName]
                        )) {
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through'] = [];
                        }

                        if (! array_key_exists(
                            'total',
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through']
                        )) {
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through']['total'] = 0;
                        }

                        if (! array_key_exists(
                            $sizeName,
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through']
                        )) {
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through'][$sizeName] = [];
                        }

                        if (! in_array($sizeName, $columns)) {
                            $columns[] = $sizeName;
                        }

                        $records[$articleKey]['colors'][$colorName]['received'][$sizeName] = [
                            'size_name' => $sizeName,
                            'received' => $record['received'] ?? 0,
                        ];

                        $records[$articleKey]['colors'][$colorName]['received']['total'] += $record['received'] ?? 0;

                        $records[$articleKey]['colors'][$colorName]['sold'][$sizeName] = [
                            'size_name' => $sizeName,
                            'units_sold' => $record['sold'] ?? 0,
                        ];

                        $records[$articleKey]['colors'][$colorName]['sold']['total'] += $record['sold'] ?? 0;

                        $records[$articleKey]['colors'][$colorName]['returned'][$sizeName] = [
                            'size_name' => $sizeName,
                            'units_returned' => $record['returned'] ?? 0,
                        ];

                        $records[$articleKey]['colors'][$colorName]['returned']['total'] += $record['returned'] ?? 0;

                        $records[$articleKey]['colors'][$colorName]['balance'][$sizeName] = [
                            'size_name' => $sizeName,
                            'balance' => $record['balance'] ?? 0,
                        ];

                        $records[$articleKey]['colors'][$colorName]['balance']['total'] += $record['balance'];

                        $total[$articleKey]['grand_total']['received'][$sizeName]['received'] += $record['received'];
                        $total[$articleKey]['grand_total']['received']['total'] += $record['received'];
                        $total[$articleKey]['grand_total']['sold'][$sizeName]['units_sold'] += $record['sold'];
                        $total[$articleKey]['grand_total']['sold']['total'] += $record['sold'];
                        $total[$articleKey]['grand_total']['returned'][$sizeName]['units_returned'] += $record['returned'];
                        $total[$articleKey]['grand_total']['returned']['total'] += $record['returned'];
                        $total[$articleKey]['grand_total']['balance'][$sizeName]['balance'] += $record['balance'];
                        $total[$articleKey]['grand_total']['balance']['total'] += $record['balance'];

                        $records[$articleKey]['colors'][$colorName]['accumulated_sell_through'] = [
                            'size_name' => $sizeName,
                            'accumulated_sell_through' => $this->calculateSellThrough(
                                (float) $records[$articleKey]['colors'][$colorName]['received']['total'],
                                (float) $records[$articleKey]['colors'][$colorName]['sold']['total'],
                                (float) $records[$articleKey]['colors'][$colorName]['returned']['total']
                            ),
                        ];

                        $total[$articleKey]['grand_total']['accumulated_sell_through'] = [
                            'accumulated_sell_through' => $this->calculateSellThrough(
                                (float) $total[$articleKey]['grand_total']['received']['total'],
                                (float) $total[$articleKey]['grand_total']['sold']['total'],
                                (float) $total[$articleKey]['grand_total']['returned']['total']
                            ),
                        ];
                    }
                }

                $records[$articleKey]['colors']['grand_total'] = $total[$articleKey]['grand_total'];
            }

            foreach ($records as $articleKey => $record) {
                foreach ($record['colors'] as $colorName => $receivedRecord) {
                    foreach ($columns as $sizeColumnKey) {
                        if (! array_key_exists($sizeColumnKey, $receivedRecord['received'])) {
                            $records[$articleKey]['colors'][$colorName]['received'][$sizeColumnKey] = [
                                'size_name' => $sizeColumnKey,
                                'received' => 0,
                            ];
                        }

                        if (! array_key_exists($sizeColumnKey, $receivedRecord['sold'])) {
                            $records[$articleKey]['colors'][$colorName]['sold'][$sizeColumnKey] = [
                                'size_name' => $sizeColumnKey,
                                'units_sold' => 0,
                            ];
                        }

                        if (! array_key_exists($sizeColumnKey, $receivedRecord['returned'])) {
                            $records[$articleKey]['colors'][$colorName]['returned'][$sizeColumnKey] = [
                                'size_name' => $sizeColumnKey,
                                'units_returned' => 0,
                            ];
                        }

                        if (! array_key_exists($sizeColumnKey, $receivedRecord['balance'])) {
                            $records[$articleKey]['colors'][$colorName]['balance'][$sizeColumnKey] = [
                                'size_name' => $sizeColumnKey,
                                'balance' => 0,
                            ];
                        }

                        if ('grand_total' === $colorName) {
                            continue;
                        }

                        if (! array_key_exists(
                            'accumulated_sell_through',
                            $receivedRecord['accumulated_sell_through']
                        )) {
                            $records[$articleKey]['colors'][$colorName]['accumulated_sell_through'] = [
                                'size_name' => $sizeColumnKey,
                                'accumulated_sell_through' => 0,
                            ];
                        }
                    }
                }
            }
        }

        $columns[] = 'total';

        return [$records, $columns, $mainColumns];
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
