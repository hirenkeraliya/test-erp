<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Sale\Enums\WorstTwentyReportTypes;
use App\Exceptions\RedirectBackWithErrorException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $conditionalServices = config('app.product_variant')
            ? [
                WorstTwentyReportTypes::BY_ATTRIBUTES->value => resolve(WorstTwentyByAttributesReportService::class),
            ]
            : [
                WorstTwentyReportTypes::BY_STYLES->value => resolve(WorstTwentyByStyleReportService::class),
                WorstTwentyReportTypes::BY_COLORS->value => resolve(WorstTwentyByColorReportService::class),
            ];

        $reportTypeServices = [
            WorstTwentyReportTypes::BY_CATEGORIES->value => resolve(WorstTwentyByCategoryReportService::class),
            WorstTwentyReportTypes::BY_BRANDS->value => resolve(WorstTwentyByBrandReportService::class),
            WorstTwentyReportTypes::BY_PRODUCTS->value => resolve(WorstTwentyByProductReportService::class),
        ] + $conditionalServices + [
            WorstTwentyReportTypes::BY_MASTER_PRODUCT->value => resolve(WorstTwentyByArticleNumberReportService::class),
        ];

        $reportType = (int) $filterData['report_type'];

        if (! array_key_exists($reportType, $reportTypeServices)) {
            throw new RedirectBackWithErrorException('Please! Select Valid Report Type.');
        }

        return $reportTypeServices[$reportType]->print($companyId, $filterData);
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $conditionalServices = config('app.product_variant')
            ? [
                WorstTwentyReportTypes::BY_ATTRIBUTES->value => resolve(WorstTwentyByAttributesReportService::class),
            ]
            : [
                WorstTwentyReportTypes::BY_STYLES->value => resolve(WorstTwentyByStyleReportService::class),
                WorstTwentyReportTypes::BY_COLORS->value => resolve(WorstTwentyByColorReportService::class),
            ];

        $reportTypeServices = [
            WorstTwentyReportTypes::BY_CATEGORIES->value => resolve(WorstTwentyByCategoryReportService::class),
            WorstTwentyReportTypes::BY_BRANDS->value => resolve(WorstTwentyByBrandReportService::class),
            WorstTwentyReportTypes::BY_PRODUCTS->value => resolve(WorstTwentyByProductReportService::class),
        ]
            + $conditionalServices + [
                WorstTwentyReportTypes::BY_MASTER_PRODUCT->value => resolve(
                    WorstTwentyByArticleNumberReportService::class
                ),
            ];

        $reportType = (int) $filterData['report_type'];

        if (! array_key_exists($reportType, $reportTypeServices)) {
            throw new RedirectBackWithErrorException('Please! Select Valid Report Type.');
        }

        return $reportTypeServices[$reportType]->export($companyId, $filterData, $filename);
    }
}
