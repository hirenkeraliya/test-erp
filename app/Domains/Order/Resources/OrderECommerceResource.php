<?php

declare(strict_types=1);

namespace App\Domains\Order\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderStatus;
use App\Models\BoxProduct;
use App\Models\Color;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Size;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class OrderECommerceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $order = $this->resource;

        /** @var Collection $orderItems */
        $orderItems = $order->getOrderItems();

        /** @var Collection $orderPayments */
        $orderPayments = $order->getPayments();

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $order->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $order->getKey(),
            'receipt_number' => $order->getReceiptNumber(),
            'bill_reference_number' => $order->getBillReferenceNumber(),
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'member_id' => $member instanceof Member ? $member->getKey() : null,
            'gross_orders' => CommonFunctions::numberFormat($order->getGrossTotal()),
            'total_tax_amount' => $order->getTotalTaxAmount(),
            'total_discount_amount' => $order->getTotalDiscountAmount(),
            'total_amount_paid' => $order->getTotalAmountPaid(),
            'net_total' => $order->netAmount(),
            'units_sold' => $this->getTotalUnitsSold($orderItems),
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'payments' => $this->getPreparedOrderPayments($orderPayments),
            'order_notes' => $order->notes,
            'happened_at' => $happenedAt,
            'round_off' => $order->getRoundOff(),
            'delivery_charges' => $order->getDeliveryCharges(),
            'status' => OrderStatus::getFormattedCaseName($order->getStatus()->value),
        ];
    }

    private function getTotalUnitsSold(Collection $orderItems): float
    {
        $totalUnitsSold = $orderItems->sum(fn ($orderItem): ?float => $orderItem->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsSold);
    }

    private function getPreparedOrderPayments(Collection $orderPayments): Collection
    {
        return $orderPayments->map(function (OrderPayment $orderPayment): array {
            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->getPaymentType();

            return [
                'id' => $orderPayment->getKey(),
                'payment_type' => $paymentType,
                'amount' => (float) $orderPayment->amount,
                'happened_at' => $orderPayment->created_at,
            ];
        });
    }

    private function getPreparedOrderItems(Collection $orderItems): Collection
    {
        return $orderItems->map(function (OrderItem $orderItem): array {
            /** @var Product $product */
            $product = $orderItem->getProduct();

            $product->load(['color:id,name', 'size:id,name']);

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $orderItem->boxProduct;

            $masterProductArray = null;
            /** @var ?MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;

            if ($masterProduct instanceof MasterProduct) {
                $masterProductArray = [
                    'name' => $masterProduct->name,
                ];
            }

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'color' => $color instanceof Color ? $color->getName() : 'N/A',
                'size' => $size instanceof Size ? $size->getName() : 'N/A',
                'upc' => $product->getUpc(),
                'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                'master_product' => $masterProductArray,
                'quantity' => (float) $orderItem->quantity,
                'unit_price' => $orderItem->getPricePaidPerUnit(),
                'subtotal' => CommonFunctions::numberFormat($orderItem->getSubTotal()),
                'total_discount_amount' => $orderItem->getTotalDiscountAmount(),
                'total_tax_amount' => $orderItem->getTotalTaxAmount(),
                'total_price_paid' => $orderItem->total_price_paid,
                'original_price_per_unit' => $orderItem->getOriginalPricePerUnit(),
                'promoters' => $this->getPromoters($orderItem),
                'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
            ];
        });
    }

    private function getPromoters(OrderItem $orderItem): ?array
    {
        if ($orderItem->promoters->isEmpty()) {
            return null;
        }

        return $orderItem->promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ];
        })->toArray();
    }

    public function getPreparedBoxProduct(BoxProduct $boxProduct): array
    {
        /** @var PackageType $packageType */
        $packageType = $boxProduct->packageType;

        return [
            'id' => $boxProduct->id,
            'package_type_id' => $boxProduct->package_type_id,
            'package_type_name' => $packageType->name,
            'units' => $boxProduct->units,
            'retail_price' => $boxProduct->retail_price,
            'staff_price' => $boxProduct->staff_price,
        ];
    }
}
