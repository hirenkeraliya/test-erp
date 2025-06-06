<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification\Services;

use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\GenuineProductVerification\GenuineProductVerificationQueries;
use App\Domains\Product\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductVerificationReportService
{
    public function print(array $filterData, Collection $filterColumns, int $companyId): string
    {
        $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);

        $prepareFilterData = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $prepareFilterData->buildFilterData($filterData);

        $genuineProductVerificationData = $genuineProductVerificationQueries->getProductVerificationReportDataForExport(
            $filterData,
            $companyId
        );

        $productVerificationsData = $this->preparedData($genuineProductVerificationData, $filterColumns);

        return view('prints.product_verification_report', [
            'productVerificationData' => $productVerificationsData,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function preparedData(Collection $genuineProductVerifications, Collection $filterColumns): Collection
    {
        $productService = resolve(ProductService::class);

        return $genuineProductVerifications->map(function ($genuineProductVerification) use (
            $filterColumns,
            $productService
        ): array {
            $product = $genuineProductVerification->product;
            $genuineProductVerificationData = [
                'name' => $genuineProductVerification->name,
                'mobile_number' => $genuineProductVerification->mobile_number,
                'email' => $genuineProductVerification->email,
                'product_name' => $product ? $product->name : 'N/A',
                'upc' => $product ? $product->upc : 'N/A',
                'color' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
                'is_genuine' => $genuineProductVerification->is_genuine ? 'Genuine' : 'Fake',
                'qr_code' => $genuineProductVerification->qr_code,
                'receipt_number' => $genuineProductVerification->receipt_number,
                'created_at' => $genuineProductVerification->created_at->format('d-m-Y D h:s:i A'),
                'remarks' => $genuineProductVerification->remarks,
                'attributes' => $productService->getAttributesWithNameAndValueKey($product),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($genuineProductVerificationData, $filterColumns);
        });
    }
}
