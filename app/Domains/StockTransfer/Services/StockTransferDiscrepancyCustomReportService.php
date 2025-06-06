<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\TransferTypeDiscrepancyReport;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferDiscrepancyCustomReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locations = $this->getLocations($filterData, $companyId);

        $html = '';

        if ((int) $filterData['report_by'] === TransferTypeDiscrepancyReport::BY_DOCUMENT->value) {
            $stockTransferDiscrepancyByDocumentReportService = resolve(
                StockTransferDiscrepancyByDocumentReportService::class
            );
            $html = $stockTransferDiscrepancyByDocumentReportService->renderPreparedByDocument(
                $filterData,
                $company,
                $locations
            );
        }

        if ((int) $filterData['report_by'] === TransferTypeDiscrepancyReport::BY_DETAILS->value) {
            $stockTransferByDetailsReportService = resolve(StockTransferDiscrepancyByDetailsReportService::class);
            $html = $stockTransferByDetailsReportService->renderPreparedByDetails($filterData, $company, $locations);
        }

        if ((int) $filterData['report_by'] === TransferTypeDiscrepancyReport::BY_SUMMARY->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferDiscrepancyBySummaryReportService::class);
            $html = $stockTransferByDocumentReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $locations,
            );
        }

        return $html;
    }

    public function export(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $locations = $this->getLocations($filterData, $companyId);

        if ((int) $filterData['report_by'] === TransferTypeDiscrepancyReport::BY_DOCUMENT->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferDiscrepancyByDocumentReportService::class);

            return $stockTransferByDocumentReportService->exportStockTransferReportByDocumentExport(
                $companyId,
                $filterData,
                $filename,
                $locations,
            );
        }

        if ((int) $filterData['report_by'] === TransferTypeDiscrepancyReport::BY_DETAILS->value) {
            $stockTransferByDetailsReportService = resolve(StockTransferDiscrepancyByDetailsReportService::class);

            return $stockTransferByDetailsReportService->exportStockTransferReportByDetailsExport(
                $companyId,
                $filterData,
                $filename,
                $locations,
            );
        }

        $stockTransferDiscrepancyBySummaryReportService = resolve(
            StockTransferDiscrepancyBySummaryReportService::class
        );

        return $stockTransferDiscrepancyBySummaryReportService->exportStockTransferReportBySummaryExport(
            $companyId,
            $filterData,
            $filename,
            $locations
        );
    }

    public function getLocations(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids']);
    }
}
