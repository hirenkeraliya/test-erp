<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\TransferReportType;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferCustomReportService
{
    public function print(int $companyId, array $filterData, bool $displayTotal): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $location = $this->getLocation($filterData, $companyId);

        $html = '';

        if ((int) $filterData['report_by'] === TransferReportType::BY_DOCUMENT->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferByDocumentReportService::class);
            $html = $stockTransferByDocumentReportService->renderPreparedByDocument(
                $filterData,
                $company,
                $location,
                $displayTotal
            );
        }

        if ((int) $filterData['report_by'] === TransferReportType::BY_DETAILS->value) {
            $stockTransferByDetailsReportService = resolve(StockTransferByDetailsReportService::class);
            $html = $stockTransferByDetailsReportService->renderPreparedByDetails(
                $filterData,
                $company,
                $location,
                $displayTotal
            );
        }

        if ((int) $filterData['report_by'] === TransferReportType::BY_SUMMARY->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferBySummaryReportService::class);
            $html = $stockTransferByDocumentReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayTotal
            );
        }

        if ((int) $filterData['report_by'] === TransferReportType::BY_SUMMARY_UPC->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferBySummaryByUpcReportService::class);
            $html = $stockTransferByDocumentReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayTotal
            );
        }

        return $html;
    }

    public function export(
        int $companyId,
        array $filterData,
        string $filename,
        bool $displayTotal
    ): BinaryFileResponse {
        $location = $this->getLocation($filterData, $companyId);

        if ((int) $filterData['report_by'] === TransferReportType::BY_DOCUMENT->value) {
            $stockTransferByDocumentReportService = resolve(StockTransferByDocumentReportService::class);

            return $stockTransferByDocumentReportService->exportStockTransferReportByDocumentExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayTotal
            );
        }

        if ((int) $filterData['report_by'] === TransferReportType::BY_DETAILS->value) {
            $stockTransferByDetailsReportService = resolve(StockTransferByDetailsReportService::class);

            return $stockTransferByDetailsReportService->exportStockTransferReportByDetailsExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayTotal
            );
        }

        if ((int) $filterData['report_by'] === TransferReportType::BY_SUMMARY_UPC->value) {
            $stockTransferByDetailsReportService = resolve(StockTransferBySummaryByUpcReportService::class);

            return $stockTransferByDetailsReportService->exportStockTransferReportBySummaryByUpcExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayTotal
            );
        }

        $stockTransferBySummaryReportService = resolve(StockTransferBySummaryReportService::class);

        return $stockTransferBySummaryReportService->exportStockTransferReportBySummaryExport(
            $companyId,
            $filterData,
            $filename,
            $location,
            $displayTotal
        );
    }

    public function getLocation(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids']);
    }

    public function formatStockTransferDate(StockTransfer $stockTransfer, array $filterData): string
    {
        $dateFields = [
            StockTransferCustomReportDateTypes::CREATED_AT->value => 'created_at',
            StockTransferCustomReportDateTypes::REJECTED_AT->value => 'rejected_at',
            StockTransferCustomReportDateTypes::CANCELLED_AT->value => 'cancelled_at',
            StockTransferCustomReportDateTypes::CLOSED_AT->value => 'closed_at',
            StockTransferCustomReportDateTypes::DISCREPANCY_AT->value => 'discrepancy_at',
            StockTransferCustomReportDateTypes::SYSTEM_RECEIVED_AT->value => 'received_at',
            StockTransferCustomReportDateTypes::MANUAL_RECEIVED_DATE->value => 'received_date',
            StockTransferCustomReportDateTypes::SHIPPED_AT->value => 'shipped_at',
            StockTransferCustomReportDateTypes::APPROVED_AT->value => 'approved_at',
            StockTransferCustomReportDateTypes::OPENED_AT->value => 'opened_at',
            StockTransferCustomReportDateTypes::REQUIRE_DATE->value => 'require_date',
            StockTransferCustomReportDateTypes::TRANSFER_DATE->value => 'transfer_date',
        ];

        $field = $dateFields[$filterData['display_date_type']] ?? null;

        if ($field) {
            $date = $stockTransfer->{$field} ? Carbon::createFromFormat(
                'received_date' === $field || 'transfer_date' === $field || 'require_date' === $field ? 'Y-m-d' : 'Y-m-d H:i:s',
                $stockTransfer->{$field}
            ) : null;

            return $date ? $date->format('d-m-Y') : 'N/A';
        }

        return 'N/A';
    }

    public function formatDateSelectionName(array $filterData, string $column): string
    {
        if ($filterData[$column] === StockTransferCustomReportDateTypes::SYSTEM_RECEIVED_AT->value) {
            return 'Received At';
        }

        if ($filterData[$column] === StockTransferCustomReportDateTypes::MANUAL_RECEIVED_DATE->value) {
            return 'Received Date';
        }

        return StockTransferCustomReportDateTypes::getFormattedCaseName($filterData[$column]);
    }
}
