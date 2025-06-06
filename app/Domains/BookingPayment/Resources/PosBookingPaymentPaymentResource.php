<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Resources;

use App\Domains\BookingPayment\DataPreparer\DataPreparer;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Product\Enums\ProductTypes;
use App\Models\AssemblyChildMasterProduct;
use App\Models\AssemblyChildProduct;
use App\Models\BookingPayment;
use App\Models\BoxProduct;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosBookingPaymentPaymentResource extends JsonResource
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

        /** @var Collection $bookingPaymentPayments */
        $bookingPaymentPayments = $bookingPayment->bookingPaymentPayments;

        /** @var Member $member */
        $member = $bookingPayment->member;

        /** @var Collection $bookingPaymentMismatches */
        $bookingPaymentMismatches = $bookingPayment->mismatches;
        $messages = $bookingPaymentMismatches->pluck('message')->toArray();

        return [
            'id' => $bookingPayment->id,
            'member' => $member,
            'total_amount' => (float) $bookingPayment->total_amount,
            'available_amount' => (float) $bookingPayment->available_amount,
            'status' => BookingPaymentStatuses::getCaseNameByValue($bookingPayment->getStatus()),
            'remarks' => $bookingPayment->remarks,
            'bill_reference_number' => $bookingPayment->bill_reference_number,
            'promoters' => $this->getPromoters($bookingPayment->promoters),
            'products' => $bookingPaymentProducts->map(function ($bookingPaymentProduct): array {
                /** @var Product $product */
                $product = $bookingPaymentProduct->product;

                /** @var ?BoxProduct $boxProduct */
                $boxProduct = $bookingPaymentProduct->boxProduct;

                $masterProductArray = null;
                /** @var ?MasterProduct $masterProduct */
                $masterProduct = $product->masterProduct;

                if ($masterProduct instanceof MasterProduct) {
                    $masterProductArray = [
                        'id' => $masterProduct->id,
                        'name' => $masterProduct->name,
                        'article_number' => (string) $masterProduct->article_number,
                        'type_id' => [
                            'id' => $masterProduct->type_id,
                            'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                            'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                        ],
                        'assembly_child_master_products' => $this->getAssemblyChildMasterProducts(
                            $masterProduct->assemblyChildMasterProducts
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
                    'size' => $product->size,
                    'color' => $product->color,
                    'quantity' => (float) $bookingPaymentProduct->quantity,
                    'price' => $bookingPaymentProduct->price ? (float) $bookingPaymentProduct->price : null,
                    'promoters' => $this->getPromoters($bookingPaymentProduct->promoters),
                    'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                    'box' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                    'assembly_child_products' => $this->assemblyChildProducts($product->assemblyChildProducts),
                    'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                    'master_product' => $masterProductArray,
                ];
            }),
            'mismatches' => $messages,
            'created_at' => $bookingPayment->created_at ? $bookingPayment->created_at->format('Y-m-d H:i:s') : null,
            'payments' => $bookingPaymentPayments->map(function ($bookingPaymentPayment): array {
                /** @var PaymentType $paymentType */
                $paymentType = $bookingPaymentPayment->paymentType;
                $dataPreparer = resolve(DataPreparer::class);

                return [
                    'payment_type' => $paymentType->name,
                    'amount' => (float) $bookingPaymentPayment->amount,
                    'currency_id' => $bookingPaymentPayment->currency_id ?? null,
                    'current_currency_rate' => $bookingPaymentPayment->currency_rate ?? null,
                    'currency_amount' => $bookingPaymentPayment->currency_amount ?? null,
                    'currency_symbol' => $bookingPaymentPayment->currency ? $bookingPaymentPayment->currency->symbol : null,
                    'created_at' => $bookingPaymentPayment->created_at ? $bookingPaymentPayment->created_at->format(
                        'Y-m-d H:i:s'
                    ) : null,
                    'remarks' => $bookingPaymentPayment->remarks,
                    'credit_notes' => $dataPreparer->getCreditNote($bookingPaymentPayment),
                ];
            }),
        ];
    }

    private function assemblyChildProducts(Collection $assemblyChildProducts): array
    {
        if ($assemblyChildProducts->isEmpty()) {
            return [];
        }

        return $assemblyChildProducts->map(function (AssemblyChildProduct $assemblyChildProduct): array {
            /** @var Product $product */
            $product = $assemblyChildProduct->product;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'article_number' => $product->article_number,
            ];
        })->toArray();
    }

    public function getAssemblyChildMasterProducts(Collection $assemblyChildMasterProducts): ?array
    {
        if ($assemblyChildMasterProducts->isEmpty()) {
            return [];
        }

        return $assemblyChildMasterProducts->map(
            function (AssemblyChildMasterProduct $assemblyChildMasterProduct): array {
                /** @var MasterProduct $masterProduct */
                $masterProduct = $assemblyChildMasterProduct->item;

                return [
                    'product_id' => $masterProduct->id,
                    'product_name' => $masterProduct->name,
                    'article_number' => $masterProduct->article_number,
                ];
            }
        )->toArray();
    }

    private function getPreparedBoxProduct(BoxProduct $boxProduct): array
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

    private function getPromoters(Collection $promoters): ?array
    {
        if ($promoters->isEmpty()) {
            return null;
        }

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'staff_id' => $employee->staff_id,
            ];
        })->toArray();
    }
}
