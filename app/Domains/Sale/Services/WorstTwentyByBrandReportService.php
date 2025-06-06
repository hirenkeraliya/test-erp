<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\WorstTwentyFilterTypes;
use App\Domains\Sale\Enums\WorstTwentyReportViewTypes;
use App\Domains\Sale\Exports\WorstTwentyByBrandExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\Brand;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyByBrandReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyBrandRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.worst_twenty_by_brands', [
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
        [$locationsSales, $company] = $this->fetchWorstTwentyBrandRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new WorstTwentyByBrandExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchWorstTwentyBrandRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getByCompanyId($companyId);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getByStoreForTopBrandExport($filterData);

        $locationsSales = [];
        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'brands' => [],
                ];

                $filteredSaleItems = $saleItems;

                foreach ($brands as $brandData) {
                    /** @var Brand $brand */
                    $brand = $brandData;

                    $saleBrands = [
                        'name' => $brand->name,
                        'products' => [],
                    ];

                    $groupBy = config(
                        'app.product_variant'
                    ) ? 'product.masterProduct.article_number' : 'product.article_number';

                    foreach ($filteredSaleItems->where(
                        config('app.product_variant') ? 'product.masterProduct.brand_id' : 'product.brand_id',
                        $brand->id
                    )->groupBy($groupBy) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        $brandId = config(
                            'app.product_variant'
                        ) ? $product->masterProduct->brand_id : $product->brand_id;

                        if ($brandId === $brand->id) {
                            $productId = $product->id;

                            if (! isset($saleBrands['products'][$productId])) {
                                $saleBrands['products'][$productId] = [
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

                            $saleBrands['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleBrands['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount'));
                            $saleBrands['products'][$productId]['discount_amount'] += $saleItem->sum(
                                'total_discount_amount'
                            );
                            $saleBrands['products'][$productId]['net_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount') - $saleItem->sum('total_discount_amount'));
                            $saleBrands['products'][$productId]['gst_amount'] += $saleItem->sum('total_tax_amount');
                            $saleBrands['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_price_paid'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleBrands['products'])->sortBy('qty')->take(20)->values();

                    if ($saleStyleProducts->isNotEmpty()) {
                        $saleBrands['products'] = $saleStyleProducts->toArray();
                        $saleBrands['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleStyleProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['brands'][] = $saleBrands;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'brands' => [],
                ];

                $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);

                foreach ($brands as $brandData) {
                    /** @var Brand $brand */
                    $brand = $brandData;

                    $saleBrands = [
                        'name' => $brand->name,
                        'products' => [],
                    ];

                    $groupBy = config(
                        'app.product_variant'
                    ) ? 'product.masterProduct.article_number' : 'product.article_number';

                    foreach ($filteredSaleItems->where(
                        config('app.product_variant') ? 'product.masterProduct.brand_id' : 'product.brand_id',
                        $brand->id
                    )->groupBy($groupBy) as $key => $saleItem) {
                        $product = $saleItem->first()->product;

                        $brandId = config(
                            'app.product_variant'
                        ) ? $product->masterProduct->brand_id : $product->brand_id;

                        if ($brandId === $brand->id) {
                            $productId = $product->id;

                            if (! isset($saleBrands['products'][$productId])) {
                                $saleBrands['products'][$productId] = [
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

                            $saleBrands['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleBrands['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount'));
                            $saleBrands['products'][$productId]['discount_amount'] += $saleItem->sum(
                                'total_discount_amount'
                            );
                            $saleBrands['products'][$productId]['net_sales_excl_gst'] += ($saleItem->sum(
                                'total_price_paid'
                            ) - $saleItem->sum('total_tax_amount') - $saleItem->sum('total_discount_amount'));
                            $saleBrands['products'][$productId]['gst_amount'] += $saleItem->sum('total_tax_amount');
                            $saleBrands['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_price_paid'
                            );
                        }
                    }

                    $saleStyleProducts = collect($saleBrands['products'])->sortBy('qty')->take(20)->values();

                    if ($saleStyleProducts->isNotEmpty()) {
                        $saleBrands['products'] = $saleStyleProducts->toArray();
                        $saleBrands['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleStyleProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['brands'][] = $saleBrands;
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
                'brands' => [],
            ];

            $filteredSaleItems = $saleItems;

            foreach ($brands as $brandData) {
                /** @var Brand $brand */
                $brand = $brandData;

                $saleBrands = [
                    'name' => $brand->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where(
                    config('app.product_variant') ? 'product.masterProduct.brand_id' : 'product.brand_id',
                    $brand->id
                ) as $saleItem) {
                    $product = $saleItem->product;

                    $brandId = config('app.product_variant') ? $product->masterProduct->brand_id : $product->brand_id;

                    if ($brandId === $brand->id) {
                        $productId = $product->id;

                        if (! isset($saleBrands['products'][$productId])) {
                            $saleBrands['products'][$productId] = [
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

                        $saleBrands['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleBrands['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleBrands['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleBrands['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleBrands['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleBrands['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleStyleProducts = collect($saleBrands['products'])->sortBy('qty')->take(20)->values();

                if ($saleStyleProducts->isNotEmpty()) {
                    $saleBrands['products'] = $saleStyleProducts->toArray();
                    $saleBrands['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleStyleProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['brands'][] = $saleBrands;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'brands' => [],
            ];

            $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);

            foreach ($brands as $brandData) {
                /** @var Brand $brand */
                $brand = $brandData;

                $saleBrands = [
                    'name' => $brand->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where(
                    config('app.product_variant') ? 'product.masterProduct.brand_id' : 'product.brand_id',
                    $brand->id
                ) as $saleItem) {
                    $product = $saleItem->product;

                    $brandId = config('app.product_variant') ? $product->masterProduct->brand_id : $product->brand_id;

                    if ($brandId === $brand->id) {
                        $productId = $product->id;

                        if (! isset($saleBrands['products'][$productId])) {
                            $saleBrands['products'][$productId] = [
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

                        $saleBrands['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleBrands['products'][$productId]['gross_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount);
                        $saleBrands['products'][$productId]['discount_amount'] += $saleItem->total_discount_amount;
                        $saleBrands['products'][$productId]['net_sales_excl_gst'] += ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount);
                        $saleBrands['products'][$productId]['gst_amount'] += $saleItem->total_tax_amount;
                        $saleBrands['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_price_paid;
                    }
                }

                $saleStyleProducts = collect($saleBrands['products'])->sortBy('qty')->take(20)->values();

                if ($saleStyleProducts->isNotEmpty()) {
                    $saleBrands['products'] = $saleStyleProducts->toArray();
                    $saleBrands['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleStyleProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleStyleProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleStyleProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleStyleProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleStyleProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleStyleProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['brands'][] = $saleBrands;
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
