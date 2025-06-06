<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Resources;

use App\CommonFunctions;
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
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnReason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SaleReturnItemsReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var SaleReturn $saleReturn */
        $saleReturn = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $saleReturn->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var ?City $city */
        $city = $location->getCity();

        /** @var Company $company */
        $company = $location->company;

        /** @var Member|null $member */
        $member = $saleReturn->member;

        /** @var Collection $saleReturnItems */
        $saleReturnItems = $saleReturn->saleReturnItems;

        /** @var Collection $mismatches */
        $mismatches = $saleReturn->mismatches;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $saleReturn->getKey(),
            'offline_sale_return_id' => $saleReturn->getOfflineSaleReturnId(),
            'order_id' => $saleReturn->getOriginalSaleId(),
            'happened_at' => $happenedAt,
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
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'member_id' => null !== $member ? $member->getKey() : null,
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'total_tax_amount' => $saleReturn->getTotalTaxAmount(),
            'total_discount_amount' => $saleReturn->getTotalDiscountAmount(),
            'return_amount' => $saleReturn->getTotalPricePaid(),
            'units_returned' => $this->getTotalUnitsReturned($saleReturnItems),
            'round_off' => $saleReturn->getRoundOffAmount(),
            'sale_return_items' => $this->getPreparedSaleReturnItems($saleReturnItems),
            'sale_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPreparedSaleReturnItems(Collection $saleReturnItems): array
    {
        return $saleReturnItems->map(function ($saleReturnItem): array {
            /** @var SaleItem $saleItem */
            $saleItem = $saleReturnItem->saleItem;
            /** @var Product $product */
            $product = $saleReturnItem->product;

            /** @var SaleReturnReason $saleReturnReason */
            $saleReturnReason = $saleReturnItem->saleReturnReason;

            return [
                'id' => $saleReturnItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
                'quantity' => $saleReturnItem->getQuantity(),
                'unit_price' => $saleItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat(
                    $saleItem->getOriginalPricePerUnit() * $saleReturnItem->getQuantity()
                ),
                'total_discount_amount' => $saleReturnItem->getTotalDiscountAmount(),
                'total_tax_amount' => $saleReturnItem->getTotalTaxAmount(),
                'total_price_paid' => $saleReturnItem->getTotalPricePaid(),
                'sale_return_reason' => $saleReturnReason->getReason(),
                'put_back_in_inventory' => $saleReturnReason->getPutBackInInventory(),
                'promoters' => Promoter::getPromoters($saleItem),
            ];
        })->toArray();
    }

    private function getTotalUnitsReturned(Collection $saleReturnItems): float
    {
        $totalUnitsReturned = $saleReturnItems->sum(fn ($saleReturnItem): ?float => $saleReturnItem->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsReturned);
    }
}
