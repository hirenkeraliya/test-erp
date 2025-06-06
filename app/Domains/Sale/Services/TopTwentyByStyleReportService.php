<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Exports\TopTwentyByStyleExport;
use App\Domains\Style\StyleQueries;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyByStyleReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchTopTwentyStyleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.top_twenty_by_styles', [
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
        [$locationsSales, $company] = $this->fetchTopTwentyStyleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new TopTwentyByStyleExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchTopTwentyStyleRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $styleQueries = resolve(StyleQueries::class);
        $styles = $styleQueries->getByCompanyId($companyId);

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);
        $topTwentyAggregateData = $topTwentyAggregateDataQueries->getByStoreForTopColorExport($filterData);

        $locationsSales = [];
        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'styles' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData;

                foreach ($styles as $style) {
                    $saleStyles = [
                        'name' => $style->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.style_id', $style->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItemArticleNumberWise) {
                        $product = $saleItemArticleNumberWise->first()->product;
                        if ($product->style_id === $style->id) {
                            $productId = $product->id;

                            if (! isset($saleStyles['products'][$productId])) {
                                $saleStyles['products'][$productId] = [
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

                            $saleStyles['products'][$productId]['qty'] += $saleItemArticleNumberWise->sum('quantity');
                            $saleStyles['products'][$productId]['gross_sales_excl_gst'] += $saleItemArticleNumberWise->sum(
                                'gross_sales'
                            );
                            $saleStyles['products'][$productId]['discount_amount'] += $saleItemArticleNumberWise->sum(
                                'discount'
                            );
                            $saleStyles['products'][$productId]['net_sales_excl_gst'] += $saleItemArticleNumberWise->sum(
                                'net_sales'
                            );
                            $saleStyles['products'][$productId]['gst_amount'] += $saleItemArticleNumberWise->sum(
                                'tax'
                            );
                            $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItemArticleNumberWise->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleStyles['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleStyleProducts->isNotEmpty()) {
                        $saleStyles['products'] = $saleStyleProducts->toArray();
                        $saleStyles['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleStyleProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['styles'][] = $saleStyles;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'styles' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

                foreach ($styles as $style) {
                    $saleStyles = [
                        'name' => $style->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.style_id', $style->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItemArticleNumberWise) {
                        $product = $saleItemArticleNumberWise->first()->product;
                        if ($product->style_id === $style->id) {
                            $productId = $product->id;

                            if (! isset($saleStyles['products'][$productId])) {
                                $saleStyles['products'][$productId] = [
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

                            $saleStyles['products'][$productId]['qty'] += $saleItemArticleNumberWise->sum('quantity');
                            $saleStyles['products'][$productId]['gross_sales_excl_gst'] += $saleItemArticleNumberWise->sum(
                                'gross_sales'
                            );
                            $saleStyles['products'][$productId]['discount_amount'] += $saleItemArticleNumberWise->sum(
                                'discount'
                            );
                            $saleStyles['products'][$productId]['net_sales_excl_gst'] += $saleItemArticleNumberWise->sum(
                                'net_sales'
                            );
                            $saleStyles['products'][$productId]['gst_amount'] += $saleItemArticleNumberWise->sum(
                                'tax'
                            );
                            $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItemArticleNumberWise->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleStyles['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleStyleProducts->isNotEmpty()) {
                        $saleStyles['products'] = $saleStyleProducts->toArray();
                        $saleStyles['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleStyleProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['styles'][] = $saleStyles;
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
                'styles' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData;

            foreach ($styles as $style) {
                $saleStyles = [
                    'name' => $style->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.style_id', $style->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->style_id === $style->id) {
                        $productId = $product->id;

                        if (! isset($saleStyles['products'][$productId])) {
                            $saleStyles['products'][$productId] = [
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

                        $saleStyles['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleStyles['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleStyles['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleStyles['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleStyles['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleStyleProducts = collect($saleStyles['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleStyleProducts->isNotEmpty()) {
                    $saleStyles['products'] = $saleStyleProducts->toArray();
                    $saleStyles['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleStyleProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['styles'][] = $saleStyles;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'styles' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

            foreach ($styles as $style) {
                $saleStyles = [
                    'name' => $style->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.style_id', $style->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->style_id === $style->id) {
                        $productId = $product->id;

                        if (! isset($saleStyles['products'][$productId])) {
                            $saleStyles['products'][$productId] = [
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

                        $saleStyles['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleStyles['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleStyles['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleStyles['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleStyles['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleStyleProducts = collect($saleStyles['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleStyleProducts->isNotEmpty()) {
                    $saleStyles['products'] = $saleStyleProducts->toArray();
                    $saleStyles['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleStyleProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['styles'][] = $saleStyles;
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
