<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Resources;

use App\CommonFunctions;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\SaleReturnReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class OrderReturnItemsReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var OrderReturn $orderReturn */
        $orderReturn = $this;

        /** @var Collection $orderReturnItems */
        $orderReturnItems = $orderReturn->orderReturnItems;

        return [
            'id' => $orderReturn->getKey(),
            'member_details' => $orderReturn->getMemberId() ? $this->getMemberDetails($orderReturn->getMember()) : [],
            'order_id' => $orderReturn->getOriginalOrderId(),
            'total_tax_amount' => $orderReturn->getTotalTaxAmount(),
            'total_discount_amount' => $orderReturn->getTotalDiscountAmount(),
            'return_amount' => $orderReturn->getTotalPricePaid(),
            'units_returned' => $this->getTotalUnitsReturned($orderReturnItems),
            'round_off' => $orderReturn->getRoundOffAmount(),
            'order_return_items' => $this->getPreparedOrderReturnItems($orderReturnItems),
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPreparedOrderReturnItems(Collection $orderReturnItems): array
    {
        return $orderReturnItems->map(function ($orderReturnItem): array {
            /** @var OrderItem $orderItem */
            $orderItem = $orderReturnItem->getOrderItem();

            /** @var Product $product */
            $product = $orderReturnItem->getProduct();

            /** @var SaleReturnReason $orderReturnReason */
            $orderReturnReason = $orderReturnItem->orderReturnReason;

            return [
                'id' => $orderReturnItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
                'quantity' => $orderReturnItem->getQuantity(),
                'unit_price' => $orderItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat(
                    $orderItem->getOriginalPricePerUnit() * $orderReturnItem->getQuantity()
                ),
                'total_discount_amount' => $orderReturnItem->getTotalDiscountAmount(),
                'total_tax_amount' => $orderReturnItem->getTotalTaxAmount(),
                'total_price_paid' => $orderReturnItem->getTotalPricePaid(),
                'order_return_reason' => $orderReturnReason->getReason(),
                'put_back_in_inventory' => $orderReturnReason->getPutBackInInventory(),
            ];
        })->toArray();
    }

    private function getTotalUnitsReturned(Collection $orderReturnItems): float
    {
        $totalUnitsReturned = $orderReturnItems->sum(fn ($orderReturnItem): ?float => $orderReturnItem->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsReturned);
    }

    private function getMemberDetails(?Member $member): array
    {
        if (! $member instanceof Member) {
            return [];
        }

        return [
            'id' => $member->getKey(),
            'name' => $member->getFullName(),
        ];
    }
}
