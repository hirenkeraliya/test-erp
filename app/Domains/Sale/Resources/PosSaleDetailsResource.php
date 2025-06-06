<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\SaleItemDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\Batch;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SerialNumber;
use App\Models\Size;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosSaleDetailsResource extends JsonResource
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

        /** @var VoidSale|null $voidSale */
        $voidSale = $sale->voidSale;

        /** @var VoidSaleReason|null $voidSaleReason */
        $voidSaleReason = $voidSale instanceof VoidSale ? $voidSale->voidSaleReason : null;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $sale->loyaltyPointUpdates;

        /** @var ?SaleDiscount $usedVoucher */
        $usedVoucher = $sale->usedVoucher;

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
            'store_id' => $counter->location_id,
            'location_id' => $counter->location_id,
            'total_tax_amount' => (float) $sale->total_tax_amount,
            'cart_discount_amount' => (float) $sale->cart_discount_amount,
            'items_discount_amount' => (float) $sale->items_discount_amount,
            'total_discount_amount' => (float) $sale->total_discount_amount,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'change_due' => (float) $sale->change_due,
            'layaway_pending_amount' => (float) $sale->layaway_pending_amount,
            'credit_pending_amount' => (float) $sale->credit_pending_amount,
            'sale_items' => $this->getPreparedSaleItems($sale, $saleItems),
            'payments' => $salePayments->map(function ($payment): array {
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
                ];
            }),
            'void_reason' => $voidSaleReason instanceof VoidSaleReason ? $voidSaleReason->reason : null,
            'status' => SaleStatus::getCaseNameByValue($sale->getStatus()),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $sale->happened_at,
            'has_mismatch' => $sale->has_mismatch,
            'sale_mismatches' => $messages,
            'round_off_amount' => (float) $sale->round_off,
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

    private function getPreparedSaleItems(self|Sale $sale, Collection $saleItems): Collection
    {
        return $saleItems->map(function ($item) use ($sale): array {
            /** @var SaleItem $saleItem */
            $saleItem = $item;

            /** @var Product $product */
            $product = $saleItem->product;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            $saleItemDataPreparer = resolve(SaleItemDataPreparer::class);

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
                    'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
                    'color' => $color,
                    'size' => $size,
                    'master_product' => $masterProductData,
                ],
                'batch_details' => $product->has_batch ? $this->getBatchDetails($saleItem->saleItemUnits) : null,
                'serial_numbers' => $this->getSerialNumbers($saleItem->saleItemUnits),
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'original_price_per_unit' => $this->getOriginalPricePerUnit($saleItem),
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                /* @phpstan-ignore-next-line */
                'total_price_paid' => (SaleStatus::PENDING_LAYAWAY_SALE->value === $sale->status || SaleStatus::PENDING_CREDIT_SALE->value === $sale->status) ? $saleItem->calculateFinalSaleItemAmount() : $saleItem->total_price_paid,
                'promoters' => $this->getPromoters($saleItem),
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
}
