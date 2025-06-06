<?php

declare(strict_types=1);

namespace App\Domains\Promoter\services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Promoter\Enums\SalesByPromoterFilterTypes;
use App\Domains\Promoter\Exports\SalesByPromoterWithDetailsReportExport;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\PromoterGroup;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesByPromoterByDetailsReportService
{
    public function preparedByDetails(array $filterData, Company $company, Collection $locations): string
    {
        [$promoterSales, $total, $columns, $dateRange] = $this->preparedRecords($filterData, $company, $locations);

        return view('prints.sales_by_promoter_by_details', [
            'locationSales' => $promoterSales,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'total' => $total,
            'filterBy' => $this->filterBy($filterData),
        ])->render();
    }

    public function exportSalesByPromoterByDetails(
        array $filterData,
        Company $company,
        Collection $locations,
        string $filename
    ): BinaryFileResponse {
        [$promoterSales, $total, $columns] = $this->preparedRecords($filterData, $company, $locations);

        return Excel::download(
            new SalesByPromoterWithDetailsReportExport($promoterSales, $columns, $total),
            $filename
        );
    }

    private function preparedRecords(array $filterData, Company $company, Collection $locations): array
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $salesByPromoters = $promoterQueries->getForSalesByPromotersByDetailsReport($filterData, $company->id);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $salesByPromoters = $salesByPromoters->map(
            fn ($promoter): Collection => $this->preparedRecordsByPromoters($promoter)
        );

        $locationsPromotersSales = [];
        $total = [];

        foreach ($salesByPromoters as $sales) {
            foreach ($sales as $sale) {
                if (! array_key_exists($sale['location_id'], $total)) {
                    $total[$sale['location_id']] = [
                        'units_sold' => 0,
                        'units_returned' => 0,
                        'total_units_returned_amount' => 0,
                        'gross_amount' => 0,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'net_amount' => 0,
                    ];
                }

                if (! array_key_exists($sale['location_id'], $locationsPromotersSales)) {
                    $location = $locations->firstWhere('id', $sale['location_id']);
                    $locationsPromotersSales[$sale['location_id']] = [
                        'location_id' => $location->id,
                        'location_name' => $location->name . ' [' . $location->code . ']',
                        'promoter_sales' => [],
                    ];
                }

                if (! array_key_exists(
                    $sale['promoter_id'],
                    $locationsPromotersSales[$sale['location_id']]['promoter_sales']
                )) {
                    $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']] = [
                        'promoter_name' => $sale['name'],
                        'promoter_group_name' => $sale['promoter_group_name'],
                        'staff_id' => $sale['staff_id'],
                        'sales' => [
                            'totals' => [
                                'promoter_name' => 0,
                                'units_sold' => 0,
                                'units_returned' => 0,
                                'total_units_returned_amount' => 0,
                                'gross_amount' => 0,
                                'discount_amount' => 0,
                                'tax_amount' => 0,
                                'net_amount' => 0,
                            ],
                        ],
                    ];
                }

                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['items'][] = [
                    'product_name' => $sale['product_name'],
                    'brand_name' => $sale['brand'],
                    'category_name' => $sale['category'],
                    'department_name' => $sale['department'],
                    'receipt_id' => $sale['receipt_id'],
                    'units_sold' => $sale['units_sold'],
                    'units_returned' => $sale['units_returned'],
                    'total_units_returned_amount' => $sale['total_units_returned_amount'],
                    'gross_amount' => $sale['gross_amount'],
                    'discount_amount' => $sale['discount_amount'],
                    'tax_amount' => $sale['tax_amount'],
                    'net_amount' => $sale['net_amount'],
                ];

                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['units_sold'] += $sale['units_sold'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['units_returned'] += $sale['units_returned'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['total_units_returned_amount'] += $sale['total_units_returned_amount'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['gross_amount'] += $sale['gross_amount'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['discount_amount'] += (float) $sale['discount_amount'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['tax_amount'] += (float) $sale['tax_amount'];
                $locationsPromotersSales[$sale['location_id']]['promoter_sales'][$sale['promoter_id']]['sales']['totals']['net_amount'] += (float) $sale['net_amount'];

                $total[$sale['location_id']]['units_sold'] += $sale['units_sold'];
                $total[$sale['location_id']]['units_returned'] += $sale['units_returned'];
                $total[$sale['location_id']]['total_units_returned_amount'] += $sale['total_units_returned_amount'];
                $total[$sale['location_id']]['gross_amount'] += $sale['gross_amount'];
                $total[$sale['location_id']]['discount_amount'] += (float) $sale['discount_amount'];
                $total[$sale['location_id']]['tax_amount'] += (float) $sale['tax_amount'];
                $total[$sale['location_id']]['net_amount'] += (float) $sale['net_amount'];
            }
        }

        $columns = ['Units Sold', 'Units Returned', 'Returned', 'Gross', 'Discounts', 'Tax', 'Net'];

        return [$locationsPromotersSales, $total, $columns, $dateRange];
    }

    private function preparedRecordsByPromoters(Promoter $promoter): Collection
    {
        $saleItems = $promoter->saleItems;

        /** @var Employee $employee */
        $employee = $promoter->employee;

        /** @var PromoterGroup $promoterGroup */
        $promoterGroup = $promoter->promoterGroup;

        $preparedSaleItems = collect([]);
        foreach ($saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            $categories = config('app.product_variant') ? $product->masterProduct?->categories : $product->categories;

            $saleItemPromoter = $saleItem->promoters->count();
            if ($saleItem->sale) {
                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $saleItem->sale->counterUpdate;

                /** @var Counter $counter */
                $counter = $counterUpdate->counter;

                /** @var Sale $sale */
                $sale = $saleItem->sale;

                $preparedSaleItems->push([
                    'location_id' => $counter->location_id,
                    'receipt_id' => $sale->offline_sale_id,
                    'product_name' => $product->name,
                    'promoter_id' => $promoter->id,
                    'name' => $employee->getFullName(),
                    'staff_id' => $employee->staff_id,
                    'promoter_group_name' => $promoterGroup->name ?? 'N/A',
                    'brand' => config(
                        'app.product_variant'
                    ) ? $product->masterProduct?->brand?->name : $product->brand?->name,
                    'department' => config(
                        'app.product_variant'
                    ) ? $product->masterProduct?->department?->name : $product->department?->name,
                    'category' => empty($categories) ? 'N/A' : implode(',', $categories->pluck('name')->toArray()),
                    'units_sold' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                    'units_returned' => 0,
                    'total_units_returned_amount' => 0,
                    'gross_amount' => (($saleItem->original_price_per_unit / $saleItemPromoter) * $saleItem->quantity),
                    'discount_amount' => ($saleItem->total_discount_amount / $saleItemPromoter),
                    'tax_amount' => ($saleItem->total_tax_amount / $saleItemPromoter),
                    'net_amount' => ($saleItem->total_price_paid / $saleItemPromoter),
                ]);
            }

            if ($saleItem->saleReturnItems->isNotEmpty()) {
                foreach ($saleItem->saleReturnItems as $saleReturnItem) {
                    /** @var Product $returnProduct */
                    $returnProduct = $saleReturnItem->product;

                    /** @var SaleReturn $saleReturn */
                    $saleReturn = $saleReturnItem->saleReturn;

                    /** @var CounterUpdate $counterUpdate */
                    $counterUpdate = $saleReturn->counterUpdate;

                    /** @var Counter $counter */
                    $counter = $counterUpdate->counter;

                    $preparedSaleItems->push([
                        'location_id' => $counter->location_id,
                        'receipt_id' => $saleReturn->offline_sale_return_id,
                        'product_name' => $returnProduct->name,
                        'promoter_id' => $promoter->id,
                        'name' => $employee->getFullName(),
                        'staff_id' => $employee->staff_id,
                        'promoter_group_name' => $promoterGroup->name ?? 'N/A',
                        'brand' => config(
                            'app.product_variant'
                        ) ? $product->masterProduct?->brand?->name : $product->brand?->name,
                        'department' => config(
                            'app.product_variant'
                        ) ? $product->masterProduct?->department?->name : $product->department?->name,
                        'category' => empty($categories) ? 'N/A' : implode(',', $categories->pluck('name')->toArray()),
                        'units_sold' => 0,
                        'units_returned' => '-' . CommonFunctions::truncateDecimal(
                            (float) $saleReturnItem->quantity
                        ),
                        'total_units_returned_amount' => '-' . CommonFunctions::truncateDecimal(
                            (float) ($saleReturnItem->total_price_paid / $saleItemPromoter)
                        ),
                        'gross_amount' => 0,
                        'discount_amount' => '-' . ($saleReturnItem->total_discount_amount / $saleItemPromoter),
                        'tax_amount' => '-' . ($saleReturnItem->total_tax_amount / $saleItemPromoter),
                        'net_amount' => '-' . ($saleReturnItem->total_price_paid / $saleItemPromoter),
                    ]);
                }
            }
        }

        return $preparedSaleItems;
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === SalesByPromoterFilterTypes::BY_BRANDS->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                SalesByPromoterFilterTypes::BY_BRANDS->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SalesByPromoterFilterTypes::BY_CATEGORIES->value && isset($filterData['category_ids']) && '' !== $filterData['category_ids']) {
            $categories = $categoryQueries->getByIds($filterData['category_ids']);

            return $this->formatFilterResult(
                SalesByPromoterFilterTypes::BY_CATEGORIES->value,
                $categories->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SalesByPromoterFilterTypes::BY_DEPARTMENTS->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                SalesByPromoterFilterTypes::BY_DEPARTMENTS->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SalesByPromoterFilterTypes::BY_PROMOTER_GROUP->value && isset($filterData['group_ids']) && '' !== $filterData['group_ids']) {
            $promoterGroups = $promoterGroupQueries->getByIds($filterData['group_ids']);

            return $this->formatFilterResult(
                SalesByPromoterFilterTypes::BY_PROMOTER_GROUP->value,
                $promoterGroups->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return SalesByPromoterFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
