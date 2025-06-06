<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\Exports\PurchaseOrderInvoiceReportBySummaryExport;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyInvoiceCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $orders = $purchaseOrderInvoiceQueries->getPurchaseOrderInvoicesForReport($filterData, $company->id);

        [$purchaseOrderInvoicesData, $columns] = $this->preparedBySummary($orders);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return view('prints.purchase_order_invoice_by_summary', [
            'purchaseOrderInvoicesData' => $purchaseOrderInvoicesData,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $interCompanyCustomReportService->filterBy($filterData, $company->id),
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $orders = $purchaseOrderInvoiceQueries->getPurchaseOrderInvoicesForReport($filterData, $company->id);

        [$purchaseOrderInvoicesData, $columns] = $this->preparedBySummary($orders);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);

        return Excel::download(
            new PurchaseOrderInvoiceReportBySummaryExport(
                $purchaseOrderInvoicesData,
                $dateRange,
                $company,
                $columns,
                $filterBy
            ),
            $filename
        );
    }

    public function preparedBySummary(Collection $purchaseOrderInvoices): array
    {
        $purchaseOrderInvoiceData = collect();
        foreach ($purchaseOrderInvoices as $purchaseOrderInvoice) {
            $purchaseOrderInvoiceData->push([
                'date' => $purchaseOrderInvoice->created_at,
                'invoice_number' => $purchaseOrderInvoice->invoice_number,
                'status' => InvoiceStatuses::getFormattedCaseName($purchaseOrderInvoice->status),
                'quantity' => $this->getTotalQuantity($purchaseOrderInvoice->fulfillments),
                'total_amount' => $this->getTotalAmount($purchaseOrderInvoice->fulfillments),
            ]);
        }

        $purchaseOrderInvoiceData->push([
            'date' => 'Total',
            'invoice_number' => null,
            'status' => null,
            'quantity' => $purchaseOrderInvoiceData->sum('quantity'),
            'total_amount' => $purchaseOrderInvoiceData->sum('total_amount'),
        ]);

        $columns = ['Date', 'Invoice Number', 'Status', 'Quantity', 'Amount'];

        return [$purchaseOrderInvoiceData, $columns];
    }

    private function getTotalQuantity(Collection $purchaseOrderFulfillments): float
    {
        $quantity = 0;

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            $quantity += $purchaseOrderFulfillment->items->sum('received_quantity');
        }

        return $quantity;
    }

    private function getTotalAmount(Collection $purchaseOrderFulfillments): float
    {
        $totalAmount = 0;

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
                $totalAmount += $purchaseOrderFulfillmentItem->received_quantity * $purchaseOrderFulfillmentItem->purchaseOrderItem->purchase_cost;
            }
        }

        return $totalAmount;
    }
}
