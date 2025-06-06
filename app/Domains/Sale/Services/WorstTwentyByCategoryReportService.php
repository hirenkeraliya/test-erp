<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Category\Exports\WorstTwentyCategoryExport;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\WorstTwentyFilterTypes;
use App\Domains\Sale\Enums\WorstTwentyReportViewTypes;
use App\Domains\SaleItem\SaleItemQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyByCategoryReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.worst_twenty_by_categories', [
            'locationsSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'displayAmount' => (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value,
        ])->render();
    }

    public function export(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new WorstTwentyCategoryExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchWorstTwentyRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $categoryQueries = resolve(CategoryQueries::class);
        $categories = $categoryQueries->getByCompanyId($companyId);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getByStoreForTopCategoryExport($filterData);

        $locationsSales = [];

        if (null !== $filterData['check_article_number'] && $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'categories' => [],
                ];

                foreach ($categories as $category) {
                    $saleCategories['name'] = $category->name;
                    $saleCategories['products'] = [];

                    $groupBy = config(
                        'app.product_variant'
                    ) ? 'product.masterProduct.article_number' : 'product.article_number';

                    foreach ($saleItems->groupBy($groupBy) as $key => $saleItemWithArticleNumber) {
                        foreach ($saleItemWithArticleNumber as $saleItem) {
                            $categoryData = config(
                                'app.product_variant'
                            ) ? $saleItem->product->masterProduct?->categories->where(
                                'id',
                                $category->id
                            )->isNotEmpty() : $saleItem->product->categories->where('id', $category->id)->isNotEmpty();

                            if ($categoryData) {
                                $articleNumber = config(
                                    'app.product_variant'
                                ) ? $saleItem->product->masterProduct?->article_number : $saleItem->product->article_number;

                                $saleCategories['products'][$key]['product_no'] = $articleNumber;
                                $saleCategories['products'][$key]['name'] = $saleItem->product->name;

                                if (! array_key_exists('qty', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['qty'] = 0;
                                }

                                $saleCategories['products'][$key]['qty'] += $saleItem->quantity;

                                if (! array_key_exists(
                                    'gross_sales_excl_gst',
                                    $saleCategories['products'][$key]
                                )) {
                                    $saleCategories['products'][$key]['gross_sales_excl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                                if (! array_key_exists('discount_amount', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['discount_amount'] = 0;
                                }

                                $saleCategories['products'][$key]['discount_amount'] += $saleItem->total_discount_amount;

                                if (! array_key_exists('net_sales_excl_gst', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['net_sales_excl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);

                                if (! array_key_exists('gst_amount', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['gst_amount'] = 0;
                                }

                                $saleCategories['products'][$key]['gst_amount'] += $saleItem->total_tax_amount;

                                if (! array_key_exists('net_sales_incl_gst', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['net_sales_incl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                            }
                        }
                    }

                    $saleCategoryProducts = collect($saleCategories['products'])->sortBy('qty')->take(20)->values();

                    if ($saleCategoryProducts->isNotEmpty()) {
                        $saleCategories['products'] = $saleCategoryProducts->toArray();
                        $saleCategories['products']['total'] = [];
                        $saleCategories['products']['total']['product_no'] = 'Total';
                        $saleCategories['products']['total']['name'] = '';
                        $saleCategories['products']['total']['qty'] = $saleCategoryProducts->sum('qty');
                        $saleCategories['products']['total']['gross_sales_excl_gst'] = $saleCategoryProducts->sum(
                            'gross_sales_excl_gst'
                        );
                        $saleCategories['products']['total']['discount_amount'] = $saleCategoryProducts->sum(
                            'discount_amount'
                        );
                        $saleCategories['products']['total']['net_sales_excl_gst'] = $saleCategoryProducts->sum(
                            'net_sales_excl_gst'
                        );
                        $saleCategories['products']['total']['gst_amount'] = $saleCategoryProducts->sum('gst_amount');
                        $saleCategories['products']['total']['net_sales_incl_gst'] = $saleCategoryProducts->sum(
                            'net_sales_incl_gst'
                        );
                        $locationSales['categories'][] = $saleCategories;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company, $dateRange];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'categories' => [],
                ];

                foreach ($categories as $category) {
                    $saleCategories['name'] = $category->name;
                    $saleCategories['products'] = [];

                    $groupBy = config(
                        'app.product_variant'
                    ) ? 'product.masterProduct.article_number' : 'product.article_number';

                    foreach ($saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->groupBy(
                        $groupBy
                    ) as $key => $saleItemWithArticleNumber) {
                        foreach ($saleItemWithArticleNumber as $saleItem) {
                            $categoryData = config(
                                'app.product_variant'
                            ) ? $saleItem->product->masterProduct?->categories->where(
                                'id',
                                $category->id
                            )->isNotEmpty() : $saleItem->product->categories->where('id', $category->id)->isNotEmpty();

                            $articleNumber = config(
                                'app.product_variant'
                            ) ? $saleItem->product->masterProduct?->article_number : $saleItem->product->article_number;

                            if ($categoryData) {
                                $saleCategories['products'][$key]['product_no'] = $articleNumber;
                                $saleCategories['products'][$key]['name'] = $saleItem->product->name;

                                if (! array_key_exists('qty', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['qty'] = 0;
                                }

                                $saleCategories['products'][$key]['qty'] += $saleItem->quantity;

                                if (! array_key_exists(
                                    'gross_sales_excl_gst',
                                    $saleCategories['products'][$key]
                                )) {
                                    $saleCategories['products'][$key]['gross_sales_excl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                                if (! array_key_exists('discount_amount', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['discount_amount'] = 0;
                                }

                                $saleCategories['products'][$key]['discount_amount'] += $saleItem->total_discount_amount;

                                if (! array_key_exists('net_sales_excl_gst', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['net_sales_excl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);

                                if (! array_key_exists('gst_amount', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['gst_amount'] = 0;
                                }

                                $saleCategories['products'][$key]['gst_amount'] += $saleItem->total_tax_amount;

                                if (! array_key_exists('net_sales_incl_gst', $saleCategories['products'][$key])) {
                                    $saleCategories['products'][$key]['net_sales_incl_gst'] = 0;
                                }

                                $saleCategories['products'][$key]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                            }
                        }
                    }

                    $saleCategoryProducts = collect($saleCategories['products'])->sortBy('qty')->take(20)->values();

                    if ($saleCategoryProducts->isNotEmpty()) {
                        $saleCategories['products'] = $saleCategoryProducts->toArray();
                        $saleCategories['products']['total'] = [];
                        $saleCategories['products']['total']['product_no'] = 'Total';
                        $saleCategories['products']['total']['name'] = '';
                        $saleCategories['products']['total']['qty'] = $saleCategoryProducts->sum('qty');
                        $saleCategories['products']['total']['gross_sales_excl_gst'] = $saleCategoryProducts->sum(
                            'gross_sales_excl_gst'
                        );
                        $saleCategories['products']['total']['discount_amount'] = $saleCategoryProducts->sum(
                            'discount_amount'
                        );
                        $saleCategories['products']['total']['net_sales_excl_gst'] = $saleCategoryProducts->sum(
                            'net_sales_excl_gst'
                        );
                        $saleCategories['products']['total']['gst_amount'] = $saleCategoryProducts->sum('gst_amount');
                        $saleCategories['products']['total']['net_sales_incl_gst'] = $saleCategoryProducts->sum(
                            'net_sales_incl_gst'
                        );
                        $locationSales['categories'][] = $saleCategories;
                    }
                }

                $locationsSales[] = $locationSales;
            }

            return [$locationsSales, $company, $dateRange];
        }

        if (array_key_exists(
            'combine_stock_by_selected_location',
            $filterData
        ) && $filterData['combine_stock_by_selected_location']) {
            $locationSales = [
                'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck('code')->implode(
                    ', '
                ) . ']',
                'categories' => [],
            ];

            foreach ($categories as $category) {
                $saleCategories['name'] = $category->name;
                $saleCategories['products'] = [];

                foreach ($saleItems as $saleItem) {
                    $categoryData = config(
                        'app.product_variant'
                    ) ? $saleItem->product->masterProduct?->categories->where(
                        'id',
                        $category->id
                    )->isNotEmpty() : $saleItem->product->categories->where('id', $category->id)->isNotEmpty();

                    if ($categoryData) {
                        $saleCategories['products'][$saleItem->product->id]['product_no'] = $saleItem->product->upc;
                        $saleCategories['products'][$saleItem->product->id]['name'] = $saleItem->product->name;

                        if (! array_key_exists('qty', $saleCategories['products'][$saleItem->product->id])) {
                            $saleCategories['products'][$saleItem->product->id]['qty'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['qty'] += $saleItem->quantity;

                        if (! array_key_exists(
                            'gross_sales_excl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['gross_sales_excl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        if (! array_key_exists(
                            'discount_amount',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['discount_amount'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['discount_amount'] += $saleItem->total_discount_amount;

                        if (! array_key_exists(
                            'net_sales_excl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['net_sales_excl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);

                        if (! array_key_exists('gst_amount', $saleCategories['products'][$saleItem->product->id])) {
                            $saleCategories['products'][$saleItem->product->id]['gst_amount'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['gst_amount'] += $saleItem->total_tax_amount;

                        if (! array_key_exists(
                            'net_sales_incl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['net_sales_incl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleCategoryProducts = collect($saleCategories['products'])->sortBy('qty')->take(20)->values();

                if ($saleCategoryProducts->isNotEmpty()) {
                    $saleCategories['products'] = $saleCategoryProducts->toArray();
                    $saleCategories['products']['total'] = [];
                    $saleCategories['products']['total']['product_no'] = 'Total';
                    $saleCategories['products']['total']['name'] = '';
                    $saleCategories['products']['total']['qty'] = $saleCategoryProducts->sum('qty');
                    $saleCategories['products']['total']['gross_sales_excl_gst'] = $saleCategoryProducts->sum(
                        'gross_sales_excl_gst'
                    );
                    $saleCategories['products']['total']['discount_amount'] = $saleCategoryProducts->sum(
                        'discount_amount'
                    );
                    $saleCategories['products']['total']['net_sales_excl_gst'] = $saleCategoryProducts->sum(
                        'net_sales_excl_gst'
                    );
                    $saleCategories['products']['total']['gst_amount'] = $saleCategoryProducts->sum('gst_amount');
                    $saleCategories['products']['total']['net_sales_incl_gst'] = $saleCategoryProducts->sum(
                        'net_sales_incl_gst'
                    );
                    $locationSales['categories'][] = $saleCategories;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company, $dateRange];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'categories' => [],
            ];

            foreach ($categories as $category) {
                $saleCategories['name'] = $category->name;
                $saleCategories['products'] = [];

                foreach ($saleItems->where('sale.counterUpdate.counter.location_id', $location->id) as $saleItem) {
                    $categoryData = config(
                        'app.product_variant'
                    ) ? $saleItem->product->masterProduct?->categories->where(
                        'id',
                        $category->id
                    )->isNotEmpty() : $saleItem->product->categories->where('id', $category->id)->isNotEmpty();

                    if ($categoryData) {
                        $saleCategories['products'][$saleItem->product->id]['product_no'] = $saleItem->product->upc;
                        $saleCategories['products'][$saleItem->product->id]['name'] = $saleItem->product->name;

                        if (! array_key_exists('qty', $saleCategories['products'][$saleItem->product->id])) {
                            $saleCategories['products'][$saleItem->product->id]['qty'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['qty'] += $saleItem->quantity;

                        if (! array_key_exists(
                            'gross_sales_excl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['gross_sales_excl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        if (! array_key_exists(
                            'discount_amount',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['discount_amount'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['discount_amount'] += $saleItem->total_discount_amount;

                        if (! array_key_exists(
                            'net_sales_excl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['net_sales_excl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);

                        if (! array_key_exists('gst_amount', $saleCategories['products'][$saleItem->product->id])) {
                            $saleCategories['products'][$saleItem->product->id]['gst_amount'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['gst_amount'] += $saleItem->total_tax_amount;

                        if (! array_key_exists(
                            'net_sales_incl_gst',
                            $saleCategories['products'][$saleItem->product->id]
                        )) {
                            $saleCategories['products'][$saleItem->product->id]['net_sales_incl_gst'] = 0;
                        }

                        $saleCategories['products'][$saleItem->product->id]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleCategoryProducts = collect($saleCategories['products'])->sortBy('qty')->take(20)->values();

                if ($saleCategoryProducts->isNotEmpty()) {
                    $saleCategories['products'] = $saleCategoryProducts->toArray();
                    $saleCategories['products']['total'] = [];
                    $saleCategories['products']['total']['product_no'] = 'Total';
                    $saleCategories['products']['total']['name'] = '';
                    $saleCategories['products']['total']['qty'] = $saleCategoryProducts->sum('qty');
                    $saleCategories['products']['total']['gross_sales_excl_gst'] = $saleCategoryProducts->sum(
                        'gross_sales_excl_gst'
                    );
                    $saleCategories['products']['total']['discount_amount'] = $saleCategoryProducts->sum(
                        'discount_amount'
                    );
                    $saleCategories['products']['total']['net_sales_excl_gst'] = $saleCategoryProducts->sum(
                        'net_sales_excl_gst'
                    );
                    $saleCategories['products']['total']['gst_amount'] = $saleCategoryProducts->sum('gst_amount');
                    $saleCategories['products']['total']['net_sales_incl_gst'] = $saleCategoryProducts->sum(
                        'net_sales_incl_gst'
                    );
                    $locationSales['categories'][] = $saleCategories;
                }
            }

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $company, $dateRange];
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === WorstTwentyFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                WorstTwentyFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === WorstTwentyFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                WorstTwentyFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return WorstTwentyFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
