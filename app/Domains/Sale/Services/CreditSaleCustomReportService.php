<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Sale\Enums\CreditReportTypes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditSaleCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        if ((int) $filterData['report_type'] === CreditReportTypes::BY_DETAILS->value) {
            $creditSaleCustomReportByDetailsService = resolve(CreditSaleCustomReportByDetailsService::class);

            return $creditSaleCustomReportByDetailsService->print($filterData, $companyId);
        }

        $creditSaleCustomReportBySummaryService = resolve(CreditSaleCustomReportBySummaryService::class);

        return $creditSaleCustomReportBySummaryService->print($filterData, $companyId);
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        if ((int) $filterData['report_type'] === CreditReportTypes::BY_DETAILS->value) {
            $creditSaleCustomReportByDetailsService = resolve(CreditSaleCustomReportByDetailsService::class);

            return $creditSaleCustomReportByDetailsService->export($filterData, $companyId, $filename);
        }

        $creditSaleCustomReportBySummaryService = resolve(CreditSaleCustomReportBySummaryService::class);

        return $creditSaleCustomReportBySummaryService->export($filterData, $companyId, $filename);
    }
}
