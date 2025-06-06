<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\Exports\InterCompanyTransferReportBySummaryForDeliveryOrderExport;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyBySummaryReportForDeliveryOrderService
{
    public function renderPreparedBySummary(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyTransferItems = $purchaseOrderFulfillmentItemQueries->getByDateAndLocationWithProduct(
            $filterData,
            $company->id
        );

        [$interCompanyDeliveryData, $columns, $dateRange] = $this->preparedBySummary(
            $interCompanyTransferItems,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_summary_for_delivery_order', [
            'interCompanyDeliveryOrdersData' => $interCompanyDeliveryData,
            'location' => $location,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'displayPurchaseCost' => $displayPurchaseCost,
            'filterBy' => $interCompanyCustomReportService->filterBy($filterData, $company->id),
            'transferType' => $interCompanyCustomReportService->transferType($filterData),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function preparedBySummary(Collection $interCompanyTransferItems, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $articleNumberWiseStockTransferData = $interCompanyTransferItems->groupBy('product.article_number')->sortBy(
            'product.article_number'
        );

        $interCompanyStockTransferTotal = collect();

        foreach ($articleNumberWiseStockTransferData as $purchaseOrderFulfillmentItems) {
            foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
                $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

                $product = $purchaseOrderFulfillmentItem->product;

                $interCompanyStockTransferTotal->push([
                    'date' => Carbon::parse($purchaseOrderFulfillment->happened_at)->format('d-m-Y'),
                    'upc' => $product->upc,
                    'article_number' => $product->article_number,
                    'delivery_order_number' => $purchaseOrderFulfillment->delivery_order_number,
                    'sales_order_number' => $purchaseOrderFulfillment->purchaseOrder->order_type === OrderTypes::SALES_ORDER->value ? $purchaseOrderFulfillment->purchaseOrder->order_number : $purchaseOrderFulfillment->purchaseOrder->external_order_number,
                    'purchase_order_number' => $purchaseOrderFulfillment->purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value ? $purchaseOrderFulfillment->purchaseOrder->order_number : $purchaseOrderFulfillment->purchaseOrder->external_order_number,
                    'invoice_number' => $purchaseOrderFulfillment->purchaseOrderInvoice?->invoice_number,
                    'name' => $product->name,
                    'status' => FulfillmentStatuses::getFormattedCaseName($purchaseOrderFulfillment->status),
                    'external_location_name' => $interCompanyCustomReportService->getToLocation(
                        $purchaseOrderFulfillment->purchaseOrder
                    ),
                    'quantity' => CommonFunctions::truncateDecimal(
                        (float) $purchaseOrderFulfillmentItem->transfer_quantity
                    ),
                    'external_company_name' => $purchaseOrderFulfillment->purchaseOrder->externalCompany?->name,
                    'received_quantity' => CommonFunctions::truncateDecimal(
                        (float) $purchaseOrderFulfillmentItem->received_quantity
                    ),
                    'purchase_cost' => CommonFunctions::currencyFormat(
                        (float) $purchaseOrderFulfillmentItem->purchaseOrderItem->purchase_cost
                    ),
                    'total_purchase_cost' => CommonFunctions::currencyFormat(
                        (float) ($purchaseOrderFulfillmentItem->purchaseOrderItem->purchase_cost * $purchaseOrderFulfillmentItem->received_quantity)
                    ),
                ]);
            }
        }

        $interCompanyStockTransferTotal->push([
            'date' => 'Total',
            'upc' => '',
            'article_number' => '',
            'delivery_order_number' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'name' => '',
            'status' => '',
            'quantity' => CommonFunctions::truncateDecimal($interCompanyStockTransferTotal->sum('quantity')),
            'received_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransferTotal->sum('received_quantity')
            ),
            'purchase_cost' => '',
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransferTotal->sum('total_purchase_cost')
            ),
        ]);

        $columns = [
            'Date',
            'UPC',
            'Article Number',
            'Delivery Order Number',
            'Sales Order Numbers',
            'Purchase Order Numbers',
            'Invoice Numbers',
            'External Company Name',
            'External Location Name',
            'Name',
            'Status',
            'Transfer Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
        ];

        return [$interCompanyStockTransferTotal->sortBy('date'), $columns, $dateRange];
    }

    public function exportStockTransferReportBySummaryExport(
        int $companyId,
        array $filterData,
        string $filename,
        Location $location,
        bool $displayPurchaseCost
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyTransferItems = $purchaseOrderFulfillmentItemQueries->getByDateAndLocationWithProduct(
            $filterData,
            $company->id
        );

        [$interCompanyDeliveryData, $columns, $dateRange] = $this->preparedBySummary(
            $interCompanyTransferItems,
            $filterData
        );

        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportBySummaryForDeliveryOrderExport(
                $interCompanyDeliveryData,
                $location,
                $dateRange,
                $company,
                $columns,
                $displayPurchaseCost,
                $filterBy,
                $transferType,
                $currency->getSymbol()
            ),
            $filename
        );
    }
}
