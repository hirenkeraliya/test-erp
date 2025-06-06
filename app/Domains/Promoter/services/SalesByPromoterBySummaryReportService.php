<?php

declare(strict_types=1);

namespace App\Domains\Promoter\services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Promoter\Enums\SalesByPromoterFilterTypes;
use App\Domains\Promoter\Exports\SalesByPromoterWithSummaryReportExport;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesByPromoterBySummaryReportService
{
    public function preparedBySummary(array $filterData, Company $company, Collection $locations): string
    {
        [$promoterSales, $total, $columns, $dateRange] = $this->preparedRecords($filterData, $company, $locations);

        return view('prints.sales_by_promoter_by_summary', [
            'locationSales' => $promoterSales,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'total' => $total,
            'filterBy' => $this->filterBy($filterData),
        ])->render();
    }

    public function exportSalesByPromoterBySummary(
        array $filterData,
        Company $company,
        Collection $locations,
        string $filename
    ): BinaryFileResponse {
        [$promoterSales, $total, $columns] = $this->preparedRecords($filterData, $company, $locations);

        return Excel::download(
            new SalesByPromoterWithSummaryReportExport($promoterSales, $columns, $total),
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

        $locationPromotersSales = [];
        $total = [];

        foreach ($salesByPromoters as $sales) {
            if ($sales->isEmpty()) {
                return [$locationPromotersSales, $total, [], $dateRange];
            }

            $locationId = $sales->first()['location_id'];
            $location = $locations->firstWhere('id', $locationId);

            $total[$locationId] = [
                'units_sold' => 0,
                'units_returned' => 0,
                'total_units_returned_amount' => 0,
                'gross_amount' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'net_amount' => 0,
            ];

            if (! array_key_exists($locationId, $locationPromotersSales)) {
                $locationPromotersSales[$locationId] = [
                    'location_id' => $location->id,
                    'location_name' => $location->name . ' [' . $location->code . ']',
                    'promoter_sales' => [],
                ];
            }

            if (array_key_exists($locationId, $locationPromotersSales)) {
                $locationPromotersSales[$locationId]['promoter_sales'][] = [
                    'promoter_name' => $sales->first()['name'],
                    'staff_id' => $sales->first()['staff_id'],
                    'units_sold' => $sales->sum('units_sold'),
                    'units_returned' => $sales->sum('units_returned'),
                    'total_units_returned_amount' => $sales->sum('total_units_returned_amount'),
                    'gross_amount' => $sales->sum('gross_amount'),
                    'discount_amount' => $sales->sum('discount_amount'),
                    'tax_amount' => $sales->sum('tax_amount'),
                    'net_amount' => $sales->sum('net_amount'),
                ];
            }

            if (array_key_exists($locationId, $total)) {
                $total[$locationId]['units_sold'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('units_sold');
                $total[$locationId]['units_returned'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('units_returned');
                $total[$locationId]['total_units_returned_amount'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('total_units_returned_amount');
                $total[$locationId]['gross_amount'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('gross_amount');
                $total[$locationId]['discount_amount'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('discount_amount');
                $total[$locationId]['tax_amount'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('tax_amount');
                $total[$locationId]['net_amount'] += collect(
                    $locationPromotersSales[$locationId]['promoter_sales']
                )->sum('net_amount');
            }
        }

        $columns = [
            'Promoter',
            'Staff Id',
            'Units Sold',
            'Units Returned',
            'Returned',
            'Gross',
            'Discount',
            'Tax',
            'Net',
        ];

        return [$locationPromotersSales, $total, $columns, $dateRange];
    }

    private function preparedRecordsByPromoters(Promoter $promoter): Collection
    {
        $saleItems = $promoter->saleItems;

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $preparedSaleItems = collect([]);
        foreach ($saleItems as $saleItem) {
            $saleItemPromoter = $saleItem->promoters->count();

            if ($saleItem->sale) {
                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $saleItem->sale->counterUpdate;

                /** @var Counter $counter */
                $counter = $counterUpdate->counter;

                $preparedSaleItems->push([
                    'location_id' => $counter->location_id,
                    'promoter_id' => $promoter->id,
                    'name' => $employee->getFullName(),
                    'staff_id' => $employee->staff_id,
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
                    /** @var SaleReturn $saleReturn */
                    $saleReturn = $saleReturnItem->saleReturn;

                    /** @var CounterUpdate $counterUpdate */
                    $counterUpdate = $saleReturn->counterUpdate;

                    /** @var Counter $counter */
                    $counter = $counterUpdate->counter;

                    $preparedSaleItems->push([
                        'location_id' => $counter->location_id,
                        'promoter_id' => $promoter->id,
                        'name' => $employee->getFullName(),
                        'staff_id' => $employee->staff_id,
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
