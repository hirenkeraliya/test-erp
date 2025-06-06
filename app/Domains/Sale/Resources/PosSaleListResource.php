<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\SaleDataPreparer;
use App\Domains\Sale\DataPreparer\SaleItemDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\AssemblyChildProduct;
use App\Models\Batch;
use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use App\Models\BoxProduct;
use App\Models\Cashback;
use App\Models\Cashier;
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
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemPriceOverride;
use App\Models\SalePayment;
use App\Models\SerialNumber;
use App\Models\StoreManager;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosSaleListResource extends JsonResource
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

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Collection $vouchers */
        $vouchers = $sale->generatedVouchers;

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

        /** @var ?SaleCashback $saleCashback */
        $saleCashback = $sale->cashback;
        $cashback = null;

        if ($saleCashback instanceof SaleCashback) {
            /** @var ?Cashback $cashback */
            $cashback = $saleCashback->cashbackConfiguration;
        }

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $sale->loyaltyPointUpdates;

        $userDataPreparer = resolve(UserDataPreparer::class);
        $saleDataPreparer = resolve(SaleDataPreparer::class);

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
            'cart_discount_amount' => (float) $sale->cart_discount_amount,
            'promo_code' => null !== $usedPromotion ? $usedPromotion->promo_code : null,
            'items_discount_amount' => (float) $sale->items_discount_amount,
            'total_discount_amount' => (float) $sale->total_discount_amount,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'change_due' => (float) $sale->change_due,
            'layaway_pending_amount' => (float) $sale->layaway_pending_amount,
            'credit_pending_amount' => (float) $sale->credit_pending_amount,
            'sale_items' => $this->getPreparedSaleItems($sale, $saleItems),
            'payments' => $this->getPreparedSalePayments($salePayments),
            'booking_payment_details' => $this->getPreparedBookingDetails($salePayments),
            'status' => SaleStatus::getCaseNameByValue($sale->getStatus()),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $sale->happened_at,
            'extra_details' => $sale->extra_details ?? null,
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
                'voucher_apply_footer_notes' => $voucherConfiguration?->redemption_foot_note,
                'handover_footer_notes' => $voucherConfiguration?->handover_foot_note,
                'redemption_foot_note' => $voucherConfiguration?->redemption_foot_note,
                'handover_foot_note' => $voucherConfiguration?->handover_foot_note,
                'transactions' => $voucher instanceof Voucher ? $voucher->getVoucherTransactions() : [],
            ] : null,
            'has_mismatch' => $sale->has_mismatch,
            'sale_mismatches' => $messages,
            'round_off_amount' => (float) $sale->round_off,
            'cashback' => $saleCashback instanceof SaleCashback ? $this->getPreparedSaleCashback(
                $saleCashback,
                $cashback
            ) : null,
            'is_cashback_apply' => $saleDataPreparer->isCashbackApply($saleCashback),
            'is_loyalty_points_used_as_payment' => $saleDataPreparer->isLoyaltyPointsUsedAsPayment($salePayments),
        ];
    }

    private function getPreparedBookingDetails(Collection $salePayments): ?Collection
    {
        $preparedDetails = $salePayments->map(function ($payment): ?array {
            /** @var SalePayment $salePayment */
            $salePayment = $payment;

            $bookingPayment = null;
            if ($salePayment->payment_type_id === StaticPaymentTypes::BOOKING_PAYMENT->value) {
                /** @var BookingPaymentUse $bookingPaymentUse */
                $bookingPaymentUse = $salePayment->bookingPaymentUse;

                /** @var BookingPayment $bookingPayment */
                $bookingPayment = $bookingPaymentUse->bookingPayment;
            }

            if (null !== $bookingPayment) {
                return [
                    'offline_id' => $bookingPayment->offline_id,
                    'available_amount' => $bookingPayment->available_amount,
                ];
            }

            return null;
        })->filter();

        return $preparedDetails->isNotEmpty() ? $preparedDetails : null;
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
                'happened_at' => $salePayment->happened_at,
                'extra_details' => $salePayment->extra_details ?? null,
            ];
        });
    }

    private function getPreparedSaleItems(self|Sale $sale, Collection $saleItems): Collection
    {
        $saleDataPreparer = resolve(SaleDataPreparer::class);
        $saleItemDataPreparer = resolve(SaleItemDataPreparer::class);

        return $saleItems->map(function ($item) use ($sale, $saleDataPreparer, $saleItemDataPreparer): array {
            /** @var SaleItem $saleItem */
            $saleItem = $item;
            $employee = null;

            /** @var ?SaleItemComplimentary $saleItemComplimentary */
            $saleItemComplimentary = $saleItem->saleItemComplimentary;

            if ($saleItemComplimentary instanceof SaleItemComplimentary) {
                /** @var StoreManager|Director $saleItemComplimentaryAuthorizer */
                $saleItemComplimentaryAuthorizer = $saleItemComplimentary->authorizer;

                /** @var Employee $employee */
                $employee = $saleItemComplimentaryAuthorizer->employee;
            }

            /** @var ?SaleItemPriceOverride $priceOverride */
            $priceOverride = $saleItem->saleItemPriceOverride;

            if ($priceOverride instanceof SaleItemPriceOverride) {
                /** @var Director|StoreManager|Cashier $negotiator */
                $negotiator = $priceOverride->negotiator;

                /** @var ?Employee $employee */
                $employee = $negotiator->employee;
            }

            /** @var Product $product */
            $product = $saleItem->product;

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $saleItem->boxProduct;

            /** @var Collection $assemblyChildProducts */
            $assemblyChildProducts = $product->assemblyChildProducts;

            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            return [
                'id' => $saleItem->getKey(),
                'product_id' => $saleItem->product_id,
                'product' => $this->getPreparedProduct($product),
                'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'box' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'batch_details' => $product->has_batch ? $this->getBatchDetails($saleItem->saleItemUnits) : null,
                'serial_numbers' => $this->getSerialNumbers($saleItem->saleItemUnits),
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'original_price_per_unit' => $this->getOriginalPricePerUnit($saleItem),
                'derivative' => $saleItemDataPreparer->getDerivative($saleItem),
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                'is_exchange' => $saleItem->is_exchange,
                'group_id' => $saleItem->group_id,
                /* @phpstan-ignore-next-line */
                'total_price_paid' => (SaleStatus::PENDING_LAYAWAY_SALE->value === $sale->status || SaleStatus::PENDING_CREDIT_SALE->value === $sale->status) ? $saleItem->calculateFinalSaleItemAmount() : $saleItem->total_price_paid,
                'promoters' => $this->getPromoters($saleItem),
                'complimentary' => $saleItemComplimentary instanceof SaleItemComplimentary ? [
                    'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
                    'amount' => $saleItemComplimentary->amount,
                ] : null,
                'price_override' => $priceOverride instanceof SaleItemPriceOverride ? [
                    'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
                    'amount' => $priceOverride->override_price,
                ] : null,
                'sale_item_discounts' => $saleItemDiscounts->map(function ($saleItemDiscount) use (
                    $saleDataPreparer
                ): array {
                    /** @var Promotion|DreamPrice|ComplimentaryItemReason|SaleItemPriceOverride $discountable */
                    $discountable = $saleItemDiscount->discountable;

                    return [
                        'discountable_type' => $saleItemDiscount->discountable_type,
                        'name' => $discountable->getName(),
                        'promo_code' => $saleItemDiscount->promo_code,
                        'promotion_type' => $saleDataPreparer->getPromotionType($discountable),
                    ];
                }),
                'assembly_child_products' => $assemblyChildProducts->map(function ($assemblyChildProduct): array {
                    /** @var AssemblyChildProduct $assemblyProduct */
                    $assemblyProduct = $assemblyChildProduct;

                    /** @var Product $product */
                    $product = $assemblyProduct->product;

                    return [
                        'id' => $assemblyProduct->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'units' => $assemblyProduct->units,
                    ];
                }),
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
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ];
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    private function getBatchDetails(Collection $saleItemUnits): array
    {
        return $saleItemUnits->transform(function ($saleItemUnit): array {
            /** @var Batch $batch */
            $batch = $saleItemUnit->batch;

            return [
                'number' => $batch->number,
                'quantity' => $saleItemUnit->quantity,
            ];
        })->toArray();
    }

    private function getSerialNumbers(Collection $saleItemUnits): array
    {
        if ($saleItemUnits->whereNotNull('serialNumber')->isEmpty()) {
            return [];
        }

        return $saleItemUnits->transform(function ($saleItemUnit): array {
            /** @var SerialNumber $serialNumber */
            $serialNumber = $saleItemUnit->serialNumber;

            return [
                'serial_number' => $serialNumber->serial_number,
                'quantity' => $saleItemUnit->quantity,
            ];
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    private function getPreparedSaleVouchers(Collection $vouchers): array
    {
        return $vouchers->transform(function ($voucher): array {
            /** @var VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $voucher->voucherConfiguration;

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
                'voucher_apply_footer_notes' => $voucherConfiguration->redemption_foot_note ?? null,
                'handover_footer_notes' => $voucherConfiguration->handover_foot_note ?? null,
                'redemption_foot_note' => $voucherConfiguration->redemption_foot_note ?? null,
                'handover_foot_note' => $voucherConfiguration->handover_foot_note ?? null,
                'transactions' => $voucher ? $voucher->getVoucherTransactions() : [],
                'dream_price_applicable' => $voucher->dream_price_applicable,
                'item_wise_promotion_applicable' => $voucher->item_wise_promotion_applicable,
                'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
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

    private function getPreparedSaleCashback(SaleCashback $saleCashback, ?Cashback $cashback): array
    {
        return [
            [
                'id' => $saleCashback->id,
                'name' => $cashback instanceof Cashback ? $cashback->name : null,
                'amount' => $saleCashback->amount,
                'round_off' => $saleCashback->round_off,
            ],
        ];
    }

    private function getPreparedProduct(Product $product): array
    {
        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            $masterProductArray = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'has_batch' => $masterProduct->has_batch,
                'brand' => $masterProduct->brand,
                'article_number' => (string) $masterProduct->article_number,
                'is_non_inventory' => $masterProduct->is_non_inventory,
                'type_id' => [
                    'id' => $masterProduct->type_id,
                    'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                    'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                ],
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'upc' => $product->upc,
            'has_batch' => $product->has_batch,
            'color' => $product->color,
            'size' => $product->size,
            'brand' => $product->brand,
            'article_number' => $product->article_number,
            'ean' => $product->ean,
            'is_non_inventory' => $product->is_non_inventory,
            'type_id' => [
                'id' => $product->type_id,
                'name' => ProductTypes::getFormattedCaseName($product->type_id),
                'key' => ProductTypes::getCaseNameByValue($product->type_id),
            ],
            'compound_product_name' => $product->compound_product_name,
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'master_product' => $masterProductArray,
        ];
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
