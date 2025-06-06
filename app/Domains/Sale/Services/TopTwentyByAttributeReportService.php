<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Exports\TopTwentyByAttributeExport;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Attribute;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyByAttributeReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchTopTwentyAttributeRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.top_twenty_by_attributes', [
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
        [$locationsSales, $company] = $this->fetchTopTwentyAttributeRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new TopTwentyByAttributeExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchTopTwentyAttributeRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $attributeQueries = resolve(AttributeQueries::class);
        $attribute = $attributeQueries->getById($filterData['attribute_type'], $companyId);

        $attributes = collect($attribute->options)->map(fn ($option) => (object) [
            'id' => $option,
            'name' => $option,
        ]);

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);
        $topTwentyAggregateData = $topTwentyAggregateDataQueries->getByStoreForTopAttributeExport($filterData);

        $locationsSales = [];

        if (null !== $filterData['check_article_number']) {
            if ($filterData['combine_stock_by_selected_location']) {
                $locationSales = [
                    'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck(
                        'code'
                    )->implode(', ') . ']',
                    'attributes' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData;

                foreach ($attributes as $attributeData) {
                    /** @var Attribute $attribute */
                    $attribute = $attributeData;

                    $saleAttributes = [
                        'name' => $attribute->name,
                        'products' => [],
                    ];

                    foreach (
                        $filteredSaleItems->where('product.productVariantValue.value', $attribute->id)->groupBy(
                            config(
                                'app.product_variant'
                            ) ? 'product.masterProduct.article_number' : 'product.article_number'
                        ) as $key => $saleItem
                    ) {
                        $product = $saleItem->first()->product;

                        if ($product->productVariantValue->value === $attribute->id) {
                            $productId = $product->id;

                            if (! isset($saleAttributes['products'][$productId])) {
                                $saleAttributes['products'][$productId] = [
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

                            $saleAttributes['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleAttributes['products'][$productId]['gross_sales_excl_gst'] += $saleItem->sum(
                                'gross_sales'
                            );
                            $saleAttributes['products'][$productId]['discount_amount'] += $saleItem->sum('discount');
                            $saleAttributes['products'][$productId]['net_sales_excl_gst'] += $saleItem->sum(
                                'net_sales'
                            );
                            $saleAttributes['products'][$productId]['gst_amount'] += $saleItem->sum('tax');
                            $saleAttributes['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleAttributeProducts = collect($saleAttributes['products'])->sortByDesc('qty')->take(
                        20
                    )->values();
                    if ($saleAttributeProducts->isNotEmpty()) {
                        $saleAttributes['products'] = $saleAttributeProducts->toArray();
                        $saleAttributes['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleAttributeProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleAttributeProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleAttributeProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleAttributeProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleAttributeProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleAttributeProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['attributes'][] = $saleAttributes;
                    }
                }

                $locationsSales[] = $locationSales;

                return [$locationsSales, $company];
            }

            foreach ($locations as $location) {
                $locationSales = [
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'attributes' => [],
                ];

                $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

                foreach ($attributes as $attributeData) {
                    /** @var Attribute $attribute */
                    $attribute = $attributeData;

                    $saleAttributes = [
                        'name' => $attribute->name,
                        'products' => [],
                    ];

                    foreach (
                        $filteredSaleItems->where('product.productVariantValue.value', $attribute->id)->groupBy(
                            config(
                                'app.product_variant'
                            ) ? 'product.masterProduct.article_number' : 'product.article_number'
                        ) as $key => $saleItem
                    ) {
                        $product = $saleItem->first()->product;

                        if ($product->productVariantValue->value === $attribute->id) {
                            $productId = $product->id;

                            if (! isset($saleAttributes['products'][$productId])) {
                                $saleAttributes['products'][$productId] = [
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

                            $saleAttributes['products'][$productId]['qty'] += $saleItem->sum('quantity');
                            $saleAttributes['products'][$productId]['gross_sales_excl_gst'] += $saleItem->sum(
                                'gross_sales'
                            );
                            $saleAttributes['products'][$productId]['discount_amount'] += $saleItem->sum('discount');
                            $saleAttributes['products'][$productId]['net_sales_excl_gst'] += $saleItem->sum(
                                'net_sales'
                            );
                            $saleAttributes['products'][$productId]['gst_amount'] += $saleItem->sum('tax');
                            $saleAttributes['products'][$productId]['net_sales_incl_gst'] += $saleItem->sum(
                                'total_amount'
                            );
                        }
                    }

                    $saleAttributeProducts = collect($saleAttributes['products'])->sortByDesc('qty')->take(
                        20
                    )->values();
                    if ($saleAttributeProducts->isNotEmpty()) {
                        $saleAttributes['products'] = $saleAttributeProducts->toArray();
                        $saleAttributes['products']['total'] = [
                            'product_no' => 'Total',
                            'name' => '',
                            'qty' => $saleAttributeProducts->sum('qty'),
                            'gross_sales_excl_gst' => $saleAttributeProducts->sum('gross_sales_excl_gst'),
                            'discount_amount' => $saleAttributeProducts->sum('discount_amount'),
                            'net_sales_excl_gst' => $saleAttributeProducts->sum('net_sales_excl_gst'),
                            'gst_amount' => $saleAttributeProducts->sum('gst_amount'),
                            'net_sales_incl_gst' => $saleAttributeProducts->sum('net_sales_incl_gst'),
                        ];
                        $locationSales['attributes'][] = $saleAttributes;
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
                'attributes' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData;

            foreach ($attributes as $attributeData) {
                /** @var Attribute $attribute */
                $attribute = $attributeData;

                $saleAttributes = [
                    'name' => $attribute->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.productVariantValue.value', $attribute->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->productVariantValue->value === $attribute->id) {
                        $productId = $product->id;

                        if (! isset($saleAttributes['products'][$productId])) {
                            $saleAttributes['products'][$productId] = [
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

                        $saleAttributes['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleAttributes['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleAttributes['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleAttributes['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleAttributes['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleAttributes['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleAttributeProducts = collect($saleAttributes['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleAttributeProducts->isNotEmpty()) {
                    $saleAttributes['products'] = $saleAttributeProducts->toArray();
                    $saleAttributes['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleAttributeProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleAttributeProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleAttributeProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleAttributeProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleAttributeProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleAttributeProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['attributes'][] = $saleAttributes;
                }
            }

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'attributes' => [],
            ];

            $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id);

            foreach ($attributes as $attributeData) {
                /** @var Attribute $attribute */
                $attribute = $attributeData;

                $saleAttributes = [
                    'name' => $attribute->name,
                    'products' => [],
                ];

                foreach ($filteredSaleItems->where('product.productVariantValue.value', $attribute->id) as $saleItem) {
                    $product = $saleItem->product;

                    if ($product->productVariantValue->value === $attribute->id) {
                        $productId = $product->id;

                        if (! isset($saleAttributes['products'][$productId])) {
                            $saleAttributes['products'][$productId] = [
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

                        $saleAttributes['products'][$productId]['qty'] += $saleItem->quantity;
                        $saleAttributes['products'][$productId]['gross_sales_excl_gst'] += $saleItem->gross_sales;
                        $saleAttributes['products'][$productId]['discount_amount'] += $saleItem->discount;
                        $saleAttributes['products'][$productId]['net_sales_excl_gst'] += $saleItem->net_sales;
                        $saleAttributes['products'][$productId]['gst_amount'] += $saleItem->tax;
                        $saleAttributes['products'][$productId]['net_sales_incl_gst'] += $saleItem->total_amount;
                    }
                }

                $saleAttributeProducts = collect($saleAttributes['products'])->sortByDesc('qty')->take(20)->values();
                if ($saleAttributeProducts->isNotEmpty()) {
                    $saleAttributes['products'] = $saleAttributeProducts->toArray();
                    $saleAttributes['products']['total'] = [
                        'product_no' => 'Total',
                        'name' => '',
                        'qty' => $saleAttributeProducts->sum('qty'),
                        'gross_sales_excl_gst' => $saleAttributeProducts->sum('gross_sales_excl_gst'),
                        'discount_amount' => $saleAttributeProducts->sum('discount_amount'),
                        'net_sales_excl_gst' => $saleAttributeProducts->sum('net_sales_excl_gst'),
                        'gst_amount' => $saleAttributeProducts->sum('gst_amount'),
                        'net_sales_incl_gst' => $saleAttributeProducts->sum('net_sales_incl_gst'),
                    ];
                    $locationSales['attributes'][] = $saleAttributes;
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
