<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Exports\OrderReportByDetailsExport;
use App\Domains\Order\OrderQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdersByDetailsReportService
{
    public function renderPreparedByDetails(Company $company, Location $location, array $filterData): string
    {
        $orderQueries = resolve(OrderQueries::class);
        $orders = $orderQueries->getOrderDetailsForReport($filterData, $company->id);

        $orderData = $this->preparedByDetails($orders);

        $orderData->push([
            'date' => 'Total',
            'bill_reference_number' => '',
            'receipt_number' => '',
            'type' => '',
            'quantity' => CommonFunctions::truncateDecimal($orderData->sum('quantity')),
            'total_price' => CommonFunctions::currencyFormat($orderData->sum('total_price')),
        ]);

        $columns = ['Date', '# Reference', 'Receipt Number', 'Type', 'Quantity', 'Price'];

        $customReportService = resolve(CustomReportService::class);

        return view('prints.order_by_details', [
            'ordersData' => $orderData,
            'location' => $location,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
        ])->render();
    }

    public function preparedByDetails(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order): array => [
            'date' => $order->getCreatedAt()?->format('d-m-Y'),
            'bill_reference_number' => $order->getBillReferenceNumber(),
            'receipt_number' => $order->getReceiptNumber(),
            'type' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
            'description' => $this->preparedProductRecords($order->getOrderItems()),
            'quantity' => CommonFunctions::truncateDecimal($order->getOrderItems()->sum('quantity')),
            'total_price' => $order->getOrderItems()->sum('total_price_paid'),
        ]);
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

        $orderData = $this->preparedByDetails($orders);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        return Excel::download(
            new OrderReportByDetailsExport($orderData, $location, $dateRange, $company),
            $filename
        );
    }

    private function preparedProductRecords(Collection $orderItems): array
    {
        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        return $orderItems->groupBy($groupBy)->map(fn ($items): array => [
            'name' => $items->first()->product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $items->first()->product?->masterProduct?->article_number : $items->first()->product->article_number,
            'total_quantity' => CommonFunctions::truncateDecimal($items->sum('quantity')),
            'color_wise_products' => $this->preparedProductByColorRecords($items),
        ])->toArray();
    }

    private function preparedProductByColorRecords(Collection $items): array
    {
        $productService = resolve(ProductService::class);

        return $items->map(function (OrderItem $item) use ($productService): array {
            /** @var Product $product */
            $product = $item->product;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            return [
                'upc' => $item->getProduct()?->upc,
                ...$colorSizeOrAttributeData,
                'quantity' => CommonFunctions::truncateDecimal($item->getQuantity()),
            ];
        })->toArray();
    }
}
