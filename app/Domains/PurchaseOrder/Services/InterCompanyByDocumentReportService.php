<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Exports\InterCompanyTransferReportByDocumentExport;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyByDocumentReportService
{
    public function renderPreparedByDocument(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyStockTransfers = $purchaseOrderQueries->getByDateAndLocationWithStockTransfer(
            $filterData,
            $company->id
        );

        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $interCompanyStockTransfers,
            $filterData
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.inter_company_stock_transfer_by_document', [
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

    public function preparedByDocument(Collection $interCompanyStockTransfers, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyStockTransferData = $interCompanyStockTransfers->map(
            fn ($interCompanyStockTransfer): array => [
                'date' => $interCompanyStockTransfer->created_at->format('d-m-Y'),
                'delivery_order_numbers' => $interCompanyStockTransfer->fulfillments->pluck(
                    'delivery_order_number'
                )->filter()->unique()->implode(', '),
                'sales_order_number' => ($interCompanyStockTransfer->order_type === OrderTypes::PURCHASE_ORDER->value) ? $interCompanyStockTransfer->external_order_number : $interCompanyStockTransfer->order_number,
                'purchase_order_number' => ($interCompanyStockTransfer->order_type === OrderTypes::PURCHASE_ORDER->value) ? $interCompanyStockTransfer->order_number : $interCompanyStockTransfer->external_order_number,
                'invoice_number' => $interCompanyStockTransfer->fulfillments->pluck(
                    'purchaseOrderInvoice.invoice_number'
                )->filter()->unique()->implode(', '),
                'reference_number' => $interCompanyStockTransfer->reference_number,
                'transfer_type' => OrderTypes::getFormattedCaseName($interCompanyStockTransfer->order_type),
                'status' => Statuses::getFormattedCaseName($interCompanyStockTransfer->status),
                'external_location_name' => $interCompanyCustomReportService->getToLocation($interCompanyStockTransfer),
                'external_company_name' => $interCompanyStockTransfer->externalCompany?->name,
                'quantity' => CommonFunctions::truncateDecimal(
                    $interCompanyStockTransfer->items->sum(fn ($item) => $item->quantity)
                ),
                'transferred_quantity' => CommonFunctions::truncateDecimal(
                    $interCompanyStockTransfer->items->sum(fn ($item) => $item->transferred_quantity)
                ),
                'purchase_cost' => CommonFunctions::currencyFormat(
                    $interCompanyStockTransfer->items->sum('purchase_cost') / $interCompanyStockTransfer->items->count()
                ),
                'total_purchase_cost' => CommonFunctions::currencyFormat(
                    $interCompanyStockTransfer->items->sum(
                        fn ($item): int|float => $item->purchase_cost * $item->transferred_quantity
                    )
                ),
                'remark' => $interCompanyStockTransfer->remarks,
            ]
        );

        $interCompanyStockTransferData->push([
            'date' => 'Total',
            'delivery_order_numbers' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'reference_number' => '',
            'transfer_type' => '',
            'status' => '',
            'external_location_name' => '',
            'external_company_name' => '',
            'quantity' => CommonFunctions::truncateDecimal($interCompanyStockTransferData->sum('quantity')),
            'transferred_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransferData->sum('transferred_quantity')
            ),
            'purchase_cost' => '',
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransferData->sum('total_purchase_cost')
            ),
            'remark' => '',
        ]);

        $columns = [
            'Date',
            'Delivery Order Number',
            'Sales Order Number',
            'Purchase Order Number',
            'Invoice Number',
            'Reference Number',
            'Transfer Type',
            'Status',
            'External Location Name',
            'External Company Name',
            'Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
            'Remark',
        ];

        return [$interCompanyStockTransferData, $columns, $dateRange];
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

        $interCompanyStockTransferQueries = resolve(PurchaseOrderQueries::class);

        $interCompanyStockTransfers = $interCompanyStockTransferQueries->getByDateAndLocationWithStockTransfer(
            $filterData,
            $company->id
        );
        [$interCompanyStockTransferData, $columns, $dateRange] = $this->preparedByDocument(
            $interCompanyStockTransfers,
            $filterData
        );
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportByDocumentExport(
                $interCompanyStockTransferData,
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
