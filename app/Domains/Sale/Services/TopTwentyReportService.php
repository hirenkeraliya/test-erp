<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Sale\Enums\TopTwentyReportTypes;
use App\Exceptions\RedirectBackWithErrorException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $reportTypeServices = [
            TopTwentyReportTypes::BY_CATEGORIES->value => resolve(TopTwentyByCategoryReportService::class),
            TopTwentyReportTypes::BY_STYLES->value => resolve(TopTwentyByStyleReportService::class),
            TopTwentyReportTypes::BY_BRANDS->value => resolve(TopTwentyByBrandReportService::class),
            TopTwentyReportTypes::BY_PRODUCTS->value => resolve(TopTwentyByProductReportService::class),
            TopTwentyReportTypes::BY_COLORS->value => resolve(TopTwentyByColorReportService::class),
            TopTwentyReportTypes::BY_MASTER_PRODUCT->value => resolve(TopTwentyByArticleNumberReportService::class),
            TopTwentyReportTypes::BY_ATTRIBUTES->value => resolve(TopTwentyByAttributeReportService::class),
        ];

        $reportType = (int) $filterData['report_type'];

        if (! array_key_exists($reportType, $reportTypeServices)) {
            throw new RedirectBackWithErrorException('Please! Select Valid Report Type.');
        }

        return $reportTypeServices[$reportType]->print($companyId, $filterData);
    }

    public function exportTopTwenty(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $reportTypeServices = [
            TopTwentyReportTypes::BY_CATEGORIES->value => resolve(TopTwentyByCategoryReportService::class),
            TopTwentyReportTypes::BY_STYLES->value => resolve(TopTwentyByStyleReportService::class),
            TopTwentyReportTypes::BY_BRANDS->value => resolve(TopTwentyByBrandReportService::class),
            TopTwentyReportTypes::BY_PRODUCTS->value => resolve(TopTwentyByProductReportService::class),
            TopTwentyReportTypes::BY_COLORS->value => resolve(TopTwentyByColorReportService::class),
            TopTwentyReportTypes::BY_MASTER_PRODUCT->value => resolve(TopTwentyByArticleNumberReportService::class),
            TopTwentyReportTypes::BY_ATTRIBUTES->value => resolve(TopTwentyByAttributeReportService::class),
        ];

        $reportType = (int) $filterData['report_type'];

        if (! array_key_exists($reportType, $reportTypeServices)) {
            throw new RedirectBackWithErrorException('Please! Select Valid Report Type.');
        }

        return $reportTypeServices[$reportType]->export($companyId, $filterData, $filename);
    }
}
