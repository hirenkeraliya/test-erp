<?php

declare(strict_types=1);

namespace App\Domains\CancelCreditSale\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\CancelCreditSale;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SaleCashback;
use App\Models\SaleItem;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemPriceOverride;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosCancelCreditSalesResource extends JsonResource
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

        /** @var Collection $payments */
        $payments = $sale->payments;

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $sale->loyaltyPointUpdates;

        /** @var CancelCreditSale $cancelCreditSale */
        $cancelCreditSale = $sale->cancelCreditSale;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var StoreManager $storeManager */
        $storeManager = $cancelCreditSale->storeManager;

        $creditNote = $cancelCreditSale->creditNote;

        /** @var Employee $employeeStoreManager */
        $employeeStoreManager = $storeManager->employee;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Collection $saleMismatches */
        $saleMismatches = $sale->mismatches;
        $messages = $saleMismatches->pluck('message')->toArray();

        /** @var ?SaleCashback $saleCashback */
        $saleCashback = $sale->cashback;
        $cashback = null;

        if ($saleCashback instanceof SaleCashback) {
            /** @var ?Cashback $cashback */
            $cashback = $saleCashback->cashbackConfiguration;
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
            'cart_discount_amount' => (float) $sale->cart_discount_amount,
            'items_discount_amount' => (float) $sale->items_discount_amount,
            'total_discount_amount' => (float) $sale->total_discount_amount,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'change_due' => (float) $sale->change_due,
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'payments' => $this->getPayments($payments),
            'reason' => $cancelCreditSale->reason,
            'store_manager' => [
                'id' => $cancelCreditSale->store_manager_id,
                'first_name' => $employeeStoreManager->first_name,
                'last_name' => $employeeStoreManager->last_name,
                'email' => $employeeStoreManager->email,
                'mobile_number' => $employeeStoreManager->mobile_number,
                'staff_id' => $employeeStoreManager->staff_id,
            ],
            'status' => SaleStatus::getCaseNameByValue($sale->getStatus()),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $sale->happened_at,
            'has_mismatch' => $sale->has_mismatch,
            'sale_mismatches' => $messages,
            'cashback' => $saleCashback instanceof SaleCashback ? $this->getPreparedSaleCashback(
                $saleCashback,
                $cashback
            ) : null,
            'vouchers' => $this->getPreparedSaleVouchers($vouchers),
            'credit_note' => $creditNote instanceof CreditNote ? $this->getCreditNote($creditNote) : null,
        ];
    }

    private function getPreparedSaleItems(Collection $saleItems): Collection
    {
        return $saleItems->map(function ($saleItem): array {
            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            $complimentary = null;
            $priceOverride = null;
            $employee = null;
            /** @var Product $product */
            $product = $saleItem->product;

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
                    'type_id' => [
                        'id' => $masterProduct->type_id,
                        'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                        'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                    ],
                ];
            }

            return [
                'id' => $saleItem->id,
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
                    'master_product' => $masterProductArray,
                ],
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'original_price_per_unit' => (float) $saleItem->original_price_per_unit,
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                'total_price_paid' => (float) $saleItem->total_price_paid,
                'promoters' => $this->getPromoters($saleItem),
                'complimentary' => $complimentary instanceof SaleItemComplimentary ? $this->getComplimentary(
                    $complimentary,
                    $employee
                ) : null,
                'price_override' => $priceOverride instanceof SaleItemPriceOverride ? $this->getPriceOverride(
                    $priceOverride,
                    $employee
                ) : null,
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
                'round_off' => $saleCashback->round_off,
            ],
        ];
    }

    private function getPayments(Collection $payments): Collection
    {
        return $payments->map(function ($payment): array {
            /** @var PaymentType $paymentType */
            $paymentType = $payment->paymentType;

            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_type' => $paymentType,
                'currency_id' => $payment->currency_id ?? null,
                'current_currency_rate' => $payment->currency_rate ?? null,
                'currency_amount' => $payment->currency_amount ?? null,
                'currency_symbol' => $payment->currency ? $payment->currency->symbol : null,
                'happened_at' => $payment->happened_at,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function getComplimentary(SaleItemComplimentary $complimentary, ?Employee $employee): array
    {
        return [
            'authorizer' => $employee instanceof Employee ? $employee->getFullName() : null,
            'amount' => $complimentary->amount,
        ];
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

    private function getPromoters(SaleItem $saleItem): ?array
    {
        if ($saleItem->promoters->isEmpty()) {
            return null;
        }

        return $saleItem->promoters->map(function ($promoter): array {
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
     * @return array<string, mixed>
     */
    private function getCreditNote(CreditNote $creditNote): array
    {
        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $creditNote->id,
            'counter_update_id' => $creditNote->counter_update_id,
            'cancel_credit_sale_id' => $creditNote->cancel_credit_sale_id,
            'user_type' => $userDataPreparer->getUserType($creditNote),
            'user_id' => $creditNote->member_id,
            'member_id' => $creditNote->member_id,
            'expiry_date' => $creditNote->expiry_date,
            'total_amount' => (float) $creditNote->total_amount,
            'available_amount' => (float) $creditNote->available_amount,
            'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
        ];
    }
}
