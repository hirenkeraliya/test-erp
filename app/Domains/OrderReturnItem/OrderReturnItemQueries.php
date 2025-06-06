<?php

declare(strict_types=1);

namespace App\Domains\OrderReturnItem;

use App\CommonFunctions;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Models\OrderReturnItem;
use Closure;

class OrderReturnItemQueries
{
    public function addNew(
        int $saleReturnReasonId,
        int $saleReturnId,
        int $originalSaleItemId,
        int $productId,
        float $quantity,
        float $totalPricePaid,
        float $itemTax,
        float $itemCartDiscount,
        float $itemDiscountAmount,
        float $totalDiscountAmount
    ): OrderReturnItem {
        return OrderReturnItem::create([
            'order_return_id' => $saleReturnId,
            'original_order_item_id' => $originalSaleItemId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'total_price_paid' => CommonFunctions::numberFormat($totalPricePaid),
            'cart_discount_amount' => CommonFunctions::numberFormat($itemCartDiscount),
            'item_discount_amount' => CommonFunctions::numberFormat($itemDiscountAmount),
            'total_discount_amount' => CommonFunctions::numberFormat($totalDiscountAmount),
            'total_tax_amount' => CommonFunctions::numberFormat($itemTax),
            'order_return_reason_id' => $saleReturnReasonId,
        ]);
    }

    public static function getColumnNames(): string
    {
        return 'id,order_return_id,original_order_item_id,product_id,order_return_reason_id,quantity,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,total_price_paid';
    }

    public static function getColumnNamesForOrderUpdate(): string
    {
        return 'id,order_return_id,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,total_price_paid';
    }

    public function getSumOfPriceAndQuantity(): Closure
    {
        return fn ($query) => $query->select('id', 'order_return_id', 'total_price_paid', 'quantity')
            ->selectRaw('SUM(total_price_paid) as total_order_returns_amount')
            ->selectRaw('SUM(quantity) as total_units_sold')
            ->selectRaw('COUNT(DISTINCT order_return_id) as total_order_returns');
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $orderReturnQueries = resolve(OrderReturnQueries::class);

        $orderReturnItems = OrderReturnItem::query()
            ->select('id', 'order_return_id', 'product_id')
            ->whereHas('orderReturn', $orderReturnQueries->filterByCompanyId($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($orderReturnItems as $orderReturnItem) {
            $orderReturnItem->product_id = $newProductId;
            $orderReturnItem->save();
        }
    }

    public function getOfflineOrderReturnWithRelation(): Closure
    {
        $orderReturnQueries = resolve(OrderReturnQueries::class);

        return fn ($query) => $query->select('id', 'order_return_id')
            ->with(['orderReturn:' . $orderReturnQueries->getOfflineOrderReturnId()]);
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }
}
