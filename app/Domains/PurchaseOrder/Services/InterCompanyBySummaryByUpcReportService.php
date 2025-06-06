<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Exports\InterCompanyTransferReportBySummaryByUpcExport;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyBySummaryByUpcReportService
{
    public function renderPreparedBySummary(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $purchaseOrderItems = $purchaseOrderItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);

        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedBySummaryUpc(
            $purchaseOrderItems,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_summary_by_upc', [
            'interCompanyStockTransfersData' => $interCompanyStockTransferData,
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

    public function preparedBySummaryUpc(Collection $interCompanyTransferItems, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);
        $productService = resolve(ProductService::class);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyStockTransferTotal = collect();
        foreach ($interCompanyTransferItems as $interCompanyTransferItem) {
            $purchaseOrder = $interCompanyTransferItem->purchaseOrder;

            $deliveryOrderNumbers = $interCompanyTransferItem->purchaseOrderFulFillmentsItems->pluck(
                'purchaseOrderFulfillment.delivery_order_number'
            )->filter()->unique()->implode(', ');

            $invoiceNumbers = $interCompanyTransferItem->purchaseOrderFulFillmentsItems->pluck(
                'purchaseOrderFulfillment.purchaseOrderInvoice.invoice_number'
            )->filter()->unique()->implode(', ');

            $product = $interCompanyTransferItem->product;
            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $interCompanyStockTransferTotal->push([
                'date' => $purchaseOrder->created_at->format('d-m-Y'),
                'delivery_order_numbers' => $deliveryOrderNumbers,
                'sales_order_number' => ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) ? $purchaseOrder->external_order_number : $purchaseOrder->order_number,
                'purchase_order_number' => ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) ? $purchaseOrder->order_number : $purchaseOrder->external_order_number,
                'invoice_number' => $invoiceNumbers,
                'upc' => $product->upc,
                'name' => $product->name,
                'status' => Statuses::getFormattedCaseName($purchaseOrder->status),
                ...$colorSizeOrAttributeData,
                'quantity' => CommonFunctions::truncateDecimal((float) $interCompanyTransferItem->quantity),
                'transferred_quantity' => CommonFunctions::truncateDecimal(
                    (float) $interCompanyTransferItem->transferred_quantity
                ),
                'purchase_cost' => CommonFunctions::currencyFormat(
                    (float) $interCompanyTransferItem->purchase_cost
                ),
                'total_purchase_cost' => CommonFunctions::currencyFormat(
                    $interCompanyTransferItem->transferred_quantity * (float) $interCompanyTransferItem->purchase_cost
                ),
                'external_location_name' => $interCompanyCustomReportService->getToLocation($purchaseOrder),
                'external_company_name' => $purchaseOrder->externalCompany?->name,
            ]);
        }

        $interCompanyStockTransferTotal->push([
            'date' => 'Total',
            'delivery_order_numbers' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'upc' => '',
            'name' => '',
            'status' => '',
            ...config('app.product_variant') ? [
                'attributes' => '',
            ] : [
                'color' => '',
                'size' => '',
            ],
            'quantity' => CommonFunctions::truncateDecimal($interCompanyStockTransferTotal->sum('quantity')),
            'transferred_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransferTotal->sum('transferred_quantity')
            ),
            'purchase_cost' => '',
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransferTotal->sum('total_purchase_cost')
            ),
        ]);

        $columns = [
            'Date',
            'Delivery Order Numbers',
            'Sales Order Numbers',
            'Purchase Order Numbers',
            'Invoice Number Numbers',
            'External Company Name',
            'External Location Name',
            'UPC',
            'Name',
            'Status',
            ...config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'],
            'Quantity',
            'Transferred Quantity',
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
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderItems = $purchaseOrderItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);
        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedBySummaryUpc(
            $purchaseOrderItems,
            $filterData
        );
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new InterCompanyTransferReportBySummaryByUpcExport(
                $interCompanyStockTransferData,
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
