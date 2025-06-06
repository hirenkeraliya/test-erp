<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Exports\InterCompanyTransferReportBySummaryExport;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyBySummaryReportService
{
    public function renderPreparedBySummary(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyTransferItems = $purchaseOrderItemQueries->getByDateAndLocationWithProduct(
            $filterData,
            $company->id
        );

        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $interCompanyTransferItems,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_summary', [
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

    public function preparedByDocument(Collection $interCompanyTransferItems, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        $articleNumberWiseStockTransferItems = $interCompanyTransferItems->groupBy($groupBy)->sortBy($groupBy);

        $interCompanyStockTransferTotal = collect();

        foreach ($articleNumberWiseStockTransferItems as $purchaseOrderItems) {
            foreach ($purchaseOrderItems as $purchaseOrderItem) {
                $purchaseOrder = $purchaseOrderItem->purchaseOrder;

                $deliveryOrderNumbers = $purchaseOrderItem->purchaseOrderFulFillmentsItems->pluck(
                    'purchaseOrderFulfillment.delivery_order_number'
                )->filter()->unique()->implode(', ');

                $invoiceNumbers = $purchaseOrderItem->purchaseOrderFulFillmentsItems->pluck(
                    'purchaseOrderFulfillment.purchaseOrderInvoice.invoice_number'
                )->filter()->unique()->implode(', ');

                $product = $purchaseOrderItem->product;

                $interCompanyStockTransferTotal->push([
                    'date' => $purchaseOrder->created_at->format('d-m-Y'),
                    'delivery_order_numbers' => $deliveryOrderNumbers,
                    'sales_order_number' => ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) ? $purchaseOrder->external_order_number : $purchaseOrder->order_number,
                    'purchase_order_number' => ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) ? $purchaseOrder->order_number : $purchaseOrder->external_order_number,
                    'invoice_number' => $invoiceNumbers,
                    'article_number' => config(
                        'app.product_variant'
                    ) ? $product->masterProduct?->article_number : $product->article_number,
                    'name' => $product->name,
                    'status' => Statuses::getFormattedCaseName($purchaseOrder->status),
                    'quantity' => CommonFunctions::truncateDecimal((float) $purchaseOrderItem->quantity),
                    'transferred_quantity' => CommonFunctions::truncateDecimal(
                        (float) $purchaseOrderItem->transferred_quantity
                    ),
                    'purchase_cost' => CommonFunctions::currencyFormat((float) $purchaseOrderItem->purchase_cost),
                    'total_purchase_cost' => CommonFunctions::currencyFormat(
                        $purchaseOrderItem->transferred_quantity * (float) $purchaseOrderItem->purchase_cost
                    ),
                    'external_location_name' => $interCompanyCustomReportService->getToLocation($purchaseOrder),
                    'external_company_name' => $purchaseOrder->externalCompany?->name,
                ]);
            }
        }

        $interCompanyStockTransferTotal->push([
            'date' => 'Total',
            'article_number' => '',
            'delivery_order_numbers' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'name' => '',
            'status' => '',
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
            'Article Number',
            'Delivery Order Numbers',
            'Sales Order Number',
            'Purchase Order Number',
            'Invoice Number',
            'External Company Name',
            'External Location Name',
            'Name',
            'Status',
            'Quantity',
            'Transferred Quantity',
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

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $purchaseOrderItems = $purchaseOrderItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);
        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $purchaseOrderItems,
            $filterData
        );
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new InterCompanyTransferReportBySummaryExport(
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
