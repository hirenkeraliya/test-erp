<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\CommonFunctions;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StoreManagerAppSaleDetailsResource extends JsonResource
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

        /** @var Member|null $member */
        $member = $sale->member;

        /** @var Collection $saleItems */
        $saleItems = $sale->saleItems;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        return [
            'id' => $sale->getKey(),
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'member_id' => null !== $member ? $member->getKey() : null,
            'total_tax_amount' => $sale->getTotalTaxAmount(),
            'total_discount_amount' => $sale->getTotalDiscountAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'net_total' => $sale->getTotalAmountBeforeRoundOff(),
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'round_off' => $sale->getRoundOff(),
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'payments' => SalePayment::getPreparedPayments($salePayments),
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPreparedSaleItems(Collection $saleItems): array
    {
        return $saleItems->map(function ($saleItem): array {
            /** @var Product $product */
            $product = $saleItem->product;

            return [
                'id' => $saleItem->getKey(),
                'product' => $product->getName(),
                'upc' => $product->getUpc(),
                'article_number' => $product->getArticleNumber(),
                'quantity' => $saleItem->getQuantity(),
                'unit_price' => $saleItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat($saleItem->getSubTotal()),
                'total_discount_amount' => $saleItem->getTotalDiscountAmount(),
                'total_tax_amount' => $saleItem->getTotalTaxAmount(),
                'total_price_paid' => $saleItem->getTotalPricePaid(),
                'original_price_per_unit' => $saleItem->getOriginalPricePerUnit(),
                'promoters' => Promoter::getPromoters($saleItem),
            ];
        })->toArray();
    }
}
