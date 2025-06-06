<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\CommonFunctions;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes as SaleItemDiscountableTypes;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\City;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SaleItemsReportResource extends JsonResource
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

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var ?City $city */
        $city = $location->getCity();

        /** @var Company $company */
        $company = $location->company;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Member|null $member */
        $member = $sale->member;

        /** @var Collection $saleItems */
        $saleItems = $sale->saleItems;

        /** @var Collection $saleDiscounts */
        $saleDiscounts = $sale->saleDiscounts;

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var Collection $mismatches */
        $mismatches = $sale->mismatches;

        /** @var ?SaleCashback $saleCashback */
        $saleCashback = $sale->cashback;
        $cashback = null;

        if ($saleCashback instanceof SaleCashback) {
            /** @var ?Cashback $cashback */
            $cashback = $saleCashback->cashbackConfiguration;
        }

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->getOfflineSaleId(),
            'location' => [
                'id' => $location->getKey(),
                'name' => $location->getName(),
                'address_line_1' => $location->getAddressLine1(),
                'address_line_2' => $location->getAddressLine2(),
                'city' => $city?->name,
                'area_code' => $location->getAreaCode(),
                'receipt_footer' => $location->getReceiptFooter(),
                'disclaimer' => $location->getDisclaimer(),
            ],
            'company' => [
                'id' => $company->getKey(),
                'name' => $company->getName(),
                'social_security_number' => $company->getSocialSecurityNumber(),
                'logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            ],
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'happened_at' => $happenedAt,
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'member_id' => null !== $member ? $member->getKey() : null,
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotal()),
            'total_tax_amount' => $sale->getTotalTaxAmount(),
            'total_discount_amount' => $sale->getTotalDiscountAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'net_total' => $sale->getTotalAmountBeforeRoundOff(),
            'units_sold' => $this->getTotalUnitsSold($saleItems),
            'units_returned' => $this->getTotalUnitsReturned($saleItems),
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'sale_discounts' => SaleDiscount::getPreparedSaleDiscounts($saleDiscounts),
            'round_off' => $sale->getRoundOff(),
            'payments' => SalePayment::getPreparedPayments($salePayments),
            'sale_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
            'cashback' => $saleCashback instanceof SaleCashback ? [
                [
                    'id' => $saleCashback->id,
                    'name' => $cashback instanceof Cashback ? $cashback->name : null,
                    'amount' => $saleCashback->amount,
                ],
            ] : null,
            'cashback_amount' => $saleCashback instanceof SaleCashback ? $saleCashback->amount : null,
        ];
    }

    private function getTotalUnitsSold(Collection $saleItems): float
    {
        $totalUnitsSold = $saleItems->sum(fn ($saleItem): ?float => $saleItem->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsSold);
    }

    private function getTotalUnitsReturned(Collection $saleItems): float
    {
        $totalUnitsReturned = $saleItems->sum(fn ($saleItem): ?float => $saleItem->getReturnedQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsReturned);
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
                'original_price_per_unit' => $saleItem->getOriginalPricePerUnit(),
                'sale_item_discounts' => $this->getSaleItemDiscountDetails($saleItemDiscounts),
                'promoters' => Promoter::getPromoters($saleItem),
            ];
        })->toArray();
    }

    private function getSaleItemDiscountDetails(?Collection $saleItemDiscounts): string
    {
        $text = '';

        if (! $saleItemDiscounts instanceof Collection) {
            return $text;
        }

        foreach ($saleItemDiscounts as $saleItemDiscount) {
            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::PROMOTION->name) {
                $text = 'Promotion: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::DREAM_PRICE->name) {
                $text .= 'Dream Price: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::COMPLIMENTARY_ITEM_REASON->name) {
                $text .= 'Complimentary Item: ' . $saleItemDiscount->getAmount();
            }

            if ($saleItemDiscount->getDiscountableType() === SaleItemDiscountableTypes::SALE_ITEM_PRICE_OVERRIDE->name) {
                $text .= 'Sale Item Price override: ' . $saleItemDiscount->getAmount() . "\n";
            }
        }

        return $text;
    }
}
