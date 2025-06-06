<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\CommonFunctions;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes as SaleItemDiscountableTypes;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleDiscount;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CreditSaleItemsReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Sale $sale */
        $sale = $this;

        /** @var Collection $saleItems */
        $saleItems = $sale->saleItems;

        /** @var Collection $saleDiscounts */
        $saleDiscounts = $sale->saleDiscounts;

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var Collection $mismatches */
        $mismatches = $sale->mismatches;

        return [
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotal()),
            'total_discount_amount' => $sale->getTotalDiscountAmount(),
            'total_tax_amount' => $sale->getTotalTaxAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'credit_pending_amount' => $sale->getCreditPendingAmount(),
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'sale_discounts' => SaleDiscount::getPreparedSaleDiscounts($saleDiscounts),
            'payments' => SalePayment::getPreparedPayments($salePayments),
            'sale_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPreparedSaleItems(Collection $saleItems): array
    {
        return $saleItems->map(function ($saleItem): array {
            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            /** @var Product $product */
            $product = $saleItem->product;

            return [
                'id' => $saleItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
                'quantity' => $saleItem->getQuantity(),
                'unit_price' => $saleItem->getPricePaidPerUnit(),
                'subtotal' => CommonFunctions::numberFormat($saleItem->getSubTotal()),
                'total_discount_amount' => $saleItem->getTotalDiscountAmount(),
                'total_tax_amount' => $saleItem->getTotalTaxAmount(),
                'total_price_paid' => $saleItem->getTotalPricePaid(),
                'sale_item_discounts' => $this->getSaleItemDiscountDetails($saleItemDiscounts),
                'promoters' => Promoter::getPromoters($saleItem),
            ];
        })->toArray();
    }

    private function getSaleItemDiscountDetails(Collection $saleItemDiscounts): string
    {
        $text = '';

        foreach ($saleItemDiscounts as $saleItemDiscount) {
            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::PROMOTION->value) {
                $text = 'Promotion: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::DREAM_PRICE->value) {
                $text .= 'Dream Price: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::COMPLIMENTARY_ITEM_REASON->value) {
                $text .= 'Complimentary Item: ' . $saleItemDiscount->getAmount();
            }
        }

        return $text;
    }
}
