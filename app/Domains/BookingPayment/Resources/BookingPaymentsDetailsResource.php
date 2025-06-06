<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Resources;

use App\Domains\Product\Services\ProductService;
use App\Models\BookingPayment;
use App\Models\BookingPaymentRefund;
use App\Models\Employee;
use App\Models\PaymentType;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\VoidSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class BookingPaymentsDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookingPayment $bookingPayment */
        $bookingPayment = $this;

        /** @var Collection $bookingPaymentProducts */
        $bookingPaymentProducts = $bookingPayment->products;

        /** @var Collection $mismatches */
        $mismatches = $bookingPayment->mismatches;

        /** @var Collection $bookingPaymentPayments */
        $bookingPaymentPayments = $bookingPayment->bookingPaymentPayments;

        /** @var ?Collection $uses */
        $uses = $bookingPayment->bookingPaymentUses;

        /** @var ?BookingPaymentRefund $refund */
        $refund = $bookingPayment->refund;

        /** @var ?Collection $refunds */
        $refunds = $bookingPayment->refunds;

        /** @var PaymentType $refundPaymentType */
        $refundPaymentType = $refund instanceof BookingPaymentRefund ? $refund->paymentType : null;

        /** @var ?Collection $voidUses */
        $voidUses = $bookingPayment->bookingPaymentVoidUses;

        return [
            'id' => $bookingPayment->getKey(),
            'offline_id' => $bookingPayment->offline_id,
            'products' => $this->getPreparedProducts($bookingPaymentProducts),
            'payments' => $bookingPaymentPayments->map(function ($bookingPaymentPayment): array {
                /** @var PaymentType $paymentType */
                $paymentType = $bookingPaymentPayment->paymentType;

                return [
                    'id' => $bookingPaymentPayment->getKey(),
                    'payment_type' => $paymentType->name,
                    'amount' => (float) $bookingPaymentPayment->amount,
                    'created_at' => $bookingPaymentPayment->created_at ? $bookingPaymentPayment->created_at->format(
                        'Y-m-d H:i:s'
                    ) : null,
                    'remarks' => $bookingPaymentPayment->remarks,
                ];
            }),
            'mismatches' => PosMismatch::getPreparedMismatches($mismatches),
            'refund' => $refund instanceof BookingPaymentRefund ? [[
                'payment_type' => $refundPaymentType->name,
                'amount' => (float) $refund->amount,
                'created_at' => $refund->created_at ? $refund->created_at->format('Y-m-d H:i:s') : null,
            ]] : [],
            'refunds' => $refunds instanceof Collection ? $refunds->map(function ($bookingPaymentRefund): array {
                /** @var PaymentType $refundPaymentType */
                $refundPaymentType = $bookingPaymentRefund->paymentType;

                return [
                    'payment_type' => $refundPaymentType->name,
                    'amount' => (float) $bookingPaymentRefund->amount,
                    'created_at' => $bookingPaymentRefund->created_at ? $bookingPaymentRefund->created_at->format(
                        'Y-m-d H:i:s'
                    ) : null,
                ];
            }) : null,
            'uses' => $uses instanceof Collection ? $uses->map(function ($bookingPaymentUse): array {
                /** @var SalePayment $salePayment */
                $salePayment = $bookingPaymentUse->salePayment;

                /** @var Sale $sale */
                $sale = $salePayment->sale;

                return [
                    'sale_payment' => $sale->offline_sale_id,
                    'amount' => (float) $bookingPaymentUse->amount,
                ];
            }) : null,
            'voidUses' => $voidUses instanceof Collection ? $voidUses->map(function ($bookingPaymentVoidUse): array {
                /** @var VoidSale $voidSale */
                $voidSale = $bookingPaymentVoidUse->voidSale;

                /** @var Sale $sale */
                $sale = $voidSale->sale;

                return [
                    'id' => $bookingPaymentVoidUse->id,
                    'amount' => (float) $bookingPaymentVoidUse->amount,
                    'void_sale' => $sale->offline_sale_id,
                ];
            }) : null,
        ];
    }

    private function getPreparedProducts(Collection $bookingPaymentProducts): array
    {
        return $bookingPaymentProducts->map(function ($bookingPaymentProduct): array {
            /** @var Product $product */
            $product = $bookingPaymentProduct->product;

            $productService = resolve(ProductService::class);

            return [
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product?->color?->name ?? null,
                'size' => config('app.product_variant') ? null : $product?->size?->name ?? null,
                'upc' => $product->getUpc(),
                'quantity' => $bookingPaymentProduct->quantity,
                'promoters' => $this->getPromoters($bookingPaymentProduct->promoters),
                'attributes' => $productService->getAttributesWithNameAndValueKey($product),
            ];
        })->toArray();
    }

    private function getPromoters(Collection $promoters): string
    {
        return $promoters->map(function ($promoter): string {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return $employee->getFullName();
        })->implode(', ');
    }
}
