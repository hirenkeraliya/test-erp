<?php

declare(strict_types=1);

namespace App\Domains\Order\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PaginatedOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $order = $this->resource;

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        /** @var Collection $orderItems */
        $orderItems = $order->orderItems;

        /** @var Collection $orderPayments */
        $orderPayments = $order->payments;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $order->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $order->getKey(),
            'receipt_number' => $order->receipt_number,
            'notes' => $order->notes,
            'happened_at' => $happenedAt,
            'total_tax_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalTaxAmount(),
                false
            ),
            'cart_discount_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalAmountPaid(),
                true
            ),
            'item_discount_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalAmountPaid(),
                false
            ),
            'total_discount_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalDiscountAmount(),
                true
            ),
            'total_amount_paid' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalAmountPaid(),
                false
            ),
            'delivery_charges' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalAmountPaid(),
                false
            ),
            'status' => $order->status,
            'status_name' => OrderStatus::getCaseNameByValue($order->status->value),
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'payments' => $this->getPreparedOrderPayments($orderPayments),
        ];
    }

    private function getPreparedOrderItems(Collection $orderItems): Collection
    {
        return $orderItems->map(function ($item): array {
            /** @var OrderItem $orderItem */
            $orderItem = $item;

            /** @var Product $product */
            $product = $orderItem->product;

            return [
                'id' => $orderItem->getKey(),
                'product_id' => $orderItem->product_id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                'quantity' => (float) $orderItem->quantity,
                'original_product_price_per_unit' => (float) $orderItem->original_product_price_per_unit,
                'cart_discount_amount' => (float) $orderItem->cart_discount_amount,
                'item_discount_amount' => (float) $orderItem->item_discount_amount,
                'total_discount_amount' => (float) $orderItem->total_discount_amount,
                'price_paid_per_unit' => (float) $orderItem->price_paid_per_unit,
                'total_price_paid' => (float) $orderItem->total_price_paid,
            ];
        });
    }

    private function getPreparedOrderPayments(Collection $orderPayments): Collection
    {
        return $orderPayments->map(function ($item): array {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $item;

            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->paymentType;

            return [
                'id' => $orderPayment->getKey(),
                'amount' => (float) $orderPayment->amount,
                'payment_type' => $paymentType,
            ];
        });
    }
}
