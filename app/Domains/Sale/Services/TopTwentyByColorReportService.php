<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Exports\TopTwentyByColorExport;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Color;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyByColorReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchTopTwentyColorRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.top_twenty_by_colors', [
            'locationsSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'displayAmount' => (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value,
        ])->render();
    }

    public function export(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        [$locationsSales, $company] = $this->fetchTopTwentyColorRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new TopTwentyByColorExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchTopTwentyColorRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $colorQueries = resolve(ColorQueries::class);
        $colors = $colorQueries->getByCompanyId($companyId);

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);
        $topTwentyAggregateData = $topTwentyAggregateDataQueries->getByStoreForTopColorExport($filterData);

        $locationsSales = [];

        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'colors' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData;

                foreach ($colors as $colorData) {
                    /** @var Color $color */
                    $color = $colorData;

                    $saleColors = [
                        'name' => $color->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.color_id', $color->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        if ($product->color_id === $color->id) {
                            $productId = $product->id;

                            if (! isset($saleColors['products'][$productId])) {
                                $saleColors['products'][$productId] = [
                                    'product_no' => $key,
                                    'name' => $product->name,
                                    'qty' => 0,
                                    'gross_sales_excl_gst' => 0,
                                    'discount_amount' => 0,
                                    'net_sales_excl_gst' => 0,
                                    'gst_amount' => 0,
                                    'net_sales_incl_gst' => 0,
                                ];
                            }

                            $saleColors['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleColors['products'][$productId]['gross_sales_excl_gst'] += $saleItem->sum(
                                'gross_sales'
                            );
                            $saleColors['products'][$productId]['discount_amount'] += $saleItem->sum('discount');
                            $saleColors['products'][$productId]['net_sales_excl_gst'] += $saleItem->sum('net_sales');
                            $saleColors['products'][$productId]['gst_amount'] += $saleItem->sum('tax');
                            $saleColors['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleColorProducts = collect($saleColors['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleColorProducts->isNotEmpty()) {
                        $saleColors['products'] = $saleColorProducts->toArray();
                        $saleColors['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleColorProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleColorProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleColorProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleColorProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleColorProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleColorProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['colors'][] = $saleColors;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'colors' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

                foreach ($colors as $colorData) {
                    /** @var Color $color */
                    $color = $colorData;

                    $saleColors = [
                        'name' => $color->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.color_id', $color->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        if ($product->color_id === $color->id) {
                            $productId = $product->id;

                            if (! isset($saleColors['products'][$productId])) {
                                $saleColors['products'][$productId] = [
                                    'product_no' => $key,
                                    'name' => $product->name,
                                    'qty' => 0,
                                    'gross_sales_excl_gst' => 0,
                                    'discount_amount' => 0,
                                    'net_sales_excl_gst' => 0,
                                    'gst_amount' => 0,
                                    'net_sales_incl_gst' => 0,
                                ];
                            }

                            $saleColors['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleColors['products'][$productId]['gross_sales_excl_gst'] += $saleItem->sum(
                                'gross_sales'
                            );
                            $saleColors['products'][$productId]['discount_amount'] += $saleItem->sum('discount');
                            $saleColors['products'][$productId]['net_sales_excl_gst'] += $saleItem->sum(
                                'net_sales'
                            );
                            $saleColors['products'][$productId]['gst_amount'] += $saleItem->sum('tax');
                            $saleColors['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleColorProducts = collect($saleColors['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleColorProducts->isNotEmpty()) {
                        $saleColors['products'] = $saleColorProducts->toArray();
                        $saleColors['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleColorProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleColorProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleColorProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleColorProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleColorProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleColorProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['colors'][] = $saleColors;
                    }
                }

                $locationsSales[] = $locationSales;
            }

            return [$locationsSales, $company];
        }

        if ($filterData['combine_stock_by_selected_location']) {
            $locationSales = [
                'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck('code')->implode(
                    ', '
                ) . ']',
                'colors' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData;

            foreach ($colors as $colorData) {
                /** @var Color $color */
                $color = $colorData;

                $saleColors = [
                    'name' => $color->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.color_id', $color->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->color_id === $color->id) {
                        $productId = $product->id;

                        if (! isset($saleColors['products'][$productId])) {
                            $saleColors['products'][$productId] = [
                                'product_no' => $product->upc,
                                'name' => $product->name,
                                'qty' => 0,
                                'gross_sales_excl_gst' => 0,
                                'discount_amount' => 0,
                                'net_sales_excl_gst' => 0,
                                'gst_amount' => 0,
                                'net_sales_incl_gst' => 0,
                            ];
                        }

                        $saleColors['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleColors['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleColors['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleColors['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleColors['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleColors['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleColorProducts = collect($saleColors['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleColorProducts->isNotEmpty()) {
                    $saleColors['products'] = $saleColorProducts->toArray();
                    $saleColors['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleColorProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleColorProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleColorProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleColorProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleColorProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleColorProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['colors'][] = $saleColors;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'colors' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

            foreach ($colors as $colorData) {
                /** @var Color $color */
                $color = $colorData;

                $saleColors = [
                    'name' => $color->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.color_id', $color->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->color_id === $color->id) {
                        $productId = $product->id;

                        if (! isset($saleColors['products'][$productId])) {
                            $saleColors['products'][$productId] = [
                                'product_no' => $product->upc,
                                'name' => $product->name,
                                'qty' => 0,
                                'gross_sales_excl_gst' => 0,
                                'discount_amount' => 0,
                                'net_sales_excl_gst' => 0,
                                'gst_amount' => 0,
                                'net_sales_incl_gst' => 0,
                            ];
                        }

                        $saleColors['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleColors['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleColors['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleColors['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleColors['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleColors['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleColorProducts = collect($saleColors['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleColorProducts->isNotEmpty()) {
                    $saleColors['products'] = $saleColorProducts->toArray();
                    $saleColors['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleColorProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleColorProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleColorProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleColorProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleColorProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleColorProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['colors'][] = $saleColors;
                }
            }

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $company];
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === TopTwentyFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                TopTwentyFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === TopTwentyFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                TopTwentyFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return TopTwentyFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
