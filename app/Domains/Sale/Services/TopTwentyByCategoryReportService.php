<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Exports\TopTwentyByCategoryExport;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyByCategoryReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchTopTwentyCategoryRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.top_twenty_by_categories', [
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
        [$locationsSales, $company] = $this->fetchTopTwentyCategoryRecords($companyId, $filterData);
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new TopTwentyByCategoryExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchTopTwentyCategoryRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $categoryQueries = resolve(CategoryQueries::class);
        $categories = $categoryQueries->getByCompanyId($companyId);

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);
        $topTwentyAggregateData = $topTwentyAggregateDataQueries->getByStoreForTopColorExport($filterData);

        $locationsSales = [];
        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'categories' => [],
                ];

                $filteredSaleItemsAndGroupByArticleNumber = $topTwentyAggregateData->groupBy(
                    config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number'
                );

                foreach ($categories as $category) {
                    $saleCategories = [
                        'name' => $category->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItemsAndGroupByArticleNumber as $key => $filteredSaleItems) {
                        foreach ($filteredSaleItems as $filteredSaleItem) {
                            $product = $filteredSaleItem->product;

                            $productCategories = config('app.product_variant')
                                ? $product->masterProduct?->categories
                                : $product->categories;

                            if ($productCategories?->contains('id', $category->id)) {
                                $productId = $key;

                                if (! isset($saleCategories['products'][$productId])) {
                                    $saleCategories['products'][$productId] = [
                                        'product_no' => config(
                                            'app.product_variant'
                                        ) ? $product->masterProduct->article_number : $product->article_number,
                                        'name' => $product->name,
                                        'qty' => 0,
                                        'gross_sales_excl_gst' => 0,
                                        'discount_amount' => 0,
                                        'net_sales_excl_gst' => 0,
                                        'gst_amount' => 0,
                                        'net_sales_incl_gst' => 0,
                                    ];
                                }

                                $saleCategories['products'][$productId]['qty'] += $filteredSaleItem->quantity;
                                $saleCategories['products'][$productId]['gross_sales_excl_gst'] += $filteredSaleItem->gross_sales;
                                $saleCategories['products'][$productId]['discount_amount'] += $filteredSaleItem->discount;
                                $saleCategories['products'][$productId]['net_sales_excl_gst'] += $filteredSaleItem->net_sales;
                                $saleCategories['products'][$productId]['gst_amount'] += $filteredSaleItem->tax;
                                $saleCategories['products'][$productId]['net_sales_incl_gst'] += $filteredSaleItem->total_amount;
                            }
                        }
                    }

                    $saleCategoryProducts = collect($saleCategories['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleCategoryProducts->isNotEmpty()) {
                        $saleCategories['products'] = $saleCategoryProducts->toArray();
                        $saleCategories['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleCategoryProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleCategoryProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleCategoryProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleCategoryProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleCategoryProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleCategoryProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['categories'][] = $saleCategories;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'categories' => [],
                ];

                $filteredSaleItemsAndGroupByArticleNumber = $topTwentyAggregateData->where(
                    'counterUpdate.counter.location_id',
                    $location->id
                )->groupBy(
                    config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number'
                );

                foreach ($categories as $category) {
                    $saleCategories = [
                        'name' => $category->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItemsAndGroupByArticleNumber as $key => $filteredSaleItems) {
                        foreach ($filteredSaleItems as $filteredSaleItem) {
                            $product = $filteredSaleItem->product;

                            $productCategories = config('app.product_variant')
                                ? $product->masterProduct?->categories
                                : $product->categories;

                            if ($productCategories?->contains('id', $category->id)) {
                                $productId = $key;

                                if (! isset($saleCategories['products'][$productId])) {
                                    $saleCategories['products'][$productId] = [
                                        'product_no' => config(
                                            'app.product_variant'
                                        ) ? $product->masterProduct->article_number : $product->article_number,
                                        'name' => $product->name,
                                        'qty' => 0,
                                        'gross_sales_excl_gst' => 0,
                                        'discount_amount' => 0,
                                        'net_sales_excl_gst' => 0,
                                        'gst_amount' => 0,
                                        'net_sales_incl_gst' => 0,
                                    ];
                                }

                                $saleCategories['products'][$productId]['qty'] += $filteredSaleItem->quantity;
                                $saleCategories['products'][$productId]['gross_sales_excl_gst'] += $filteredSaleItem->gross_sales;
                                $saleCategories['products'][$productId]['discount_amount'] += $filteredSaleItem->discount;
                                $saleCategories['products'][$productId]['net_sales_excl_gst'] += $filteredSaleItem->net_sales;
                                $saleCategories['products'][$productId]['gst_amount'] += $filteredSaleItem->tax;
                                $saleCategories['products'][$productId]['net_sales_incl_gst'] += $filteredSaleItem->total_amount;
                            }
                        }
                    }

                    $saleCategoryProducts = collect($saleCategories['products'])->sortByDesc('qty')->take(20)->values();
                    if ($saleCategoryProducts->isNotEmpty()) {
                        $saleCategories['products'] = $saleCategoryProducts->toArray();
                        $saleCategories['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleCategoryProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleCategoryProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleCategoryProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleCategoryProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleCategoryProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleCategoryProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['categories'][] = $saleCategories;
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
                'categories' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData;

            foreach ($categories as $category) {
                $saleCategories = [
                    'name' => $category->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems as $filteredSaleItem) {
                    $product = $filteredSaleItem->product;

                    $productCategories = config('app.product_variant')
                        ? $product->masterProduct?->categories
                        : $product->categories;

                    if ($productCategories?->contains('id', $category->id)) {
                        $productId = $product->id;

                        if (! isset($saleCategories['products'][$productId])) {
                            $saleCategories['products'][$productId] = [
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

                        $saleCategories['products'][$productId]['qty'] += $filteredSaleItem->quantity;
                        $saleCategories['products'][$productId]['gross_sales_excl_gst'] += $filteredSaleItem->gross_sales;
                        $saleCategories['products'][$productId]['discount_amount'] += $filteredSaleItem->discount;
                        $saleCategories['products'][$productId]['net_sales_excl_gst'] += $filteredSaleItem->net_sales;
                        $saleCategories['products'][$productId]['gst_amount'] += $filteredSaleItem->tax;
                        $saleCategories['products'][$productId]['net_sales_incl_gst'] += $filteredSaleItem->total_amount;
                    }
                }

                $saleCategoryProducts = collect($saleCategories['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleCategoryProducts->isNotEmpty()) {
                    $saleCategories['products'] = $saleCategoryProducts->toArray();
                    $saleCategories['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleCategoryProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleCategoryProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleCategoryProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleCategoryProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleCategoryProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleCategoryProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['categories'][] = $saleCategories;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'categories' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

            foreach ($categories as $category) {
                $saleCategories = [
                    'name' => $category->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems as $filteredSaleItem) {
                    $product = $filteredSaleItem->product;

                    $productCategories = config('app.product_variant')
                        ? $product->masterProduct?->categories
                        : $product->categories;

                    if ($productCategories?->contains('id', $category->id)) {
                        $productId = $product->id;

                        if (! isset($saleCategories['products'][$productId])) {
                            $saleCategories['products'][$productId] = [
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

                        $saleCategories['products'][$productId]['qty'] += $filteredSaleItem->quantity;
                        $saleCategories['products'][$productId]['gross_sales_excl_gst'] += $filteredSaleItem->gross_sales;
                        $saleCategories['products'][$productId]['discount_amount'] += $filteredSaleItem->discount;
                        $saleCategories['products'][$productId]['net_sales_excl_gst'] += $filteredSaleItem->net_sales;
                        $saleCategories['products'][$productId]['gst_amount'] += $filteredSaleItem->tax;
                        $saleCategories['products'][$productId]['net_sales_incl_gst'] += $filteredSaleItem->total_amount;
                    }
                }

                $saleCategoryProducts = collect($saleCategories['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleCategoryProducts->isNotEmpty()) {
                    $saleCategories['products'] = $saleCategoryProducts->toArray();
                    $saleCategories['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleCategoryProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleCategoryProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleCategoryProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleCategoryProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleCategoryProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleCategoryProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['categories'][] = $saleCategories;
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
