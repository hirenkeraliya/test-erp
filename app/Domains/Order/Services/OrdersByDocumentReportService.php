<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Exports\OrdersReportByDocumentExport;
use App\Domains\Order\OrderQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdersByDocumentReportService
{
    public function renderPreparedByDocument(array $filterData, Company $company, Location $location): string
    {
        $orderQueries = resolve(OrderQueries::class);
        $orders = $orderQueries->getOrderDetailsForReport($filterData, $company->id);

        [$orderData, $columns, $dateRange] = $this->preparedByDocument($orders, $filterData);

        return view('prints.order_by_document', [
            'ordersData' => $orderData,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'location' => $location,
            'columns' => $columns,
        ])->render();
    }

    public function preparedByDocument(Collection $orders, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $orderData = $orders->map(fn (Order $order): array => [
            'date' => $order->getCreatedAt()?->format('d-m-Y'),
            'receipt_number' => $order->getReceiptNumber(),
            'member' => $order->getMember() instanceof Member ? $order->getMember()->getFullName() : 'Walk In Member',
            'bill_reference_number' => $order->getBillReferenceNumber(),
            'type' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
            'quantity' => CommonFunctions::truncateDecimal((float) $order->getOrderItems()->sum('quantity')),
            'total_price' => $order->getOrderItems()->sum('total_price_paid'),
        ]);

        $orderData->push([
            'date' => 'Total',
            'receipt_number' => '',
            'member' => '',
            'bill_reference_number' => '',
            'type' => '',
            'quantity' => CommonFunctions::truncateDecimal($orderData->sum('quantity')),
            'total_price' => CommonFunctions::currencyFormat($orderData->sum('total_price')),
        ]);

        $columns = ['Date', 'Receipt Number', 'Member', '# Reference', 'Type', 'Quantity', 'Price'];

        return [$orderData, $columns, $dateRange];
    }

    public function export(
        Location $location,
        array $filterData,
        int $companyId,
        string $filename,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $orderQueries = resolve(OrderQueries::class);
        $orders = $orderQueries->getOrderDetailsForReport($filterData, $company->id);

        [$orderData, $columns, $dateRange] = $this->preparedByDocument($orders, $filterData);

        return Excel::download(
            new OrdersReportByDocumentExport($orderData, $location, $dateRange, $company, $columns),
            $filename
        );
    }
}
