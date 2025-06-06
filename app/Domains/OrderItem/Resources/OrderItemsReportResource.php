<?php

declare(strict_types=1);

namespace App\Domains\OrderItem\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\BoxProduct;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class OrderItemsReportResource extends JsonResource
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
        $orderItems = $order->orderItems;

        /** @var Collection $orderPayments */
        $orderPayments = $order->payments;

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        return [
            'order_items' => $this->getPreparedOrderItems($orderItems, $currencySymbol),
            'type' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
            'notes' => $order->notes ?? 'N/A',
            'payments' => $this->getPreparedPayments($orderPayments, $currencySymbol),
            'total_tax_amount' => $order->getTotalTaxAmount(),
            'total_discount_amount' => $order->getTotalDiscountAmount(),
            'layaway_pending_amount' => $order->getLayawayPendingAmount(),
            'credit_pending_amount' => $order->getCreditPendingAmount(),
            'total_amount_paid' => $order->getTotalAmountPaid(),
            'delivery_charges' => $order->getDeliveryCharges(),
            'round_off' => $order->getRoundOff(),
        ];
    }

    public function getPreparedPayments(?Collection $orderPayments, string $currencySymbol): array
    {
        if (! $orderPayments instanceof Collection) {
            return [];
        }

        return $orderPayments->map(function ($payment) use ($currencySymbol): array {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $payment;
            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->paymentType;

            return [
                'id' => $orderPayment->getKey(),
                'payment_type' => $paymentType->getName(),
                'amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currencySymbol,
                    CommonFunctions::currencyFormat($orderPayment->getAmount())
                ),
            ];
        })->toArray();
    }

    public function getPromoters(OrderItem $orderItem): string
    {
        /** @var Collection $promoters */
        $promoters = $orderItem->promoters;

        return $promoters->map(function ($promoter): string {
            /** @var Promoter $orderItemPromoter */
            $orderItemPromoter = $promoter;
            /** @var Employee $employee */
            $employee = $orderItemPromoter->employee;

            return $employee->getFullName();
        })->implode(', ');
    }

    /**
     * @return mixed[]
     */
    private function getPreparedOrderItems(Collection $orderItems, string $currencySymbol): array
    {
        return $orderItems->map(function ($orderItem) use ($currencySymbol): array {
            /** @var Product $product */
            $product = $orderItem->product;

            /** @var ?ComplimentaryItemReason $complimentaryItemReason */
            $complimentaryItemReason = $orderItem->complimentaryItemReason;

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $orderItem->boxProduct;

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
                'quantity' => CommonFunctions::truncateDecimal($orderItem->getQuantity()),
                'unit_price' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currencySymbol,
                    CommonFunctions::currencyFormat($orderItem->getOriginalPricePerUnit())
                ),
                'subtotal' => $orderItem->getSubTotal(),
                'total_discount_amount' => $orderItem->getTotalDiscountAmount(),
                'total_tax_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currencySymbol,
                    CommonFunctions::currencyFormat($orderItem->getTotalTaxAmount())
                ),
                'total_price_paid' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currencySymbol,
                    CommonFunctions::currencyFormat($orderItem->getTotalPricePaid())
                ),
                'original_price_per_unit' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currencySymbol,
                    $orderItem->getOriginalPricePerUnit()
                ),
                'complimentary_item_reason' => $complimentaryItemReason instanceof ComplimentaryItemReason ? $complimentaryItemReason->reason : null,
                'promoters' => $this->getPromoters($orderItem),
                'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
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
