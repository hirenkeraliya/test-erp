<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Sale\Enums\LayawayReportTypes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LayawaySaleCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        if ((int) $filterData['report_type'] === LayawayReportTypes::BY_DETAILS->value) {
            $layawaySaleCustomReportByDetailsService = resolve(LayawaySaleCustomReportByDetailsService::class);

            return $layawaySaleCustomReportByDetailsService->print($filterData, $companyId);
        }

        $layawaySaleCustomReportBySummaryService = resolve(LayawaySaleCustomReportBySummaryService::class);

        return $layawaySaleCustomReportBySummaryService->print($filterData, $companyId);
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        if ((int) $filterData['report_type'] === LayawayReportTypes::BY_DETAILS->value) {
            $layawaySaleCustomReportByDetailsService = resolve(LayawaySaleCustomReportByDetailsService::class);

            return $layawaySaleCustomReportByDetailsService->export($filterData, $companyId, $filename);
        }

        $layawaySaleCustomReportBySummaryService = resolve(LayawaySaleCustomReportBySummaryService::class);

        return $layawaySaleCustomReportBySummaryService->export($filterData, $companyId, $filename);
    }
}
