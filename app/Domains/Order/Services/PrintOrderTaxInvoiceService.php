<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\OrderQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrintOrderTaxInvoiceService
{
    public function print(int $orderId): string
    {
        $orderQueries = resolve(OrderQueries::class);
        $productService = resolve(ProductService::class);

        $order = $orderQueries->getOrderDetailsForReceipt($orderId);

        $orderDetails = $this->preparedData($order, $productService);
        [$company, $location] = $this->preparedCompanyAndLocation($order);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.order_tax_invoice', [
            'ordersDetails' => $orderDetails,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'company_logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            'currency_symbol' => $currency->getSymbol(),
            'location' => $location,
            'orderType' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
            'productVariant' => config('app.product_variant'),
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Order $order, ProductService $productService): array
    {
        /** @var Collection $orderItems */
        $orderItems = $order->getOrderItems();

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var MemberAddress|null $memberAddress */
        $memberAddress = $member?->primaryMemberAddress;

        return [
            'member_details' => [
                'name' => $member instanceof Member ? $member->company_name ?? $member->getFullName() : null,
                'address_line_1' => $member instanceof Member ? $member->company_address ?? $memberAddress?->address_line_1 : null,
                'address_line_2' => null !== $memberAddress ? $memberAddress->address_line_2 : null,
                'mobile_number' => $member instanceof Member ? $member->mobile_number : null,
            ],
            'receipt_number' => $order->getReceiptNumber(),
            'date' => $order->getCreatedAt()?->format('Y-m-d'),
            'bill_reference_number' => $order->getBillReferenceNumber(),
            'gross_sales' => CommonFunctions::numberFormat($order->getGrossTotal()),
            'total_discount_amount' => $order->getTotalDiscountAmount(),
            'layaway_pending_amount' => $order->getLayawayPendingAmount(),
            'credit_pending_amount' => $order->getCreditPendingAmount(),
            'total_tax_amount' => $order->getTotalTaxAmount(),
            'total_amount_paid' => $order->getTotalAmountPaidForLayawayAndCreditOrder(),
            'total_amount_paid_for_credit_or_layaway' => $order->getTotalAmountPaid(),
            'order_items' => $this->getPreparedOrderItems($orderItems, $productService),
        ];
    }

    private function preparedCompanyAndLocation(Order $order): array
    {
        /** @var Location $location */
        $location = $order->location;

        /** @var Company $company */
        $company = $location->company;

        return [$company, $location];
    }

    private function getPreparedOrderItems(Collection $orderItems, ProductService $productService): array
    {
        return $orderItems->map(function ($orderItem) use ($productService): array {
            /** @var Product $product */
            $product = $orderItem->getProduct();

            return [
                'id' => $orderItem->getKey(),
                'upc' => $product->getUpc(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'attributes' => $productService->getAttributesForPrint($product),
                'quantity' => $orderItem->getQuantity(),
                'unit_price' => $orderItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat($orderItem->getSubTotal()),
                'total_discount_amount' => $orderItem->getTotalDiscountAmount(),
                'total_tax_amount' => $orderItem->getTotalTaxAmount(),
                'total_price_paid' => $orderItem->getTotalAmount(),
            ];
        })->toArray();
    }
}
