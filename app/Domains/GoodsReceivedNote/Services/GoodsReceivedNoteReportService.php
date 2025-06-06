<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteFilterTypes;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteReportTypes;
use App\Domains\GoodsReceivedNote\Exports\GoodsReceivedNoteByDetailsExport;
use App\Domains\GoodsReceivedNote\Exports\GoodsReceivedNoteByDocumentExport;
use App\Domains\GoodsReceivedNote\Exports\GoodsReceivedNoteBySummaryExport;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Vendor\VendorQueries;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GoodsReceivedNoteReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $location = $this->getLocation($filterData, $companyId);

        if ((int) $filterData['report_type'] === GoodsReceivedNoteReportTypes::BY_DOCUMENT->value) {
            $goodsReceivedNoteByDocumentReportService = resolve(GoodsReceivedNoteByDocumentReportService::class);

            return $goodsReceivedNoteByDocumentReportService->preparedByDocument($filterData, $company, $location);
        }

        if ((int) $filterData['report_type'] === GoodsReceivedNoteReportTypes::BY_SUMMARY->value) {
            $goodsReceivedNoteBySummaryReportService = resolve(GoodsReceivedNoteBySummaryReportService::class);

            return $goodsReceivedNoteBySummaryReportService->preparedBySummary($filterData, $company, $location);
        }

        if ((int) $filterData['report_type'] === GoodsReceivedNoteReportTypes::BY_DETAILS->value) {
            $goodsReceivedNoteByDetailsReportService = resolve(GoodsReceivedNoteByDetailsReportService::class);

            return $goodsReceivedNoteByDetailsReportService->preparedByDetails($filterData, $company, $location);
        }

        return '';
    }

    public function getLocation(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids']);
    }

    public function exportGoodsReceivedNote(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $this->getLocation($filterData, $companyId);
        $filterBy = $this->filterBy($filterData, $companyId);
        if ((int) $filterData['report_type'] === GoodsReceivedNoteReportTypes::BY_DOCUMENT->value) {
            $goodsReceivedNoteByDocumentReportService = resolve(GoodsReceivedNoteByDocumentReportService::class);

            [$goodsReceivedNotes, $columns, $dateRange] = $goodsReceivedNoteByDocumentReportService->fetchRecords(
                $filterData,
                $company,
                $locations
            );

            return Excel::download(
                new GoodsReceivedNoteByDocumentExport(
                    $goodsReceivedNotes,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GoodsReceivedNoteReportTypes::BY_SUMMARY->value) {
            $goodsReceivedNoteBySummaryReportService = resolve(GoodsReceivedNoteBySummaryReportService::class);

            [$goodsReceivedNotes, $columns, $dateRange] = $goodsReceivedNoteBySummaryReportService->fetchRecords(
                $filterData,
                $company,
                $locations
            );

            return Excel::download(
                new GoodsReceivedNoteBySummaryExport(
                    $goodsReceivedNotes,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                ),
                $filename
            );
        }

        $goodsReceivedNoteByDetailsReportService = resolve(GoodsReceivedNoteByDetailsReportService::class);

        [$goodsReceivedNotes, $columns, $dateRange] = $goodsReceivedNoteByDetailsReportService->fetchRecords(
            $filterData,
            $company,
            $locations
        );

        return Excel::download(
            new GoodsReceivedNoteByDetailsExport($goodsReceivedNotes, $columns, $company, $dateRange, $filterBy),
            $filename
        );
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value && isset($filterData['article_number']) && '' !== $filterData['article_number']) {
            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value,
                $filterData['article_number']
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_VENDOR->value && isset($filterData['vendor_ids']) && '' !== $filterData['vendor_ids']) {
            $vendors = $vendorQueries->getByIds($filterData['vendor_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_VENDOR->value,
                $vendors->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return GoodsReceivedNoteFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
