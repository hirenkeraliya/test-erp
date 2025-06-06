<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionUpdate\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\PromoterCommissionUpdate\Exports\PromoterCommissionUpdateExport;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\Sale\Enums\PromoterCommissionFilterTypes;
use App\Domains\Sale\Enums\PromoterCommissionReportTypes;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use App\Models\PromoterGroup;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromoterCommissionUpdateReportService
{
    public function printPromoterCommission(array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $companyId = session('admin_company_id');
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        return $this->renderPreparedPromoterCommission($filterData, $company, $locations);
    }

    public function exportPromoterCommissionData(array $filterData, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $companyId = session('admin_company_id');
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        [$promoterCommissionUpdate, $columns, $dateRange] = $this->fetchPromoterCommissionData($filterData, $locations);

        $filterBy = $this->filterBy($filterData);

        return Excel::download(
            new PromoterCommissionUpdateExport(
                $promoterCommissionUpdate,
                $columns,
                (int) $filterData['report_type'],
                $dateRange,
                $company,
                $filterBy,
            ),
            $filename
        );
    }

    private function renderPreparedPromoterCommission(
        array $filterData,
        Company $company,
        Collection $locations
    ): string {
        [$promoterCommissionUpdate, $columns, $dateRange] = $this->fetchPromoterCommissionData($filterData, $locations);

        return view('prints.promoter_commission', [
            'promoterCommissionSales' => $promoterCommissionUpdate,
            'reportType' => PromoterCommissionReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'filterBy' => $this->filterBy($filterData),
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
        ])->render();
    }

    private function fetchPromoterCommissionData(array $filterData, Collection $locations): array
    {
        $promoterCommissionUpdate = null;
        $columns = null;

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareMonthRange($filterData);

        if ((int) $filterData['report_type'] === PromoterCommissionReportTypes::BY_ITEM->value) {
            [$promoterCommissionUpdate, $columns] = $this->preparedPromoterCommissionUpdateByItem(
                $filterData,
                $locations
            );
        }

        return [$promoterCommissionUpdate, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    private function preparedPromoterCommissionUpdateByItem(array $filterData, Collection $locations): array
    {
        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
        $promoterCommissionUpdatesData = $promoterCommissionUpdateQueries->getPromoterCommissionReportByItem(
            $filterData
        );

        $locationsPromoterCommissionUpdates = [];
        foreach ($locations as $location) {
            $promoterCommissionUpdate = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'promoters' => [],
            ];

            $storePromoterCommissionUpdates = $promoterCommissionUpdatesData->where('location_id', $location->id);

            foreach ($storePromoterCommissionUpdates->groupBy(
                'promoterCommission.promoter.id'
            ) as $promoterCommissionUpdateData) {
                $promoterCommissionUpdate['promoters'][] = $this->getPreparedPromoterCommissionUpdateByItem(
                    $promoterCommissionUpdateData
                );
            }

            $locationsPromoterCommissionUpdates[] = $promoterCommissionUpdate;
        }

        $columns = [
            'Sale Type',
            'Receipt No',
            'Product Upc',
            'Product Name',
            'Brand Name',
            'Department Name',
            'Promoter Group Name',
            'Quantity',
            'Commission Percentage',
            'Commission Amount',
        ];

        return [$locationsPromoterCommissionUpdates, $columns];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPreparedPromoterCommissionUpdateByItem(Collection $promoterCommissionUpdates): array
    {
        $promoterCommissionUpdateData = [];
        foreach ($promoterCommissionUpdates as $promoterCommissionUpdate) {
            /** @var PromoterCommission $promoterCommission */
            $promoterCommission = $promoterCommissionUpdate->promoterCommission;

            /** @var Promoter $promoter */
            $promoter = $promoterCommission->promoter;

            /** @var Employee $employee */
            $employee = $promoter->employee;

            /** @var PromoterGroup $promoterGroup */
            $promoterGroup = $promoter->promoterGroup;

            /** @var Brand $brand */
            $brand = $promoterCommissionUpdate->brand;

            $saleItem = null;
            $sale = null;
            $saleReturnItem = null;

            if ($promoterCommissionUpdate->affected_by instanceof SaleReturnItem) {
                /** @var SaleReturnItem $saleReturnItem */
                $saleReturnItem = $promoterCommissionUpdate->affected_by;

                /** @var SaleItem $saleItem */
                $saleItem = $saleReturnItem->saleItem;

                /** @var Sale $sale */
                $sale = $saleItem->sale;
            }

            if ($promoterCommissionUpdate->affected_by instanceof SaleItem) {
                /** @var SaleItem $saleItem */
                $saleItem = $promoterCommissionUpdate->affected_by;

                /** @var Sale $sale */
                $sale = $saleItem->sale;
            }

            /** @var Product $product */
            $product = $saleItem?->product;

            /** @var Department $department */
            $department = $promoterCommissionUpdate->department;

            $promoterCommissionUpdateData['code'] = $promoter->code;
            $promoterCommissionUpdateData['name'] = $employee->getFullName();

            $promoterCommissionUpdateData['details'][] = [
                'sale_type' => $promoterCommissionUpdate->affected_by_type === ModelMapping::SALE_ITEM->name ? 'SL' : 'SR',
                'receipt_no' => $sale->offline_sale_id ?? null,
                'product_upc' => $product->upc ?? null,
                'product_name' => $product->name ?? null,
                'brand_name' => $brand->name ?? null,
                'department_name' => $department->name ?? null,
                'promoter_group_name' => $promoterGroup->name ?? null,
                'quantity' => $promoterCommissionUpdate->affected_by_type === ModelMapping::SALE_ITEM->name ? CommonFunctions::truncateDecimal(
                    (float) $saleItem?->quantity
                ) : CommonFunctions::truncateDecimal((float) $saleReturnItem?->quantity),
                'commission_percentage' => $promoterCommissionUpdate->commission_percentage,
                'commission_amount' => CommonFunctions::currencyFormat(
                    (float) $promoterCommissionUpdate->commission_amount,
                    4
                ),
            ];
        }

        $promoterCommissionUpdateData['total'] = [
            'total_quantity' => $promoterCommissionUpdates->sum('affected_by.quantity'),
            'total_commission_amount' => CommonFunctions::currencyFormat(
                (float) $promoterCommissionUpdates->sum('commission_amount')
            ),
        ];

        return $promoterCommissionUpdateData;
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === PromoterCommissionFilterTypes::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                PromoterCommissionFilterTypes::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === PromoterCommissionFilterTypes::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                PromoterCommissionFilterTypes::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === PromoterCommissionFilterTypes::BY_PROMOTER_GROUP->value && isset($filterData['group_ids']) && '' !== $filterData['group_ids']) {
            $promoterGroups = $promoterGroupQueries->getByIds($filterData['group_ids']);

            return $this->formatFilterResult(
                PromoterCommissionFilterTypes::BY_PROMOTER_GROUP->value,
                $promoterGroups->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return PromoterCommissionFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
