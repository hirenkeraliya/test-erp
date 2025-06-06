<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingListItem;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderPickingList\OrderPickingListQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\OrderPickingListItem;
use Closure;
use Illuminate\Support\Collection;

class OrderPickingListItemQueries
{
    public function getOrderPickingListForOrder(int $orderPickingListId, int $companyId): Collection
    {
        $orderQueries = resolve(OrderQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $orderPickingListQueries = resolve(OrderPickingListQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return OrderPickingListItem::query()
                ->select('id', 'order_id', 'order_picking_list_id')
                ->with([
                    'orderPickingList:' . $orderPickingListQueries->getBasicColumnNames(),
                    'order:' . $orderQueries->getBasicColumnsForMarketPlaceOrder(),
                    'order.orderItems:' . $orderItemQueries->getBasicColumnNames(),
                    'order.orderItems.product:' . $productQueries->getBasicColumnNames(),
                    'order.orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'order.orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'order.member',
                ])
                ->where('order_picking_list_id', $orderPickingListId)
                ->whereHas('orderPickingList', $orderPickingListQueries->filterByCompanyId($companyId))
                ->get();
        }

        return OrderPickingListItem::query()
            ->select('id', 'order_id', 'order_picking_list_id')
            ->with([
                'orderPickingList:' . $orderPickingListQueries->getBasicColumnNames(),
                'order:' . $orderQueries->getBasicColumnsForMarketPlaceOrder(),
                'order.orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'order.orderItems.product:' . $productQueries->getBasicColumnNames(),
                'order.orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'order.orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'order.member',
            ])
            ->where('order_picking_list_id', $orderPickingListId)
            ->whereHas('orderPickingList', $orderPickingListQueries->filterByCompanyId($companyId))
            ->get();
    }

    public function filterByOrderPickingListId(int $orderPickingListId): Closure
    {
        return fn ($query) => $query->select('id', 'order_id')->where('order_picking_list_id', $orderPickingListId);
    }

    public function getOrderPickingListForOrderIds(int $orderPickingListId, int $companyId): Collection
    {
        $orderPickingListQueries = resolve(OrderPickingListQueries::class);

        return OrderPickingListItem::query()
            ->select('id', 'order_id')
            ->where('order_picking_list_id', $orderPickingListId)
            ->whereHas('orderPickingList', $orderPickingListQueries->filterByCompanyId($companyId))
            ->get();
    }
}
