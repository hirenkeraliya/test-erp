<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\Order\OrderQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrintPurchaseOrderService
{
    public function print(int $orderId): string
    {
        $orderQueries = resolve(OrderQueries::class);

        $order = $orderQueries->getOrderDetailsForReceipt($orderId);

        $orderDetails = $this->preparedData($order);

        /** @var Location $location */
        $location = $order->location;

        /** @var Company $company */
        $company = $location->company;

        return view('prints.print_purchase_order', [
            'ordersDetails' => $orderDetails,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Order $order): array
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
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'total_quantity' => collect($this->getPreparedOrderItems($orderItems))->sum('quantity'),
        ];
    }

    private function getPreparedOrderItems(Collection $orderItems): array
    {
        return $orderItems->map(function ($orderItem): array {
            /** @var Product $product */
            $product = $orderItem->getProduct();

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'upc' => $product->getUpc(),
                'quantity' => $orderItem->getQuantity(),
            ];
        })->toArray();
    }
}
