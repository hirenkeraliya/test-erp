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
use App\Domains\Sale\Exports\WorstTwentyByStyleExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Style\StyleQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyByStyleReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyStyleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.worst_twenty_by_styles', [
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
        [$locationsSales, $company] = $this->fetchWorstTwentyStyleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new WorstTwentyByStyleExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchWorstTwentyStyleRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $styleQueries = resolve(StyleQueries::class);
        $styles = $styleQueries->getByCompanyId($companyId);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getByStoreForTopStyleExport($filterData);

        $locationsSales = [];
        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'styles' => [],
                ];

                $filteredSaleItems = $saleItems;

                foreach ($styles as $style) {
                    $saleStyles = [
                        'name' => $style->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.style_id', $style->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        if ($product->style_id === $style->id) {
                            $productId = $key;

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

                            $saleStyles['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleStyles['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount'));
                            $saleStyles['products'][$productId]['discount_amount'] += $saleItem->sum(
                                'total_discount_amount'
                            );
                            $saleStyles['products'][$productId]['net_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount') - $saleItem->sum('total_discount_amount'));
                            $saleStyles['products'][$productId]['gst_amount'] += $saleItem->sum('total_tax_amount');
                            $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_price_paid'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleStyles['products'])->sortBy('qty')->take(20)->values();
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

                $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);

                foreach ($styles as $style) {
                    $saleStyles = [
                        'name' => $style->name,
                        'products' => [],
                    ];

                    foreach ($filteredSaleItems->where('product.style_id', $style->id)->groupBy(
                        'product.article_number'
                    ) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        if ($product->style_id === $style->id) {
                            $productId = $key;

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

                            $saleStyles['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleStyles['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount'));
                            $saleStyles['products'][$productId]['discount_amount'] += $saleItem->sum(
                                'total_discount_amount'
                            );
                            $saleStyles['products'][$productId]['net_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount') - $saleItem->sum('total_discount_amount'));
                            $saleStyles['products'][$productId]['gst_amount'] += $saleItem->sum('total_tax_amount');
                            $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_price_paid'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleStyles['products'])->sortBy('qty')->take(20)->values();
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

            $filteredSaleItems = $saleItems;

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
                        $saleStyles['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleStyles['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleStyles['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleStyles['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleStyleProducts = collect($saleStyles['products'])->sortBy('qty')->take(20)->values();
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

            $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);

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
                        $saleStyles['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleStyles['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleStyles['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleStyles['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleStyles['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleStyleProducts = collect($saleStyles['products'])->sortBy('qty')->take(20)->values();
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
