<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdersCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $location = $this->getLocation($filterData, $companyId);

        $html = '';

        if ((int) $filterData['report_type'] === OrderReportTypes::BY_DOCUMENT->value) {
            $ordersByDocumentReportService = resolve(OrdersByDocumentReportService::class);
            $html = $ordersByDocumentReportService->renderPreparedByDocument($filterData, $company, $location);
        }

        if ((int) $filterData['report_type'] === OrderReportTypes::BY_DETAILS->value) {
            $ordersByDetailsReportService = resolve(OrdersByDetailsReportService::class);
            $html = $ordersByDetailsReportService->renderPreparedByDetails($company, $location, $filterData);
        }

        if ((int) $filterData['report_type'] === OrderReportTypes::BY_SUMMARY->value) {
            $ordersBySummaryReportService = resolve(OrdersBySummaryReportService::class);
            $html = $ordersBySummaryReportService->renderPreparedBySummary($company, $location, $filterData);
        }

        return $html;
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $location = $this->getLocation($filterData, $companyId);

        if ((int) $filterData['report_type'] === OrderReportTypes::BY_DOCUMENT->value) {
            $ordersByDocumentReportService = resolve(OrdersByDocumentReportService::class);

            return $ordersByDocumentReportService->export($location, $filterData, $companyId, $filename);
        }

        if ((int) $filterData['report_type'] === OrderReportTypes::BY_DETAILS->value) {
            $ordersByDetailsReportService = resolve(OrdersByDetailsReportService::class);

            return $ordersByDetailsReportService->export($location, $filterData, $companyId, $filename);
        }

        $ordersBySummaryReportService = resolve(OrdersBySummaryReportService::class);

        return $ordersBySummaryReportService->export($location, $filterData, $companyId, $filename);
    }

    public function getLocation(array $filterData, int $companyId): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdWithNameAndCode($companyId, (int) $filterData['location_id']);
    }
}
