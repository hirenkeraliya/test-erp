<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Resources;

use App\CommonFunctions;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StoreManagerAppSaleReturnDetailsResource extends JsonResource
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

        /** @var Member|null $member */
        $member = $saleReturn->member;

        /** @var Collection $saleReturnItems */
        $saleReturnItems = $saleReturn->saleReturnItems;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $saleReturn->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        return [
            'id' => $saleReturn->getKey(),
            'order_id' => $saleReturn->getOriginalSaleId(),
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

            return [
                'id' => $saleReturnItem->getKey(),
                'product' => $product->getName(),
                'upc' => $product->getUpc(),
                'article_number' => $product->getArticleNumber(),
                'quantity' => $saleReturnItem->getQuantity(),
                'unit_price' => $saleItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat(
                    $saleItem->getOriginalPricePerUnit() * $saleReturnItem->getQuantity()
                ),
                'total_discount_amount' => $saleReturnItem->getTotalDiscountAmount(),
                'total_tax_amount' => $saleReturnItem->getTotalTaxAmount(),
                'total_price_paid' => $saleReturnItem->getTotalPricePaid(),
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
