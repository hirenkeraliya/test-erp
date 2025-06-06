<?php

declare(strict_types=1);

namespace App\Domains\OrderItem\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\Color;
use App\Models\Employee;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class OrderReturnDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $order = $this->resource;

        /** @var Collection $orderItems */
        $orderItems = $order->orderItems;

        return [
            'id' => $order->id,
            'member_details' => $order->getMemberId() ? $this->getMemberDetails($order->getMember()) : [],
            'order_items' => $this->getPreparedOrderItems($orderItems),
            'type' => OrderTypes::getFormattedCaseName($order->getTypeId()->value),
        ];
    }

    public function getPromoters(OrderItem $orderItem): string
    {
        /** @var Collection $promoters */
        $promoters = $orderItem->promoters;

        return $promoters->map(function ($promoter): string {
            /** @var Promoter $orderItemPromoter */
            $orderItemPromoter = $promoter;

            /** @var Employee $employee */
            $employee = $orderItemPromoter->employee;

            return $employee->getFullName();
        })->implode(', ');
    }

    /**
     * @return mixed[]
     */
    private function getPreparedOrderItems(Collection $orderItems): array
    {
        return $orderItems->map(function (OrderItem $orderItem): array {
            /** @var Product $product */
            $product = $orderItem->product;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var Collection $orderReturnItems */
            $orderReturnItems = $orderItem->getOrderReturnItems();

            return [
                'id' => $orderItem->getKey(),
                'product' => $product->getName(),
                'product_id' => $product->id,
                'color' => $color instanceof Color ? $color->getName() : 'N/A',
                'size' => $size instanceof Size ? $size->getName() : 'N/A',
                'upc' => $product->getUpc(),
                'quantity' => CommonFunctions::truncateDecimal($orderItem->getQuantity()),
                'total_price_paid' => $orderItem->getTotalPricePaid(),
                'price_paid_per_unit' => $orderItem->getPricePaidPerUnit(),
                'promoters' => $this->getPromoters($orderItem),
                'is_returned' => $orderReturnItems->where('original_order_item_id', $orderItem->getKey())->count() > 0,
            ];
        })->toArray();
    }

    private function getMemberDetails(Member $member): array
    {
        return [
            'id' => $member->getKey(),
            'name' => $member->getFullName(),
        ];
    }
}
