<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\SaleItemDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\BoxProduct;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Promotion;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemPriceOverride;
use App\Models\SalePayment;
use App\Models\StoreManager;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosPendingCreditSaleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $sale = $this->resource;

        /** @var Collection $saleMismatches */
        $saleMismatches = $sale->mismatches;
        $messages = $saleMismatches->pluck('message')->toArray();

        /** @var Collection $saleItems */
        $saleItems = $sale->getSaleItems();

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $sale->loyaltyPointUpdates;

        /** @var ?SaleDiscount $usedVoucher */
        $usedVoucher = $sale->usedVoucher;

        /** @var ?SaleDiscount $usedPromotion */
        $usedPromotion = $sale->usedPromotion;

        if ($usedVoucher instanceof SaleDiscount) {
            /** @var ?Voucher $voucher */
            $voucher = $usedVoucher->discountable;

            /** @var ?VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $voucher instanceof Voucher ? $voucher->voucherConfiguration : null;
        }

        /** @var Collection $vouchers */
        $vouchers = $sale->generatedVouchers;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->offline_sale_id,
            'user_type' => $userDataPreparer->getUserType($sale),
            'user_id' => $sale->member_id,
            'user_details' => $userDataPreparer->getUserDetails($sale->member, $loyaltyPointUpdates, $saleItems),
            'member_id' => $sale->member_id,
            'member' => $userDataPreparer->getUserDetails($sale->member, $loyaltyPointUpdates, $saleItems),
            'cashier' => [
                'id' => $cashier->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ],
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->getName(),
            ],
            'total_tax_amount' => (float) $sale->total_tax_amount,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'change_due' => (float) $sale->change_due,
            'credit_pending_amount' => (float) $sale->credit_pending_amount,
            'round_off_amount' => (float) $sale->round_off,
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'payments' => $this->getPreparedSalePayments($salePayments),
            'status' => SaleStatus::getCaseNameByValue($sale->getStatus()),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $sale->happened_at,
            'has_mismatch' => $sale->has_mismatch,
            'sale_mismatches' => $messages,
            'promo_code' => null !== $usedPromotion ? $usedPromotion->promo_code : null,
            'vouchers' => $this->getPreparedSaleVouchers($vouchers),
            'used_voucher' => $usedVoucher instanceof SaleDiscount ? [
                'id' => $usedVoucher->discountable_id,
                'voucher_type' => $voucherConfiguration instanceof VoucherConfiguration ?
                    VoucherConfigurationService::getVoucherType(
                        $voucherConfiguration->restricted_by_type,
                        $voucherConfiguration->voucher_type,
                        $voucherConfiguration->discount_type
                    ) : null,
                'number' => $voucher?->number,
                'amount' => $usedVoucher->amount,
            ] : null,
            'location_id' => $counter->location_id,
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPreparedSaleVouchers(Collection $vouchers): array
    {
        return $vouchers->transform(function ($voucher): array {
            [$discountType , $discountValue] = $this->getKeyAndValueForSelectedVoucher(
                $voucher->discount_type,
                $voucher->percentage,
                $voucher->flat_amount,
            );

            return [
                'id' => $voucher->id,
                'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
                'number' => $voucher->number,
                'minimum_spend_amount' => (float) $voucher->minimum_spend_amount,
                $discountType => (float) $discountValue,
                'expiry_date' => $voucher->expiry_date,
            ];
        })->toArray();
    }

    /**
     * @return string[]|null[]
     */
    private function getKeyAndValueForSelectedVoucher(
        int $discountType,
        ?string $percentage,
        ?string $flatAmount,
    ): array {
        if ($discountType === DiscountTypes::PERCENTAGE->value) {
            return ['percentage', $percentage];
        }

        return ['flat_amount', $flatAmount];
    }

    private function getPreparedSalePayments(Collection $salePayments): Collection
    {
        return $salePayments->map(function ($payment): array {
            /** @var SalePayment $salePayment */
            $salePayment = $payment;

            /** @var PaymentType $paymentType */
            $paymentType = $salePayment->paymentType;

            return [
                'id' => $salePayment->getKey(),
                'payment_type' => $paymentType,
                'amount' => (float) $salePayment->amount,
                'currency_id' => $salePayment->currency_id ?? null,
                'current_currency_rate' => $salePayment->currency_rate ?? null,
                'currency_amount' => $salePayment->currency_amount ?? null,
                'currency_symbol' => $salePayment->currency ? $salePayment->currency->symbol : null,
                'happened_at' => $payment->happened_at,
            ];
        });
    }

    private function getPreparedSaleItems(Collection $saleItems): Collection
    {
        return $saleItems->map(function ($item): array {
            /** @var SaleItem $saleItem */
            $saleItem = $item;

            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            /** @var ?SaleItemComplimentary $saleItemComplimentary */
            $saleItemComplimentary = $saleItem->saleItemComplimentary;

            if ($saleItemComplimentary instanceof SaleItemComplimentary) {
                /** @var StoreManager|Director $saleItemComplimentaryAuthorizer */
                $saleItemComplimentaryAuthorizer = $saleItemComplimentary->authorizer;

                /** @var Employee $complimentaryEmployee */
                $complimentaryEmployee = $saleItemComplimentaryAuthorizer->employee;
            }

            /** @var Product $product */
            $product = $saleItem->product;

            /** @var Collection $categories */
            $categories = $product->categories;

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $saleItem->boxProduct;

            /** @var ?SaleItemPriceOverride $priceOverride */
            $priceOverride = $saleItem->saleItemPriceOverride;

            if ($priceOverride instanceof SaleItemPriceOverride) {
                /** @var Director|StoreManager|Cashier $negotiator */
                $negotiator = $priceOverride->negotiator;

                /** @var ?Employee $employee */
                $employee = $negotiator->employee;
            }

            $saleItemDataPreparer = resolve(SaleItemDataPreparer::class);

            $masterProductArray = null;
            /** @var ?MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;

            if ($masterProduct instanceof MasterProduct) {
                $masterProductArray = [
                    'id' => $masterProduct->id,
                    'name' => $masterProduct->name,
                    'has_batch' => $masterProduct->has_batch,
                    'article_number' => $masterProduct->article_number,
                    'is_non_inventory' => $masterProduct->is_non_inventory,
                    'brand' => $masterProduct->brand,
                    'type_id' => [
                        'id' => $masterProduct->type_id,
                        'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                        'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                    ],
                    'categories' => $masterProduct->categories->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]),
                ];
            }

            return [
                'id' => $saleItem->getKey(),
                'product_id' => $saleItem->product_id,
                'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'box' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'upc' => $product->upc,
                    'has_batch' => $product->has_batch,
                    'article_number' => $product->article_number,
                    'ean' => $product->ean,
                    'is_non_inventory' => $product->is_non_inventory,
                    'compound_product_name' => $product->compound_product_name,
                    'type_id' => [
                        'id' => $product->type_id,
                        'name' => ProductTypes::getFormattedCaseName($product->type_id),
                        'key' => ProductTypes::getCaseNameByValue($product->type_id),
                    ],
                    'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                    'color' => $product->color,
                    'size' => $product->size,
                    'brand' => $product->brand,
                    'categories' => $categories->map(function ($category): array {
                        /** @var Category $productCategory */
                        $productCategory = $category;

                        return [
                            'id' => $productCategory->id,
                            'name' => $productCategory->name,
                        ];
                    }),
                    'master_product' => $masterProductArray,
                ],
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'original_price_per_unit' => $this->getOriginalPricePerUnit($saleItem),
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                'total_price_paid' => $saleItem->calculateFinalSaleItemAmount(),
                'is_exchange' => $saleItem->is_exchange,
                'promoters' => $this->getPromoters($saleItem),
                'price_override' => $priceOverride instanceof SaleItemPriceOverride ? $this->getPriceOverride(
                    $priceOverride,
                    $employee
                ) : null,
                'complimentary' => $saleItemComplimentary instanceof SaleItemComplimentary ? [
                    'authorizer' => $complimentaryEmployee->getFullName(),
                    'amount' => $saleItemComplimentary->amount,
                ] : null,
                'sale_item_discounts' => $saleItemDiscounts->map(function ($saleItemDiscount): array {
                    /** @var Promotion|DreamPrice|ComplimentaryItemReason|SaleItemPriceOverride $discountable */
                    $discountable = $saleItemDiscount->discountable;

                    return [
                        'discountable_type' => $saleItemDiscount->discountable_type,
                        'name' => $discountable->getName(),
                        'promo_code' => $saleItemDiscount->promo_code,
                    ];
                }),
                'derivative' => $saleItemDataPreparer->getDerivative($saleItem),
            ];
        });
    }

    private function getOriginalPricePerUnit(SaleItem $saleItem): float
    {
        if ($saleItem->original_price_per_unit > 0) {
            return (float) $saleItem->original_price_per_unit;
        }

        if ($saleItem->loyaltyPointUpdates->isNotEmpty()) {
            /** @var Product $product */
            $product = $saleItem->product;

            return (float) $product->retail_price;
        }

        return 0.00;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPriceOverride(SaleItemPriceOverride $priceOverride, ?Employee $employee): array
    {
        return [
            'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
            'amount' => $priceOverride->override_price,
        ];
    }

    private function getPromoters(SaleItem $saleItem): ?array
    {
        if ($saleItem->promoters->isEmpty()) {
            return null;
        }

        return $saleItem->promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'code' => $promoter->code,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ];
        })->toArray();
    }

    private function getPreparedBoxProduct(BoxProduct $boxProduct): array
    {
        /** @var PackageType $packageType */
        $packageType = $boxProduct->packageType;

        return [
            'id' => $boxProduct->id,
            'unit_of_measure_two_id' => $boxProduct->package_type_id,
            'unit_of_measure_two_name' => $packageType->name,
            'package_type_id' => $boxProduct->package_type_id,
            'package_type_name' => $packageType->name,
            'units' => $boxProduct->units,
            'retail_price' => $boxProduct->retail_price,
            'staff_price' => $boxProduct->staff_price,
        ];
    }
}
