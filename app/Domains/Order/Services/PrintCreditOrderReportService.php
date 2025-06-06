<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrintCreditOrderReportService
{
    public function print(int $orderId): string
    {
        $orderQueries = resolve(OrderQueries::class);
        $productService = resolve(ProductService::class);

        $layawayOrderDetails = $orderQueries->getLayawayOrderItemsByForPrint($orderId);

        $orderDetails = $this->preparedData($layawayOrderDetails, $productService);
        [$company, $location] = $this->preparedCompanyAndLocation($layawayOrderDetails);

        return view('prints.credit_order_report', [
            'ordersDetails' => $orderDetails,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'location' => $location,
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Order $order, ProductService $productService): array
    {
        /** @var Collection $orderItems */
        $orderItems = $order->getOrderItems();

        /** @var Collection $orderPayments */
        $orderPayments = $order->getPayments();

        /** @var ?Member $member */
        $member = $order->getMember();

        $userDataPrepare = resolve(UserDataPreparer::class);

        return [
            'member_details' => $userDataPrepare->getMemberNameAndAddressDetails($member),
            'gross_sales' => CommonFunctions::numberFormat($order->getGrossTotal()),
            'total_discount_amount' => $order->getTotalDiscountAmount(),
            'total_tax_amount' => $order->getTotalTaxAmount(),
            'total_amount_paid' => $order->getTotalAmountPaid(),
            'credit_pending_amount' => $order->getCreditPendingAmount(),
            'receipt_number' => $order->getReceiptNumber(),
            'order_items' => $this->getPreparedOrderItems($orderItems, $productService),
            'payments' => OrderPayment::getPreparedPayments($orderPayments),
        ];
    }

    private function preparedCompanyAndLocation(Order $order): array
    {
        $locationQueries = resolve(LocationQueries::class);

        /** @var Location $location */
        $location = $locationQueries->getNameAndCodeWithCompanyById((string) $order->getLocationId());

        /** @var Company $company */
        $company = $location->company;

        return [$company, $location];
    }

    private function getPreparedOrderItems(Collection $orderItems, ProductService $productService): array
    {
        return $orderItems->map(function ($orderItem) use ($productService): array {
            /** @var Product $product */
            $product = $orderItem->product;

            [$color, $size] = $productService->getColorAndSize($product);

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'color' => $color,
                'size' => $size,
                'upc' => $product->getUpc(),
                'quantity' => $orderItem->getQuantity(),
                'unit_price' => $orderItem->getPricePaidPerUnit(),
                'subtotal' => CommonFunctions::numberFormat($orderItem->getSubTotal()),
                'total_discount_amount' => $orderItem->getTotalDiscountAmount(),
                'total_tax_amount' => $orderItem->getTotalTaxAmount(),
                'total_price_paid' => $orderItem->getTotalPricePaid(),
                'promoters' => Promoter::getOrderPromoters($orderItem),
            ];
        })->toArray();
    }
}
