<?php

declare(strict_types=1);

namespace App\Domains\Order\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\BoxProduct;
use App\Models\City;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

class OrderReceiptResource extends JsonResource
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

        /** @var ?StoreManager $storeManager */
        $storeManager = $order->getStoreManager();

        if ($storeManager instanceof StoreManager) {
            /** @var Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager->getEmployee();
        }

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var ?City $city */
        $city = $location->getCity();

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var Company $company */
        $company = $location->getCompany();

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $order->getKey(),
            'receipt_number' => $order->getReceiptNumber(),
            'location' => [
                'id' => $location->getKey(),
                'name' => $location->getName(),
                'address_line_1' => $location->getAddressLine1(),
                'address_line_2' => $location->getAddressLine2(),
                'city' => $city?->name,
                'area_code' => $location->getAreaCode(),
                'receipt_footer' => $location->getReceiptFooter(),
                'disclaimer' => $location->getDisclaimer(),
            ],
            'company' => [
                'id' => $company->getKey(),
                'name' => $company->getName(),
                'social_security_number' => $company->getSocialSecurityNumber(),
                'logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            ],
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'member_id' => $member instanceof Member ? $member->getKey() : null,
            'gross_orders' => CommonFunctions::numberFormat($order->getGrossTotal()),
            'total_tax_amount' => $order->getTotalTaxAmount(),
            'total_discount_amount' => $order->getTotalDiscountAmount(),
            'layaway_pending_amount' => $order->getLayawayPendingAmount(),
            'credit_pending_amount' => $order->getCreditPendingAmount(),
            'total_amount_paid' => $order->getTotalAmountPaid(),
            'net_total' => $order->netAmount(),
            'units_sold' => $this->getTotalUnitsSold($orderItems),
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'payments' => $this->getPreparedOrderPayments($orderPayments),
            'type' => OrderTypes::getCaseNameByValue($order->getTypeId()->value),
            'order_notes' => $order->notes,
            'bill_reference_number' => $order->bill_reference_number,
            'happened_at' => $happenedAt,
            'round_off' => $order->getRoundOff(),
            'store_manager' => $storeManager ? $storeManagerEmployee->getFullName() : 'N/A',
            'order_barcode' => $this->getOrderBarcode($order->getReceiptNumber()),
        ];
    }

    private function getOrderBarcode(string $receiptNumber): string
    {
        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        return base64_encode(
            $barcodeGeneratorPNG->getBarcode($receiptNumber, $barcodeGeneratorPNG::TYPE_CODE_128, 2, 35)
        );
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

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $orderItem->boxProduct;

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
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
