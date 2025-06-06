<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentReportType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockAdjustmentCustomReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $this->getLocations($filterData, $companyId);

        $html = '';

        if ((int) $filterData['report_type'] === StockAdjustmentReportType::BY_SUMMARY->value) {
            $stockAdjustmentBySummaryReportService = resolve(StockAdjustmentBySummaryReportService::class);
            $html = $stockAdjustmentBySummaryReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $locations,
            );
        }

        if ((int) $filterData['report_type'] === StockAdjustmentReportType::BY_DETAILS->value) {
            $stockAdjustmentByDetailsReportService = resolve(StockAdjustmentByDetailsReportService::class);
            $html = $stockAdjustmentByDetailsReportService->renderPreparedByDetails(
                $filterData,
                $company,
                $locations,
            );
        }

        return $html;
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $locations = $this->getLocations($filterData, $companyId);
        $company = $companyQueries->getNameAndCodeById($companyId);

        if ((int) $filterData['report_type'] === StockAdjustmentReportType::BY_DETAILS->value) {
            $stockTransferByDetailsReportService = resolve(StockAdjustmentByDetailsReportService::class);

            return $stockTransferByDetailsReportService->exportStockAdjustmentReportByDocumentExport(
                $company,
                $filterData,
                $filename,
                $locations,
            );
        }

        $stockTransferBySummaryReportService = resolve(StockAdjustmentBySummaryReportService::class);

        return $stockTransferBySummaryReportService->exportStockAdjustmentReportBySummaryExport(
            $company,
            $filterData,
            $filename,
            $locations,
        );
    }

    public function getLocations(array $filterData, int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids'])->toArray();
    }
}
