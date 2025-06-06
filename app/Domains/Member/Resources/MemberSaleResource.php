<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\SaleItemDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\Batch;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
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
use App\Models\Size;
use App\Models\StoreManager;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MemberSaleResource extends JsonResource
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

        /** @var Collection $saleItems */
        $saleItems = $sale->getSaleItems();

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Collection $vouchers */
        $vouchers = $sale->generatedVouchers;

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $sale->loyaltyPointUpdates;

        /** @var ?SaleCashback $saleCashback */
        $saleCashback = $sale->cashback;
        $cashback = null;

        if ($saleCashback instanceof SaleCashback) {
            /** @var ?Cashback $cashback */
            $cashback = $saleCashback->cashbackConfiguration;
        }

        /** @var ?SaleDiscount $usedVoucher */
        $usedVoucher = $sale->usedVoucher;

        if ($usedVoucher instanceof SaleDiscount) {
            /** @var ?Voucher $voucher */
            $voucher = $usedVoucher->discountable;

            /** @var ?VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $voucher instanceof Voucher ? $voucher->voucherConfiguration : null;
        }

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->offline_sale_id,
            'user_type' => $userDataPreparer->getUserType($sale),
            'user_id' => $sale->member_id,
            'user_details' => $userDataPreparer->getUserDetails($sale->member, $loyaltyPointUpdates, $saleItems),
            'member_id' => $sale->member_id,
            'member' => $userDataPreparer->getUserDetails($sale->member, $loyaltyPointUpdates, $saleItems),
            'company' => [
                'id' => $company->id,
                'name' => $company->getName(),
            ],
            'store' => [
                'id' => $location->id,
                'name' => $location->getName(),
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->getName(),
            ],
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->getName(),
            ],
            'cashier' => [
                'id' => $cashier->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ],
            'total_tax_amount' => (float) $sale->total_tax_amount,
            'cart_discount_amount' => (float) $sale->cart_discount_amount,
            'items_discount_amount' => (float) $sale->items_discount_amount,
            'total_discount_amount' => (float) $sale->total_discount_amount,
            'total_amount_before_round_off' => (float) $sale->total_amount_before_round_off,
            'round_off_amount' => (float) $sale->round_off,
            'change_due' => (float) $sale->change_due,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'layaway_pending_amount' => (float) $sale->layaway_pending_amount,
            'layaway_completed_at' => (float) $sale->layaway_completed_at,
            'credit_pending_amount' => (float) $sale->credit_pending_amount,
            'credit_completed_at' => (float) $sale->credit_completed_at,
            'sale_items' => $this->getPreparedSaleItems($sale, $saleItems),
            'payments' => $this->getPreparedSalePayments($salePayments),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'extra_details' => $sale->extra_details ?? null,
            'vouchers' => $this->getPreparedSaleVouchers($vouchers),
            'has_mismatch' => $sale->has_mismatch,
            'cashback' => $saleCashback instanceof SaleCashback ? $this->getPreparedSaleCashback(
                $saleCashback,
                $cashback
            ) : null,
            'used_voucher' => $usedVoucher instanceof SaleDiscount ? [
                'id' => $usedVoucher->discountable_id,
                'voucher_type' => $voucherConfiguration instanceof VoucherConfiguration ?
                    VoucherConfigurationService::getVoucherType(
                        $voucherConfiguration->restricted_by_type,
                        $voucherConfiguration->voucher_type,
                        $voucherConfiguration->discount_type
                    ) : null,
                'amount' => $usedVoucher->amount,
                'number' => $voucher ? $voucher->number : null,
                'store_name' => $location->getName(),
            ] : null,
            'status' => SaleStatus::getCaseNameByValue($sale->getStatus()),
            'happened_at' => $sale->happened_at,
            'sale_invoice_footer' => [
                'receipt_footer' => $location->receipt_footer,
                'disclaimer' => $location->disclaimer,
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, name: mixed, amount: mixed}>
     */
    private function getPreparedSaleCashback(SaleCashback $saleCashback, ?Cashback $cashback): array
    {
        return [
            [
                'id' => $saleCashback->id,
                'name' => $cashback instanceof Cashback ? $cashback->name : null,
                'amount' => $saleCashback->amount,
            ],
        ];
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
                'happened_at' => $salePayment->happened_at,
            ];
        });
    }

    private function getPreparedSaleItems(self|Sale $sale, Collection $saleItems): Collection
    {
        $saleItemDataPreparer = resolve(SaleItemDataPreparer::class);

        return $saleItems->map(function ($item) use ($sale, $saleItemDataPreparer): array {
            $employee = null;
            /** @var SaleItem $saleItem */
            $saleItem = $item;

            /** @var Product $product */
            $product = $saleItem->product;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var ?SaleItemComplimentary $complimentary */
            $complimentary = $saleItem->saleItemComplimentary;

            if ($complimentary instanceof SaleItemComplimentary) {
                /** @var Director|StoreManager $authorizer */
                $authorizer = $complimentary->authorizer;

                /** @var ?Employee $employee */
                $employee = $authorizer->employee;
            }

            /** @var ?SaleItemPriceOverride $priceOverride */
            $priceOverride = $saleItem->saleItemPriceOverride;

            if ($priceOverride instanceof SaleItemPriceOverride) {
                /** @var Director|StoreManager|Cashier $negotiator */
                $negotiator = $priceOverride->negotiator;

                /** @var ?Employee $employee */
                $employee = $negotiator->employee;
            }

            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

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
                ];
            }

            return [
                'id' => $saleItem->getKey(),
                'product_id' => $saleItem->product_id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'article_number' => $product->article_number,
                    'type_id' => [
                        'id' => $product->type_id,
                        'name' => ProductTypes::getFormattedCaseName($product->type_id),
                        'key' => ProductTypes::getCaseNameByValue($product->type_id),
                    ],
                    'upc' => $product->upc,
                    'color' => $color,
                    'size' => $size,
                    'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                    'master_product' => $masterProductArray,
                ],
                'batch_details' => $product->has_batch ? $this->getBatchDetails($saleItem->saleItemUnits) : null,
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'original_price_per_unit' => (float) $saleItem->original_price_per_unit,
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                /* @phpstan-ignore-next-line */
                'total_price_paid' => (SaleStatus::PENDING_LAYAWAY_SALE->value === $sale->status || SaleStatus::PENDING_CREDIT_SALE->value === $sale->status) ? $saleItem->calculateFinalSaleItemAmount() : $saleItem->total_price_paid,
                'promoters' => $this->getPromoters($saleItem),
                'derivative' => $saleItemDataPreparer->getDerivative($saleItem),
                'complimentary' => $complimentary instanceof SaleItemComplimentary ? [
                    'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
                    'amount' => $complimentary->amount,
                ] : null,
                'price_override' => $priceOverride instanceof SaleItemPriceOverride ? [
                    'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
                    'amount' => $priceOverride->override_price,
                ] : null,
                'sale_item_discounts' => $saleItemDiscounts->map(function ($saleItemDiscount): array {
                    /** @var Promotion|DreamPrice|ComplimentaryItemReason|SaleItemPriceOverride $discountable */
                    $discountable = $saleItemDiscount->discountable;

                    return [
                        'discountable_type' => $saleItemDiscount->discountable_type,
                        'name' => $discountable->getName(),
                    ];
                }),
            ];
        });
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
                'staff_id' => $employee->staff_id,
            ];
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    private function getBatchDetails(Collection $saleItemUnits): array
    {
        return $saleItemUnits->transform(function ($saleItemUnit): array {
            /** @var ?Batch $batch */
            $batch = $saleItemUnit->batch;

            return [
                'number' => $batch instanceof Batch ? $batch->number : 'N/A',
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
            [$discountType , $discountValue] = $this->getKeyAndValueForSelectedVoucher(
                $voucher->discount_type,
                $voucher->percentage,
                $voucher->flat_amount,
            );

            return [
                'id' => $voucher->id,
                'discount_type' => DiscountTypes::getFormattedCaseName($voucher->discount_type),
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
}
