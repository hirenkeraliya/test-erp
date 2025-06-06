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
use App\Domains\PurchaseOrderFulfillment\Exports\InterCompanyTransferReportByDocumentForDeliveryOrderExport;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyByDocumentReportForDeliveryOrderService
{
    public function renderPreparedByDocument(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyDeliveryOrderDetails = $purchaseOrderFulfillmentQueries->getByDeliveryOrderForCustomReport(
            $filterData,
            $company->id
        );

        [$interCompanyDeliveryOrderData, $columns, $dateRange] = $this->preparedByDocument(
            $interCompanyDeliveryOrderDetails,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_document_for_delivery_order', [
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

    public function preparedByDocument(Collection $interCompanyDeliveryOrderDetails, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyDeliveryOrderData = $interCompanyDeliveryOrderDetails->map(
            fn ($interCompanyDeliveryOrderDetail): array => [
                'date' => Carbon::parse($interCompanyDeliveryOrderDetail->happened_at)->format('d-m-Y'),
                'delivery_order_number' => $interCompanyDeliveryOrderDetail->delivery_order_number,
                'sales_order_number' => $interCompanyDeliveryOrderDetail->purchaseOrder->order_type === OrderTypes::SALES_ORDER->value ? $interCompanyDeliveryOrderDetail->purchaseOrder->order_number : $interCompanyDeliveryOrderDetail->purchaseOrder->external_order_number,
                'purchase_order_number' => $interCompanyDeliveryOrderDetail->purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value ? $interCompanyDeliveryOrderDetail->purchaseOrder->order_number : $interCompanyDeliveryOrderDetail->purchaseOrder->external_order_number,
                'invoice_number' => $interCompanyDeliveryOrderDetail->purchaseOrderInvoice?->invoice_number,
                'status' => FulfillmentStatuses::getFormattedCaseName($interCompanyDeliveryOrderDetail->status),
                'external_location_name' => $interCompanyCustomReportService->getToLocation(
                    $interCompanyDeliveryOrderDetail->purchaseOrder
                ),
                'external_company_name' => $interCompanyDeliveryOrderDetail->purchaseOrder->externalCompany?->name,
                'quantity' => CommonFunctions::truncateDecimal(
                    $interCompanyDeliveryOrderDetail->items->sum(
                        fn ($item) => $item->transfer_quantity ?: $item->transfer_quantity
                    )
                ),
                'received_quantity' => CommonFunctions::truncateDecimal(
                    $interCompanyDeliveryOrderDetail->items->sum(
                        fn ($item) => $item->received_quantity ?: $item->received_quantity
                    )
                ),
                'purchase_cost' => CommonFunctions::currencyFormat(
                    $interCompanyDeliveryOrderDetail->items->sum(
                        'purchaseOrderItem.purchase_cost'
                    ) / $interCompanyDeliveryOrderDetail->items->count()
                ),
                'total_purchase_cost' => CommonFunctions::currencyFormat(
                    $interCompanyDeliveryOrderDetail->items->sum(
                        fn ($item): int|float => $item->purchaseOrderItem->purchase_cost * $item->received_quantity
                    )
                ),
                'remark' => $interCompanyDeliveryOrderDetail->notes,
            ]
        );

        $interCompanyDeliveryOrderData->push([
            'date' => 'Total',
            'delivery_order_number' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'status' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'quantity' => CommonFunctions::truncateDecimal($interCompanyDeliveryOrderData->sum('quantity')),
            'received_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyDeliveryOrderData->sum('received_quantity')
            ),
            'purchase_cost' => '',
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyDeliveryOrderData->sum('total_purchase_cost')
            ),
            'remark' => '',
        ]);

        $columns = [
            'Date',
            'Delivery Order No.',
            'Sales Order No.',
            'Purchase Order No.',
            'Invoice No.',
            'Status',
            'External Location Name',
            'External Company Name',
            'Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
            'Remark',
        ];

        return [$interCompanyDeliveryOrderData, $columns, $dateRange];
    }

    public function exportInterCompanyReportByDocumentExport(
        int $companyId,
        array $filterData,
        string $filename,
        Location $location,
        bool $displayPurchaseCost
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        $interCompanyDeliveryOrderDetails = $purchaseOrderFulfillmentQueries->getByDeliveryOrderForCustomReport(
            $filterData,
            $company->id
        );

        [$interCompanyDeliveryOrderData, $columns, $dateRange] = $this->preparedByDocument(
            $interCompanyDeliveryOrderDetails,
            $filterData
        );
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportByDocumentForDeliveryOrderExport(
                $interCompanyDeliveryOrderData,
                $location,
                $dateRange,
                $company,
                $columns,
                $displayPurchaseCost,
                $filterBy,
                $transferType,
                $currency->getSymbol(),
            ),
            $filename
        );
    }
}
