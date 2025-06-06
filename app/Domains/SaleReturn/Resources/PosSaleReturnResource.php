<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Resources;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\BoxProduct;
use App\Models\Cashier;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemPriceOverride;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosSaleReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleReturn = $this->resource;

        /** @var Collection $saleReturnMismatches */
        $saleReturnMismatches = $saleReturn->mismatches;
        $messages = $saleReturnMismatches->pluck('message')->toArray();

        /** @var Collection $saleReturnItems */
        $saleReturnItems = $saleReturn->getSaleReturnItems();

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $saleReturn->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var CreditNote|null $creditNote */
        $creditNote = $saleReturn->creditNote;

        /** @var Collection $loyaltyPointUpdates */
        $loyaltyPointUpdates = $saleReturn->loyaltyPointUpdates;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $saleReturn->getKey(),
            'offline_sale_return_id' => $saleReturn->offline_sale_return_id,
            'user_type' => $userDataPreparer->getUserType($saleReturn),
            'user_id' => $saleReturn->member_id,
            'user_details' => $userDataPreparer->getUserDetails(
                $saleReturn->member,
                $loyaltyPointUpdates,
                collect([]),
            ),
            'member_id' => $saleReturn->member_id,
            'member' => $userDataPreparer->getUserDetails($saleReturn->member, $loyaltyPointUpdates, collect([])),
            'cashier' => [
                'id' => $cashier->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ],
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->getName(),
            ],
            'total_tax_amount' => (float) $saleReturn->total_tax_amount,
            'cart_discount_amount' => (float) $saleReturn->cart_discount_amount,
            'items_discount_amount' => (float) $saleReturn->items_discount_amount,
            'total_discount_amount' => (float) $saleReturn->total_discount_amount,
            'total_amount_paid' => (float) $saleReturn->total_price_paid,
            'round_off_amount' => (float) $saleReturn->round_off_amount,
            'total_amount_before_round_off' => (float) $saleReturn->total_amount_before_round_off,
            'sale_return_items' => $this->getPreparedSaleReturnItems($saleReturnItems),
            'notes' => $saleReturn->notes,
            'happened_at' => $saleReturn->happened_at,
            'has_mismatch' => $saleReturn->has_mismatch,
            'sale_return_mismatches' => $messages,
            'credit_note' => $creditNote instanceof CreditNote ? $this->getCreditNote($creditNote) : null,
        ];
    }

    public function getPromoters(Collection $promoters): ?array
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
            ];
        })->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getCreditNote(CreditNote $creditNote): array
    {
        /** @var Collection $creditNoteRefundMismatches */
        $creditNoteRefundMismatches = $creditNote->mismatches;
        $messages = $creditNoteRefundMismatches->pluck('message')->toArray();

        /** @var ?SaleReturn $saleReturn */
        $saleReturn = $creditNote->saleReturn;

        /** @var ?Sale $sale */
        $sale = $saleReturn?->originalSale;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $creditNote->id,
            'counter_update_id' => $creditNote->counter_update_id,
            'sale_return_id' => $creditNote->sale_return_id,
            'sale_return_receipt_number' => $saleReturn?->offline_sale_return_id,
            'original_sale_receipt_number' => $sale?->offline_sale_id,
            'user_type' => $userDataPreparer->getUserType($creditNote),
            'user_id' => $creditNote->member_id,
            'member_id' => $creditNote->member_id,
            'expiry_date' => $creditNote->expiry_date,
            'total_amount' => (float) $creditNote->total_amount,
            'available_amount' => (float) $creditNote->available_amount,
            'credit_note_refund_mismatches' => $messages,
            'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
        ];
    }

    private function getPreparedSaleReturnItems(Collection $saleReturnItems): Collection
    {
        return $saleReturnItems->map(function ($item): array {
            /** @var SaleReturnItem $saleReturnItem */
            $saleReturnItem = $item;

            /** @var Product $product */
            $product = $saleReturnItem->product;

            /** @var SaleReturnReason $saleReturnReason */
            $saleReturnReason = $saleReturnItem->saleReturnReason;

            /** @var SaleItem $saleItem */
            $saleItem = $saleReturnItem->saleItem;

            /** @var ?BoxProduct $boxProduct */
            $boxProduct = $saleItem->boxProduct;

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
                    'has_batch' => $masterProduct->has_batch,
                    'is_non_inventory' => $masterProduct->is_non_inventory,
                ];
            }

            return [
                'id' => $saleReturnItem->getKey(),
                'product_id' => $saleReturnItem->product_id,
                'product' => $this->getPreparedProduct($product, $masterProductData),
                'sale_return_reason' => $saleReturnReason,
                'quantity' => (float) $saleReturnItem->quantity,
                'total_price_paid' => (float) $saleReturnItem->total_price_paid,
                'cart_discount_amount' => (float) $saleReturnItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleReturnItem->item_discount_amount,
                'total_discount_amount' => (float) $saleReturnItem->total_discount_amount,
                'total_tax_amount' => (float) $saleReturnItem->total_tax_amount,
                'original_price_per_unit' => (float) $saleItem->original_price_per_unit,
                'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'box' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
                'sale_items' => $this->getPreparedSaleItems($saleReturnItem),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function getPreparedSaleItems(SaleReturnItem $saleReturnItem): array
    {
        /** @var SaleItem $saleItem */
        $saleItem = $saleReturnItem->saleItem;

        /** @var ?BoxProduct $boxProduct */
        $boxProduct = $saleItem->boxProduct;

        /** @var Collection $saleItemDiscounts */
        $saleItemDiscounts = $saleItem->saleItemDiscounts;

        /** @var ?SaleItemComplimentary $saleItemComplimentary */
        $saleItemComplimentary = $saleItem->saleItemComplimentary;

        if ($saleItemComplimentary instanceof SaleItemComplimentary) {
            /** @var StoreManager|Director $saleItemComplimentaryAuthorizer */
            $saleItemComplimentaryAuthorizer = $saleItemComplimentary->authorizer;

            /** @var Employee $employee */
            $employee = $saleItemComplimentaryAuthorizer->employee;
        }

        /** @var Collection $promoters */
        $promoters = $saleItem->promoters;

        return [
            'id' => $saleItem->getKey(),
            'complimentary' => $saleItemComplimentary instanceof SaleItemComplimentary ? [
                'authorizer' => $employee->getFullName(),
                'amount' => $saleItemComplimentary->amount,
            ] : null,
            'is_exchange' => $saleItem->is_exchange,
            'promoters' => $this->getPromoters($promoters),
            'bundle' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
            'box' => $boxProduct ? $this->getPreparedBoxProduct($boxProduct) : null,
            'sale_item_discounts' => $saleItemDiscounts->map(function ($saleItemDiscount): array {
                /** @var Promotion|DreamPrice|ComplimentaryItemReason|SaleItemPriceOverride $discountable */
                $discountable = $saleItemDiscount->discountable;

                return [
                    'discountable_type' => $saleItemDiscount->discountable_type,
                    'name' => $discountable->getName(),
                ];
            }),
        ];
    }

    private function getPreparedProduct(Product $product, ?array $masterProductData): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'upc' => $product->upc,
            'has_batch' => $product->has_batch,
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'color' => $product->color,
            'size' => $product->size,
            'article_number' => $product->article_number,
            'ean' => $product->ean,
            'is_non_inventory' => $product->is_non_inventory,
            'type_id' => [
                'id' => $product->type_id,
                'name' => ProductTypes::getFormattedCaseName($product->type_id),
                'key' => ProductTypes::getCaseNameByValue($product->type_id),
            ],
            'compound_product_name' => $product->compound_product_name,
            'master_product' => $masterProductData,
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
