<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Exports\OrderReportBySummaryExport;
use App\Domains\OrderItem\OrderItemQueries;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdersBySummaryReportService
{
    public function renderPreparedBySummary(Company $company, Location $location, array $filterData): string
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orders = $orderItemQueries->getOrderItemsForTheReport($filterData, $company->id);

        [$orderData, $columns, $dateRange] = $this->preparedBySummary($orders, $filterData);

        return view('prints.order_by_summary', [
            'ordersData' => $orderData,
            'location' => $location,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
        ])->render();
    }

    public function preparedBySummary(Collection $orders, array $filterData): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        $articleNumberWiseStockTransferData = $orders->groupBy($groupBy)->sortBy($groupBy);

        $articleNumberWiseStockTransferData = $articleNumberWiseStockTransferData->sortBy(
            fn ($item, $key): int|string => $key
        );

        $orderTotal = collect();

        foreach ($articleNumberWiseStockTransferData as $orderItems) {
            $orderItem = $orderItems->first();

            $order = $orderItem->getOrder();

            $product = $orderItem->getProduct();

            $quantity = $orderItems->sum('quantity');

            $orderTotal->push([
                'date' => $order->getCreatedAt()?->format('d-m-Y'),
                'upc' => $product->upc,
                'article_number' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->article_number : $product->article_number,
                'name' => $product->name,
                'type' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
                'quantity' => CommonFunctions::truncateDecimal((float) $quantity),
                'total_price' => $orderItems->sum('total_price_paid'),
            ]);
        }

        $orderTotal->push([
            'date' => 'Total',
            'upc' => '',
            'article_number' => '',
            'name' => '',
            'type' => '',
            'quantity' => CommonFunctions::truncateDecimal($orderTotal->sum('quantity')),
            'total_price' => CommonFunctions::currencyFormat($orderTotal->sum('total_price')),
        ]);

        $columns = ['Date', 'UPC', 'Article Number', 'Name', 'Type', 'Quantity', 'Price'];

        return [$orderTotal->sortBy('date'), $columns, $dateRange];
    }

    public function export(
        Location $location,
        array $filterData,
        int $companyId,
        string $filename,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $orderQueries = resolve(OrderItemQueries::class);

        $orderItems = $orderQueries->getOrderItemsForTheReport($filterData, $company->id);
        [$order, $columns, $dateRange] = $this->preparedBySummary($orderItems, $filterData);

        return Excel::download(
            new OrderReportBySummaryExport($order, $location, $dateRange, $company, $columns),
            $filename
        );
    }
}
