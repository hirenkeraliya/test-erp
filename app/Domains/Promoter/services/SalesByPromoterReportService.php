<?php

declare(strict_types=1);

namespace App\Domains\Promoter\services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\Enums\SalesByPromoterReportTypes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesByPromoterReportService
{
    public function printSalesByPromoter(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        if ((int) $filterData['report_type'] === SalesByPromoterReportTypes::BY_SUMMARY->value) {
            $salesByPromoterBySummaryReportService = resolve(SalesByPromoterBySummaryReportService::class);

            return $salesByPromoterBySummaryReportService->preparedBySummary($filterData, $company, $locations);
        }

        $salesByPromoterByDetailsReportService = resolve(SalesByPromoterByDetailsReportService::class);

        return $salesByPromoterByDetailsReportService->preparedByDetails($filterData, $company, $locations);
    }

    public function exportSalesByPromoter(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        if ((int) $filterData['report_type'] === SalesByPromoterReportTypes::BY_SUMMARY->value) {
            $salesByPromoterBySummaryReportService = resolve(SalesByPromoterBySummaryReportService::class);

            return $salesByPromoterBySummaryReportService->exportSalesByPromoterBySummary(
                $filterData,
                $company,
                $locations,
                $filename
            );
        }

        $salesByPromoterByDetailsReportService = resolve(SalesByPromoterByDetailsReportService::class);

        return $salesByPromoterByDetailsReportService->exportSalesByPromoterByDetails(
            $filterData,
            $company,
            $locations,
            $filename
        );
    }
}
