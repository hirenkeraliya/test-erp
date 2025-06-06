<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\WorstTwentyFilterTypes;
use App\Domains\Sale\Enums\WorstTwentyReportViewTypes;
use App\Domains\Sale\Exports\WorstTwentyByArticleNumberExport;
use App\Domains\SaleItem\SaleItemQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyByArticleNumberReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyArticleNumberRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.worst_twenty_by_article_numbers', [
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
        [$locationsSales, $company] = $this->fetchWorstTwentyArticleNumberRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new WorstTwentyByArticleNumberExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchWorstTwentyArticleNumberRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getByStoreForTopArticleNumberExport($filterData);

        $pluckArticleNumber = config(
            'app.product_variant'
        ) ? 'product.masterProduct.article_number' : 'product.article_number';

        $articleNumbers = $saleItems->sortBy('quantity')->pluck($pluckArticleNumber)->unique()->take(20);

        $locationsSales = [];

        if ($filterData['combine_stock_by_selected_location']) {
            $locationSales = [
                'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck('code')->implode(
                    ', '
                ) . ']',
                'article_numbers' => [],
            ];

            $filteredSaleItems = $saleItems;

            foreach ($articleNumbers as $articleNumber) {
                $saleByArticleNumber = [
                    'name' => $articleNumber,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where(
                    config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number',
                    $articleNumber
                ) as $saleItem) {
                    $product = $saleItem->product;

                    $productArticleNumber = config(
                        'app.product_variant'
                    ) ? $product->masterProduct->article_number : $product->article_number;

                    if ($productArticleNumber === $articleNumber) {
                        $productId = $product->id;

                        if (! isset($saleByArticleNumber['products'][$productId])) {
                            $saleByArticleNumber['products'][$productId] = [
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

                        $saleByArticleNumber['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleByArticleNumber['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleByArticleNumber['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleByArticleNumber['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleByArticleNumber['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleByArticleNumber['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleByArticleNumberProducts = collect($saleByArticleNumber['products'])->sortBy('qty')->take(
                    20
                )->values();
                if ($saleByArticleNumberProducts->isNotEmpty()) {
                    $saleByArticleNumber['products'] = $saleByArticleNumberProducts->toArray();
                    $saleByArticleNumber['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleByArticleNumberProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleByArticleNumberProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleByArticleNumberProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleByArticleNumberProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleByArticleNumberProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleByArticleNumberProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['article_numbers'][] = $saleByArticleNumber;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'article_numbers' => [],
            ];

            $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);

            foreach ($articleNumbers as $articleNumber) {
                $saleByArticleNumber = [
                    'name' => $articleNumber,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where(
                    config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number',
                    $articleNumber
                ) as $saleItem) {
                    $product = $saleItem->product;

                    $productArticleNumber = config(
                        'app.product_variant'
                    ) ? $product->masterProduct->article_number : $product->article_number;

                    if ($productArticleNumber === $articleNumber) {
                        $productId = $product->id;

                        if (! isset($saleByArticleNumber['products'][$productId])) {
                            $saleByArticleNumber['products'][$productId] = [
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

                        $saleByArticleNumber['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleByArticleNumber['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleByArticleNumber['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleByArticleNumber['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleByArticleNumber['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleByArticleNumber['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleByArticleNumberProducts = collect($saleByArticleNumber['products'])->sortBy('qty')->take(
                    20
                )->values();
                if ($saleByArticleNumberProducts->isNotEmpty()) {
                    $saleByArticleNumber['products'] = $saleByArticleNumberProducts->toArray();
                    $saleByArticleNumber['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleByArticleNumberProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleByArticleNumberProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleByArticleNumberProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleByArticleNumberProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleByArticleNumberProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleByArticleNumberProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['article_numbers'][] = $saleByArticleNumber;
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
