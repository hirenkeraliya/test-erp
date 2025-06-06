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
use App\Domains\PurchaseOrderFulfillment\Exports\InterCompanyTransferReportByDetailsForDeliveryOrderExport;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyByDetailsReportForDeliveryOrderService
{
    public function renderPreparedByDetails(
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

        $interCompanyDeliveryOrderData = $this->preparedByDetails($interCompanyDeliveryOrderDetails);

        $interCompanyDeliveryOrderData->push([
            'date' => 'Total',
            'delivery_order_number' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'status' => '',
            'external_company_name' => '',
            'external_location_name' => '',
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
            'Delivery Order Number',
            'Sales Order Number',
            'Purchase Order Number',
            'Invoice Number',
            'Status',
            'External Company Name',
            'External Location Name	',
            'Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
            'Remark',
        ];

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.inter_company_stock_transfer_by_details_for_delivery_order', [
            'interCompanyDeliveryOrdersData' => $interCompanyDeliveryOrderData,
            'location' => $location,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'displayPurchaseCost' => $displayPurchaseCost,
            'filterBy' => $interCompanyCustomReportService->filterBy($filterData, $company->id),
            'transferType' => $interCompanyCustomReportService->transferType($filterData),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function preparedByDetails(Collection $interCompanyStockTransfers): Collection
    {
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyStockTransfers->map(fn ($interCompanyStockTransfer): array => [
            'date' => Carbon::parse($interCompanyStockTransfer->happened_at)->format('d-m-Y'),
            'delivery_order_number' => $interCompanyStockTransfer->delivery_order_number,
            'sales_order_number' => $interCompanyStockTransfer->purchaseOrder->order_type === OrderTypes::SALES_ORDER->value ? $interCompanyStockTransfer->purchaseOrder->order_number : $interCompanyStockTransfer->purchaseOrder->external_order_number,
            'purchase_order_number' => $interCompanyStockTransfer->purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value ? $interCompanyStockTransfer->purchaseOrder->order_number : $interCompanyStockTransfer->purchaseOrder->external_order_number,
            'invoice_number' => $interCompanyStockTransfer->purchaseOrderInvoice?->invoice_number,
            'status' => FulfillmentStatuses::getFormattedCaseName($interCompanyStockTransfer->status),
            'external_company_name' => $interCompanyStockTransfer->purchaseOrder->externalCompany?->name,
            'external_location_name' => $interCompanyCustomReportService->getToLocation(
                $interCompanyStockTransfer->purchaseOrder
            ),

            'transfer_from_and_to' => $this->preparedProductRecords($interCompanyStockTransfer->items),
            'quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransfer->items->sum('transfer_quantity')
            ),
            'received_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransfer->items->sum('received_quantity')
            ),
            'purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransfer->items->sum(
                    'purchaseOrderItem.purchase_cost'
                ) / $interCompanyStockTransfer->items->count()
            ),
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransfer->items->sum(
                    fn ($item): int|float => $item->purchaseOrderItem->purchase_cost * $item->received_quantity
                )
            ),
            'remark' => $interCompanyStockTransfer->notes,
        ]);
    }

    public function exportStockTransferReportByDetailsExport(
        int $companyId,
        array $filterData,
        string $filename,
        Location $location,
        bool $displayPurchaseCost,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyDeliveryOrderDetails = $purchaseOrderFulfillmentQueries->getByDeliveryOrderForCustomReport(
            $filterData,
            $company->id
        );

        $interCompanyDeliveryOrderData = $this->preparedByDetails($interCompanyDeliveryOrderDetails);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportByDetailsForDeliveryOrderExport(
                $interCompanyDeliveryOrderData,
                $location,
                $dateRange,
                $company,
                $displayPurchaseCost,
                $filterBy,
                $transferType,
                $currency->getSymbol()
            ),
            $filename
        );
    }

    private function preparedProductRecords(Collection $interCompanyStockTransferItems): array
    {
        return $interCompanyStockTransferItems->groupBy('product.article_number')->map(fn ($items): array => [
            'name' => $items->first()->product->name,
            'article_number' => $items->first()->product->article_number ?? 'N/A',
            'total_quantity' => CommonFunctions::truncateDecimal($items->sum('transfer_quantity')),
            'color_wise_products' => $this->preparedProductByColorRecords($items),
        ])->toArray();
    }

    private function preparedProductByColorRecords(Collection $items): array
    {
        return $items->map(function ($item): array {
            /** @var Product $product */
            $product = $item->product;

            return [
                'upc' => $product->upc,
                'color' => $product->color?->name ?? 'N/A',
                'size' => $product->size?->name ?? 'N/A',
                'transfer_quantity' => CommonFunctions::truncateDecimal((float) $item->transfer_quantity),
                'received_quantity' => CommonFunctions::truncateDecimal((float) $item->received_quantity),
                'purchase_cost' => CommonFunctions::currencyFormat((float) $item->purchaseOrderItem->purchase_cost),
                'total_purchase_cost' => CommonFunctions::currencyFormat(
                    (float) $item->purchaseOrderItem->purchase_cost * $item->received_quantity
                ),
            ];
        })->toArray();
    }
}
