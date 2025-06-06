<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Resources;

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Product\DataPreparer\AssemblyProductDataPreparer;
use App\Domains\Product\DataPreparer\BoxProductDataPreparer;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promoter\DataPreparer\PromoterDataPreparer;
use App\Models\BookingPayment;
use App\Models\BookingPaymentRefund;
use App\Models\BoxProduct;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class BookingPaymentResource extends JsonResource
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

        /** @var ?BookingPaymentRefund $refund */
        $refund = $bookingPayment->refund;

        /** @var ?Collection $refunds */
        $refunds = $bookingPayment->refunds;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $bookingPayment->counterUpdate;

        if ($refund instanceof BookingPaymentRefund) {
            /** @var CounterUpdate $refundCounterUpdate */
            $refundCounterUpdate = $refund->counterUpdate;

            /** @var Counter $refundCounter */
            $refundCounter = $refundCounterUpdate->getCounter();

            /** @var Cashier $refundCashier */
            $refundCashier = $refundCounterUpdate->cashier;

            /** @var Employee $refundEmployee */
            $refundEmployee = $refundCashier->getEmployee();
        }

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();
        $counter['store_id'] = $counter->location_id;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Member $member */
        $member = $bookingPayment->member;

        /** @var PaymentType $refundPaymentType */
        $refundPaymentType = $refund instanceof BookingPaymentRefund ? $refund->paymentType : null;

        /** @var ?Collection $uses */
        $uses = $bookingPayment->bookingPaymentUses;

        /** @var Collection $bookingPaymentPayments */
        $bookingPaymentPayments = $bookingPayment->bookingPaymentPayments;

        $promoterDataPreparer = resolve(PromoterDataPreparer::class);
        $boxProductDataPreparer = resolve(BoxProductDataPreparer::class);
        $assemblyProductDataPreparer = resolve(AssemblyProductDataPreparer::class);

        return [
            'id' => $bookingPayment->id,
            'offline_id' => $bookingPayment->offline_id,
            'member' => $member,
            'counter' => $counter,
            'cashier' => $employee,
            'promoters' => $promoterDataPreparer->getPromoters($bookingPayment->promoters),
            'total_amount' => (float) $bookingPayment->total_amount,
            'available_amount' => (float) $bookingPayment->available_amount,
            'status' => BookingPaymentStatuses::getCaseNameByValue($bookingPayment->getStatus()),
            'created_at' => $bookingPayment->created_at ? $bookingPayment->created_at->format('Y-m-d H:i:s') : null,
            'remarks' => $bookingPayment->remarks,
            'bill_reference_number' => $bookingPayment->bill_reference_number,
            'products' => $bookingPaymentProducts->map(function ($bookingPaymentProduct) use (
                $boxProductDataPreparer,
                $assemblyProductDataPreparer,
                $promoterDataPreparer
            ): array {
                /** @var Product $product */
                $product = $bookingPaymentProduct->product;
                /** @var ?BoxProduct $boxProduct */
                $boxProduct = $bookingPaymentProduct->boxProduct;
                /** @var ?Size $size */
                $size = $product->size;
                /** @var ?Color $color */
                $color = $product->color;

                /** @var ?MasterProduct $masterProduct */
                $masterProduct = $product->masterProduct;

                $masterProductData = [];

                if ($masterProduct instanceof MasterProduct) {
                    $masterProductData = [
                        'id' => $masterProduct->id,
                        'name' => $masterProduct->name,
                        'article_number' => $masterProduct->article_number,
                        'attributes' => $product->productVariantValues,
                        'type_id' => [
                            'id' => $masterProduct->type_id,
                            'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                            'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                        ],
                        'assembly_child_master_products' => $assemblyProductDataPreparer->getAssemblyChildMasterProducts(
                            collect($masterProduct->assemblyChildMasterProducts)
                        ),
                    ];
                }

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'article_number' => $product->article_number ?? null,
                    'upc' => $product->upc,
                    'type_id' => [
                        'id' => $product->type_id,
                        'name' => ProductTypes::getFormattedCaseName($product->type_id),
                        'key' => ProductTypes::getCaseNameByValue($product->type_id),
                    ],
                    'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                    'color' => $color,
                    'size' => $size,
                    'quantity' => (float) $bookingPaymentProduct->quantity,
                    'price' => $bookingPaymentProduct->price ? (float) $bookingPaymentProduct->price : null,
                    'promoters' => $promoterDataPreparer->getPromoters($bookingPaymentProduct->promoters),
                    'bundle' => $boxProduct ? $boxProductDataPreparer->getBoxProducts($boxProduct) : null,
                    'box' => $boxProduct ? $boxProductDataPreparer->getBoxProducts($boxProduct) : null,
                    'assembly_child_products' => $assemblyProductDataPreparer->getAssemblyChildProducts(
                        $product->assemblyChildProducts
                    ),
                    'master_product' => $masterProductData,
                ];
            }),
            'payments' => $bookingPaymentPayments->map(function ($bookingPaymentPayment): array {
                /** @var PaymentType $paymentType */
                $paymentType = $bookingPaymentPayment->paymentType;

                return [
                    'payment_type' => $paymentType->name,
                    'payment_type_details' => $paymentType,
                    'amount' => (float) $bookingPaymentPayment->amount,
                    'currency_id' => $bookingPaymentPayment->currency_id ?? null,
                    'current_currency_rate' => $bookingPaymentPayment->currency_rate ?? null,
                    'currency_amount' => $bookingPaymentPayment->currency_amount ?? null,
                    'currency_symbol' => $bookingPaymentPayment->currency ? $bookingPaymentPayment->currency->symbol : null,
                    'created_at' => $bookingPaymentPayment->created_at ? $bookingPaymentPayment->created_at->format(
                        'Y-m-d H:i:s'
                    ) : null,
                    'remarks' => $bookingPaymentPayment->remarks,
                ];
            }),
            'uses' => $uses instanceof Collection ? $uses->map(function ($bookingPaymentUse): array {
                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $bookingPaymentUse->counterUpdate;
                /** @var Counter $counter */
                $counter = $counterUpdate->getCounter();
                /** @var Cashier $cashier */
                $cashier = $counterUpdate->cashier;
                /** @var Employee $employee */
                $employee = $cashier->getEmployee();

                return [
                    'counter' => [
                        'id' => $counter->id,
                        'name' => $counter->name,
                    ],
                    'cashier' => [
                        'id' => $employee->id,
                        'name' => $employee->getFullName(),
                    ],
                    'amount' => (float) $bookingPaymentUse->amount,
                    'created_at' => $bookingPaymentUse->created_at ? $bookingPaymentUse->created_at->format(
                        'Y-m-d H:i:s'
                    ) : null,
                ];
            }) : null,
            'refund' => $refund instanceof BookingPaymentRefund ? [
                'counter' => [
                    'id' => $refundCounter->id,
                    'name' => $refundCounter->name,
                ],
                'cashier' => [
                    'id' => $refundEmployee->id,
                    'name' => $refundEmployee->getFullName(),
                ],
                'payment_type' => $refundPaymentType->name,
                'amount' => (float) $refund->amount,
                'currency_id' => $refund->currency_id ?? null,
                'current_currency_rate' => $refund->currency_rate ?? null,
                'currency_amount' => $refund->currency_amount ?? null,
                'currency_symbol' => $refund->currency ? $refund->currency->symbol : null,
                'created_at' => $refund->created_at ? $refund->created_at->format('Y-m-d H:i:s') : null,
            ] : null,
            'refunds' => $refunds instanceof Collection ? $refunds->map(
                function ($bookingPaymentRefund): array {
                    /** @var CounterUpdate $refundCounterUpdate */
                    $refundCounterUpdate = $bookingPaymentRefund->counterUpdate;
                    /** @var Counter $refundCounter */
                    $refundCounter = $refundCounterUpdate->getCounter();
                    /** @var Cashier $refundCashier */
                    $refundCashier = $refundCounterUpdate->cashier;
                    /** @var Employee $refundEmployee */
                    $refundEmployee = $refundCashier->getEmployee();
                    /** @var PaymentType $refundPaymentType */
                    $refundPaymentType = $bookingPaymentRefund->paymentType;

                    return [
                        'counter' => [
                            'id' => $refundCounter->id,
                            'name' => $refundCounter->name,
                        ],
                        'cashier' => [
                            'id' => $refundEmployee->id,
                            'name' => $refundEmployee->getFullName(),
                        ],
                        'payment_type' => $refundPaymentType->name,
                        'amount' => (float) $bookingPaymentRefund->amount,
                        'currency_id' => $bookingPaymentRefund->currency_id ?? null,
                        'current_currency_rate' => $bookingPaymentRefund->currency_rate ?? null,
                        'currency_amount' => $bookingPaymentRefund->currency_amount ?? null,
                        'currency_symbol' => $bookingPaymentRefund->currency ? $bookingPaymentRefund->currency->symbol : null,
                        'created_at' => $bookingPaymentRefund->created_at ? $bookingPaymentRefund->created_at->format(
                            'Y-m-d H:i:s'
                        ) : null,
                    ];
                }
            ) : null,
        ];
    }
}
