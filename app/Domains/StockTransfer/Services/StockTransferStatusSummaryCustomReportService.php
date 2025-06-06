<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\StockTransferStatusSummaryReportType;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferStatusSummaryCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $filterData['location_ids'] ? $this->getLocations($filterData, $companyId) : collect([]);

        $html = '';

        if ((int) $filterData['report_type'] === StockTransferStatusSummaryReportType::BY_SUMMARY->value) {
            $stockAdjustmentBySummaryReportService = resolve(StockTransferStatusSummaryBySummaryReportService::class);
            $html = $stockAdjustmentBySummaryReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $locations,
            );
        }

        return $html;
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $locations = $filterData['location_ids'] ? $this->getLocations($filterData, $companyId) : collect([]);

        $stockAdjustmentBySummaryReportService = resolve(StockTransferStatusSummaryBySummaryReportService::class);

        return $stockAdjustmentBySummaryReportService->exportStockTransferStatusSummaryReportExport(
            $companyId,
            $filterData,
            $filename,
            $locations,
        );
    }

    private function getLocations(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids']);
    }
}
