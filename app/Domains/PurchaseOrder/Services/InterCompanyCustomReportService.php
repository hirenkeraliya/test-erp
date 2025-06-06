<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes as EnumsLocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Enums\InterCompanyCustomReportTypes;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferReportType;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrderFulfillment\Services\InterCompanyByDetailsReportForDeliveryOrderService;
use App\Domains\PurchaseOrderFulfillment\Services\InterCompanyByDocumentReportForDeliveryOrderService;
use App\Domains\PurchaseOrderFulfillment\Services\InterCompanyBySummaryByUpcReportForDeliveryOrderService;
use App\Domains\PurchaseOrderFulfillment\Services\InterCompanyBySummaryReportForDeliveryOrderService;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyCustomReportService
{
    public function print(int $companyId, array $filterData, bool $displayPurchaseCost): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $location = $this->getLocation($filterData, $companyId);

        $html = '';

        if ((int) $filterData['transfer_type'] === InterCompanyTransferType::DELIVERY_ORDER->value) {
            return $this->printDeliveryOrder($companyId, $filterData, $displayPurchaseCost);
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DOCUMENT->value) {
            $interCompanyByDocumentReportService = resolve(InterCompanyByDocumentReportService::class);
            $html = $interCompanyByDocumentReportService->renderPreparedByDocument(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DETAILS->value) {
            $interCompanyByDetailsReportService = resolve(InterCompanyByDetailsReportService::class);
            $html = $interCompanyByDetailsReportService->renderPreparedByDetails(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::SUMMARY_BY_ARTICLE->value) {
            $interCompanyBySummaryReportService = resolve(InterCompanyBySummaryReportService::class);
            $html = $interCompanyBySummaryReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_SUMMARY_UPC->value) {
            $interCompanyBySummaryByUpcReportService = resolve(InterCompanyBySummaryByUpcReportService::class);
            $html = $interCompanyBySummaryByUpcReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        return $html;
    }

    public function export(
        int $companyId,
        array $filterData,
        string $filename,
        bool $displayPurchaseCost
    ): BinaryFileResponse {
        $location = $this->getLocation($filterData, $companyId);

        if ((int) $filterData['transfer_type'] === InterCompanyTransferType::DELIVERY_ORDER->value) {
            return $this->exportDeliveryOrder($companyId, $filterData, $filename, $displayPurchaseCost);
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DOCUMENT->value) {
            $interCompanyByDocumentReportService = resolve(InterCompanyByDocumentReportService::class);

            return $interCompanyByDocumentReportService->exportInterCompanyReportByDocumentExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DETAILS->value) {
            $interCompanyByDetailsReportService = resolve(InterCompanyByDetailsReportService::class);

            return $interCompanyByDetailsReportService->exportStockTransferReportByDetailsExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_SUMMARY_UPC->value) {
            $interCompanyBySummaryByUpcReportService = resolve(InterCompanyBySummaryByUpcReportService::class);

            return $interCompanyBySummaryByUpcReportService->exportStockTransferReportBySummaryByUpcExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        $interCompanyBySummaryReportService = resolve(InterCompanyBySummaryReportService::class);

        return $interCompanyBySummaryReportService->exportStockTransferReportBySummaryExport(
            $companyId,
            $filterData,
            $filename,
            $location,
            $displayPurchaseCost
        );
    }

    public function exportDeliveryOrder(
        int $companyId,
        array $filterData,
        string $filename,
        bool $displayPurchaseCost
    ): BinaryFileResponse {
        $location = $this->getLocation($filterData, $companyId);

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DOCUMENT->value) {
            $interCompanyByDocumentReportForDeliveryOrderService = resolve(
                InterCompanyByDocumentReportForDeliveryOrderService::class
            );

            return $interCompanyByDocumentReportForDeliveryOrderService->exportInterCompanyReportByDocumentExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DETAILS->value) {
            $interCompanyByDetailsReportForDeliveryOrderService = resolve(
                InterCompanyByDetailsReportForDeliveryOrderService::class
            );

            return $interCompanyByDetailsReportForDeliveryOrderService->exportStockTransferReportByDetailsExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_SUMMARY_UPC->value) {
            $interCompanyBySummaryByUpcReportForDeliveryOrderService = resolve(
                InterCompanyBySummaryByUpcReportForDeliveryOrderService::class
            );

            return $interCompanyBySummaryByUpcReportForDeliveryOrderService->exportStockTransferReportBySummaryByUpcExport(
                $companyId,
                $filterData,
                $filename,
                $location,
                $displayPurchaseCost
            );
        }

        $interCompanyBySummaryReportForDeliveryOrderService = resolve(
            InterCompanyBySummaryReportForDeliveryOrderService::class
        );

        return $interCompanyBySummaryReportForDeliveryOrderService->exportStockTransferReportBySummaryExport(
            $companyId,
            $filterData,
            $filename,
            $location,
            $displayPurchaseCost
        );
    }

    public function getLocation(array $filterData, int $companyId): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getByIdWithNameAndCode($companyId, (int) $filterData['location_id']);
    }

    public function getToLocation(PurchaseOrder $purchaseOrder): string
    {
        /** @var Location $location */
        $location = $purchaseOrder->location;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $locationType = EnumsLocationTypes::getFormattedCaseName($location->type_id);
        $externalLocationType = $externalLocation->type_id ? EnumsLocationTypes::getFormattedCaseName(
            $externalLocation->type_id
        ) : null;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return $location->name . ' (' . $locationType . ')';
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && null === $purchaseOrder->created_by_company_id
        ) {
            return $location->name . ' (' . $locationType . ')';
        }

        return $externalLocation->name . ' (' . $externalLocationType . ')';
    }

    public function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === InterCompanyCustomReportTypes::BY_PRODUCT->value && $this->isValidProductIdFilter(
            $filterData
        )) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                InterCompanyCustomReportTypes::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy !== InterCompanyCustomReportTypes::BY_MASTER_PRODUCT->value) {
            return '';
        }

        if ($this->isValidArticleNumberFilter($filterData)) {
            return $this->formatFilterResult(
                InterCompanyCustomReportTypes::BY_MASTER_PRODUCT->value,
                $filterData['article_number']
            );
        }

        return '';
    }

    private function isValidProductIdFilter(array $filterData): bool
    {
        return isset($filterData['product_id']) && '' !== $filterData['product_id'];
    }

    private function isValidArticleNumberFilter(array $filterData): bool
    {
        return isset($filterData['article_number']) && '' !== $filterData['article_number'];
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return InterCompanyCustomReportTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }

    public function transferType(array $filterData): string
    {
        if (! isset($filterData['transfer_type'])) {
            return '';
        }

        $transferType = (int) $filterData['transfer_type'];

        if ($transferType === InterCompanyTransferType::TRANSFER_REQUEST->value) {
            return InterCompanyTransferType::getFormattedCaseName(InterCompanyTransferType::TRANSFER_REQUEST->value);
        }

        if ($transferType === InterCompanyTransferType::PURCHASE_REQUEST->value) {
            return InterCompanyTransferType::getFormattedCaseName(InterCompanyTransferType::PURCHASE_REQUEST->value);
        }

        if ($transferType === InterCompanyTransferType::SALES_ORDER->value) {
            return InterCompanyTransferType::getFormattedCaseName(InterCompanyTransferType::SALES_ORDER->value);
        }

        if ($transferType === InterCompanyTransferType::PURCHASE_ORDER->value) {
            return InterCompanyTransferType::getFormattedCaseName(InterCompanyTransferType::PURCHASE_ORDER->value);
        }

        if ($transferType === InterCompanyTransferType::DELIVERY_ORDER->value) {
            return InterCompanyTransferType::getFormattedCaseName(InterCompanyTransferType::DELIVERY_ORDER->value);
        }

        return '';
    }

    public function fetchProductPurchaseCost(Collection $items): Collection
    {
        return $items->map(fn ($item): array => [
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $item->transferred_quantity * $item->purchase_cost
            ),
        ]);
    }

    private function printDeliveryOrder(int $companyId, array $filterData, bool $displayPurchaseCost): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $location = $this->getLocation($filterData, $companyId);

        $html = '';

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DOCUMENT->value) {
            $interCompanyByDocumentReportForDeliveryOrderService = resolve(
                InterCompanyByDocumentReportForDeliveryOrderService::class
            );
            $html = $interCompanyByDocumentReportForDeliveryOrderService->renderPreparedByDocument(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_DETAILS->value) {
            $interCompanyByDetailsReportForDeliveryOrderService = resolve(
                InterCompanyByDetailsReportForDeliveryOrderService::class
            );
            $html = $interCompanyByDetailsReportForDeliveryOrderService->renderPreparedByDetails(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::SUMMARY_BY_ARTICLE->value) {
            $interCompanyBySummaryReportService = resolve(InterCompanyBySummaryReportForDeliveryOrderService::class);
            $html = $interCompanyBySummaryReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        if ((int) $filterData['report_by'] === InterCompanyTransferReportType::BY_SUMMARY_UPC->value) {
            $interCompanyBySummaryByUpcReportService = resolve(
                InterCompanyBySummaryByUpcReportForDeliveryOrderService::class
            );
            $html = $interCompanyBySummaryByUpcReportService->renderPreparedBySummary(
                $filterData,
                $company,
                $location,
                $displayPurchaseCost
            );
        }

        return $html;
    }
}
