<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Services;

use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\Product\Services\ProductService;
use App\Domains\ProductAgeingReport\Enums\AgeCategories;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Models\ProductAgeing;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class ProductAgeingReportService
{
    public function print(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $products = $productAgeingQueries->getProductsAgeingReportForExport($filterData, $companyId);

        $productsData = $this->preparedData($products, $filterColumns);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return view('prints.product_ageing_report', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function printByArticleNumber(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $productAgeings = $productAgeingQueries->getProductsAgeingReportByArticleNumberForExport(
            $filterData,
            $companyId
        );

        $productsData = $this->preparedDataByArticleNumber($productAgeings, $filterColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return view('prints.product_ageing_report_by_article_number', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function printByUpc(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $productAgeings = $productAgeingQueries->getProductsAgeingReportByUpcForExport($filterData, $companyId);

        $productsData = $this->preparedDataByUpc($productAgeings, $filterColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return view('prints.product_ageing_report_by_upc', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function preparedData(Collection $productAgeings, Collection $filterColumns): Collection
    {
        $productAgeingAgeingReportService = resolve(self::class);
        $productService = resolve(ProductService::class);

        return $productAgeings->map(function ($productAgeing) use (
            $productAgeingAgeingReportService,
            $filterColumns,
            $productService
        ): array {
            $productAgeingData = [
                'id' => $productAgeing->product->id,
                'location' => $productAgeing->location->getNameWithCode(),
                'product' => $productAgeing->product->name,
                'upc' => $productAgeing->product->upc,
                'article_number' => $productAgeing->product->article_number ?: 'N/A',
                'color' => config('app.product_variant') ? null : $productAgeing->product?->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? null : $productAgeing->product?->size?->name ?? 'N/A',
                'created_at' => $this->getCreatedAt($productAgeing),
                'last_selling_date' => $this->getLastSellingDate($productAgeing),
                'quantity_sold' => (float) $productAgeing->quantity_sold,
                'quantity_remaining' => (float) $productAgeing->quantity_remaining,
                'age_of_the_product' => $productAgeing->age_category . ' Days',
                'age_category' => $productAgeingAgeingReportService->getAgeCategory($productAgeing->age_category),
                'first_grn_date' => $this->getFirstGrnDate($productAgeing),
                'first_transfer_in_date' => $this->getFirstTransferInDate($productAgeing),
                'attributes' => $productService->getAttributesForPrint($productAgeing->product),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productAgeingData, $filterColumns);
        });
    }

    public function preparedDataByArticleNumber(Collection $productAgeings, Collection $filterColumns): Collection
    {
        $productAgeingAgeingReportService = resolve(self::class);

        return $productAgeings->map(function ($productAgeing) use (
            $productAgeingAgeingReportService,
            $filterColumns
        ): array {
            $productAgeingDataByArticleNumber = [
                'id' => $productAgeing->product->id,
                'product' => $productAgeing->product->name,
                'article_number' => $productAgeing->product->article_number ?: 'N/A',
                'created_at' => $this->getCreatedAt($productAgeing),
                'first_grn_date' => $this->getFirstGrnDate($productAgeing),
                'first_transfer_in_date' => $this->getFirstTransferInDate($productAgeing),
                'last_selling_date' => $this->getLastSellingDate($productAgeing),
                'quantity_sold' => (float) $productAgeing->quantity_sold,
                'quantity_remaining' => (float) $productAgeing->quantity_remaining,
                'age_of_the_product' => $productAgeing->age_category_based_on_created_at . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_created_at
                ) . ')',
                'age_of_the_product_first_grn' => null === $productAgeing->age_category_based_on_first_goods_received_note ? 'N/A' : $productAgeing->age_category_based_on_first_goods_received_note . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_first_goods_received_note
                ) . ')',
                'age_of_the_product_first_transfer_in' => null === $productAgeing->age_category_based_on_first_transfer_in ? 'N/A' : $productAgeing->age_category_based_on_first_transfer_in . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_first_transfer_in
                ) . ')',
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productAgeingDataByArticleNumber, $filterColumns);
        });
    }

    public function preparedDataByUpc(Collection $productAgeings, Collection $filterColumns): Collection
    {
        $productAgeingAgeingReportService = resolve(self::class);
        $productService = resolve(ProductService::class);

        return $productAgeings->map(function ($productAgeing) use (
            $productAgeingAgeingReportService,
            $filterColumns,
            $productService
        ): array {
            $productAgeingDataForUpc = [
                'id' => $productAgeing->product->id,
                'product' => $productAgeing->product->name,
                'upc' => $productAgeing->product->upc,
                'article_number' => $productAgeing->product->article_number ?: 'N/A',
                'color' => config('app.product_variant') ? null : $productAgeing->product?->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? null : $productAgeing->product?->size?->name ?? 'N/A',
                'created_at' => $this->getCreatedAt($productAgeing),
                'first_grn_date' => $this->getFirstGrnDate($productAgeing),
                'first_transfer_in_date' => $this->getFirstTransferInDate($productAgeing),
                'last_selling_date' => $this->getLastSellingDate($productAgeing),
                'quantity_sold' => (float) $productAgeing->quantity_sold,
                'quantity_remaining' => (float) $productAgeing->quantity_remaining,
                'age_of_the_product' => $productAgeing->age_category_based_on_created_at . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_created_at
                ) . ')',
                'age_of_the_product_first_grn' => null === $productAgeing->age_category_based_on_first_goods_received_note ? 'N/A' : $productAgeing->age_category_based_on_first_goods_received_note . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_first_goods_received_note
                ) . ')',
                'age_of_the_product_first_transfer_in' => null === $productAgeing->age_category_based_on_first_transfer_in ? 'N/A' : $productAgeing->age_category_based_on_first_transfer_in . ' Days (' . $productAgeingAgeingReportService->getAgeCategory(
                    $productAgeing->age_category_based_on_first_transfer_in
                ) . ')',
                'attributes' => $productService->getAttributesForPrint($productAgeing->product),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productAgeingDataForUpc, $filterColumns);
        });
    }

    public function getQuantityRemaining(ProductAgeing $productAgeing): float
    {
        return (float) $productAgeing->quantity_remaining;
    }

    public function getAgeOfTheProduct(ProductAgeing $productAgeing, int $ageOfProductType): int
    {
        $firstGrnInventoryUpdateDate = $productAgeing->first_goods_received_note;

        $firstTransferInInventoryUpdateDate = $productAgeing->first_transfer_in;

        $currentDate = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'));

        if (false === $currentDate) {
            return 0;
        }

        if (
            $ageOfProductType === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value
            && $firstGrnInventoryUpdateDate
        ) {
            return $currentDate->diffInDays($firstGrnInventoryUpdateDate);
        }

        if (
            $ageOfProductType === AgeOfProductTypes::FIRST_TRANSFER_IN->value
            && $firstTransferInInventoryUpdateDate
        ) {
            return $currentDate->diffInDays($firstTransferInInventoryUpdateDate);
        }

        if (! $productAgeing->product_created_at) {
            return 0;
        }

        if ($ageOfProductType !== AgeOfProductTypes::CREATED_AT->value) {
            return 0;
        }

        return $currentDate->diffInDays($productAgeing->product_created_at);
    }

    public function getAgeCategory(int $ageOfTheProduct): string
    {
        return AgeCategories::getAgeCategoriesByDays($ageOfTheProduct);
    }

    public function printByMonthAndYear(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $products = $productAgeingQueries->getProductsAgeingReportByMonthAndYearForExport($filterData, $companyId);

        $productsData = $this->preparedDataByMonthAndYear(
            $products,
            (int) $filterData['age_of_product_type'],
            $filterColumns
        );

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return view('prints.product_ageing_report_by_month_and_year', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function preparedDataByMonthAndYear(
        Collection $productAgeings,
        int $ageOfProductType,
        Collection $filterColumns
    ): Collection {
        $productAgeingAgeingReportService = resolve(self::class);
        $productService = resolve(ProductService::class);

        return $productAgeings->map(function ($productAgeing) use (
            $productAgeingAgeingReportService,
            $filterColumns,
            $productService
        ): array {
            $productAgeingDataForMonthAndYear = [
                'id' => $productAgeing->product->id,
                'product' => $productAgeing->product->name,
                'location' => $productAgeing->location->getNameWithCode(),
                'upc' => $productAgeing->product->upc,
                'article_number' => $productAgeing->product->article_number ?: 'N/A',
                'color' => config('app.product_variant') ? null : $productAgeing->product?->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? null : $productAgeing->product?->size?->name ?? 'N/A',
                'created_at' => $this->getCreatedAt($productAgeing),
                'last_selling_date' => $this->getLastSellingDate($productAgeing),
                'quantity_sold' => (float) $productAgeing->quantity_sold,
                'quantity_remaining' => (float) $productAgeing->quantity_remaining,
                'age_of_the_product' => $productAgeing->age_category . ' Days',
                'age_category' => $productAgeingAgeingReportService->getAgeCategory($productAgeing->age_category),
                'first_month_quantity_sold' => $productAgeing->first_month_sold,
                'second_month_quantity_sold' => $productAgeing->second_month_sold,
                'third_month_quantity_sold' => $productAgeing->third_month_sold,
                'fourth_month_quantity_sold' => $productAgeing->fourth_month_sold,
                'fifth_month_quantity_sold' => $productAgeing->fifth_month_sold,
                'sixth_month_quantity_sold' => $productAgeing->sixth_month_sold,
                'seventh_month_quantity_sold' => $productAgeing->seventh_month_sold,
                'eighth_month_quantity_sold' => $productAgeing->eighth_month_sold,
                'ninth_month_quantity_sold' => $productAgeing->ninth_month_sold,
                'tenth_month_quantity_sold' => $productAgeing->tenth_month_sold,
                'eleventh_month_quantity_sold' => $productAgeing->eleventh_month_sold,
                'twelfth_month_quantity_sold' => $productAgeing->twelfth_month_sold,
                'first_grn_date' => $this->getFirstGrnDate($productAgeing),
                'first_transfer_in_date' => $this->getFirstTransferInDate($productAgeing),
                'attributes' => $productService->getAttributesForPrint($productAgeing->product),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productAgeingDataForMonthAndYear, $filterColumns);
        });
    }

    public function getQuantitySold(self|ProductAgeing $productAgeing): float
    {
        /* @phpstan-ignore-next-line */
        return (float) ($productAgeing->first_month_sold + $productAgeing->second_month_sold + $productAgeing->third_month_sold + $productAgeing->fourth_month_sold + $productAgeing->fifth_month_sold + $productAgeing->sixth_month_sold + $productAgeing->seventh_month_sold + $productAgeing->eighth_month_sold + $productAgeing->ninth_month_sold + $productAgeing->tenth_month_sold + $productAgeing->eleventh_month_sold + $productAgeing->twelfth_month_sold);
    }

    public function getCreatedAt(ProductAgeing $productAgeing): string
    {
        /** @var Carbon $createdAt */
        $createdAt = Carbon::createFromFormat('Y-m-d', $productAgeing->product_created_at);

        return $createdAt->format('d-m-Y');
    }

    public function getLastSellingDate(ProductAgeing $productAgeing): string
    {
        $lastSellingDate = 'N/A';
        if ($productAgeing->last_selling_date) {
            /** @var Carbon $lastSellingDateFormat */
            $lastSellingDateFormat = Carbon::createFromFormat('Y-m-d', $productAgeing->last_selling_date);
            $lastSellingDate = $lastSellingDateFormat->format('d-m-Y');
        }

        return $lastSellingDate;
    }

    public function getFirstGrnDate(ProductAgeing $productAgeing): string
    {
        $firstGrnInventoryUpdateDate = $productAgeing->first_goods_received_note;

        if ($firstGrnInventoryUpdateDate) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d', $firstGrnInventoryUpdateDate);

            return $happenedAtFormat->format('d-m-Y');
        }

        return 'N/A';
    }

    public function getFirstTransferInDate(ProductAgeing $productAgeing): string
    {
        $firstTransferInInventoryUpdateDate = $productAgeing->first_transfer_in;

        if ($firstTransferInInventoryUpdateDate) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d', $firstTransferInInventoryUpdateDate);

            return $happenedAtFormat->format('d-m-Y');
        }

        return 'N/A';
    }

    public function exportProductAgeingWithJob(
        User $user,
        array $filterData,
        int $companyId,
        Collection $columns
    ): array {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $totalRecords = $productAgeingQueries->getProductAgeingExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_AGEING->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord);

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportProductAgeingByMonthAndYearWithJob(
        User $user,
        array $filterData,
        int $companyId,
        Collection $columns
    ): array {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $totalRecords = $productAgeingQueries->getProductAgeingByMonthAndYearExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_AGEING_BY_MONTH_AND_YEAR->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord);

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportProductAgeingByArticleNumberWithJob(
        User $user,
        array $filterData,
        int $companyId,
        Collection $columns
    ): array {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $totalRecords = $productAgeingQueries->getProductAgeingExportCountByArticleNumber($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_AGEING_BY_ARTICLE_NUMBER->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord);

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportProductAgeingByUpcWithJob(
        User $user,
        array $filterData,
        int $companyId,
        Collection $columns
    ): array {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);
        $totalRecords = $productAgeingQueries->getProductAgeingExportCountByUpc($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_AGEING_BY_UPC->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord);

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function getProductAgeingByMonthAndYearHeadings(Collection $filteredColumns): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($filteredColumns);
    }

    public function getProductAgeingByArticleNumberHeadings(): array
    {
        return [
            'Product',
            'Article Number',
            'Created At',
            'First Grn Date',
            'First Transfer In Date',
            'Last Sold Date',
            'Unit Sold',
            'Balance Stock',
            'Ageing Based On Created At',
            'Ageing Based On First GRN Date',
            'Ageing Based On Transfer In Date',
        ];
    }

    public function getProductAgeingByUpcHeadings(): array
    {
        return [
            'Product',
            'Upc',
            'Article Number',
            'Color',
            'Size',
            'Created At',
            'First Grn Date',
            'First Transfer In Date',
            'Last Sold Date',
            'Unit Sold',
            'Balance Stock',
            'Ageing Based On Created At',
            'Ageing Based On First GRN Date',
            'Ageing Based On Transfer In Date',
        ];
    }
}
