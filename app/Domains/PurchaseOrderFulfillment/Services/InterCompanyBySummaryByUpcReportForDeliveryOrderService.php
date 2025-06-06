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
use App\Domains\PurchaseOrderFulfillment\Exports\InterCompanyTransferReportBySummaryByUpcForDeliveryOrderExport;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Models\Color;
use App\Models\Company;
use App\Models\Location;
use App\Models\Size;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyBySummaryByUpcReportForDeliveryOrderService
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

        [$interCompanyDeliveryOrderData, $columns, $dateRange] = $this->preparedBySummaryByUpc(
            $interCompanyTransferItems,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_summary_by_upc_for_delivery_order', [
            'interCompanyDeliveryOrdersData' => $interCompanyDeliveryOrderData,
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

    public function preparedBySummaryByUpc(Collection $interCompanyTransferItems, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyStockTransferTotal = collect();
        foreach ($interCompanyTransferItems as $interCompanyTransferItem) {
            $purchaseOrderFulfillment = $interCompanyTransferItem->purchaseOrderFulfillment;
            $quantity = $interCompanyTransferItem->transfer_quantity;

            $product = $interCompanyTransferItem->product;
            $color = $product->color;
            $size = $product->size;
            $interCompanyStockTransferTotal->push([
                'date' => Carbon::parse($purchaseOrderFulfillment->happened_at)->format('d-m-Y'),
                'upc' => $product->upc,
                'delivery_order_numbers' => $purchaseOrderFulfillment->delivery_order_number,
                'sales_order_number' => $purchaseOrderFulfillment->purchaseOrder->order_type === OrderTypes::SALES_ORDER->value ? $purchaseOrderFulfillment->purchaseOrder->order_number : $purchaseOrderFulfillment->purchaseOrder->external_order_number,
                'purchase_order_number' => $purchaseOrderFulfillment->purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value ? $purchaseOrderFulfillment->purchaseOrder->order_number : $purchaseOrderFulfillment->purchaseOrder->external_order_number,
                'invoice_number' => $purchaseOrderFulfillment->purchaseOrderInvoice?->invoice_number,
                'name' => $product->name,
                'status' => FulfillmentStatuses::getFormattedCaseName($purchaseOrderFulfillment->status),
                'color' => $color instanceof Color ? $color->name : 'N/A',
                'size' => $size instanceof Size ? $size->name : 'N/A',
                'external_location_name' => $interCompanyCustomReportService->getToLocation(
                    $purchaseOrderFulfillment->purchaseOrder
                ),
                'external_company_name' => $purchaseOrderFulfillment->purchaseOrder->externalCompany?->name,
                'quantity' => CommonFunctions::truncateDecimal((float) $quantity),
                'received_quantity' => CommonFunctions::truncateDecimal(
                    (float) $interCompanyTransferItem->received_quantity
                ),
                'purchase_cost' => CommonFunctions::currencyFormat(
                    (float) $interCompanyTransferItem->purchaseOrderItem->purchase_cost
                ),
                'total_purchase_cost' => CommonFunctions::currencyFormat(
                    (float) ($interCompanyTransferItem->purchaseOrderItem->purchase_cost * $interCompanyTransferItem->received_quantity)
                ),
            ]);
        }

        $interCompanyStockTransferTotal->push([
            'date' => 'Total',
            'upc' => '',
            'delivery_order_numbers' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'name' => '',
            'status' => '',
            'color' => '',
            'size' => '',
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
            'Delivery Order Numbers',
            'Sales Order Numbers',
            'Purchase Order Numbers',
            'Invoice Numbers',
            'External Company Name',
            'External Location Name',
            'Name',
            'Status',
            'Color',
            'Size',
            'Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
        ];

        return [$interCompanyStockTransferTotal->sortBy('date'), $columns, $dateRange];
    }

    public function exportStockTransferReportBySummaryByUpcExport(
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

        [$interCompanyDeliveryOrderData, $columns, $dateRange] = $this->preparedBySummaryByUpc(
            $interCompanyTransferItems,
            $filterData
        );

        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportBySummaryByUpcForDeliveryOrderExport(
                $interCompanyDeliveryOrderData,
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
