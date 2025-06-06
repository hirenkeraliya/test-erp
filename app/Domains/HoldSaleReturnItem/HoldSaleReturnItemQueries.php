<?php

declare(strict_types=1);

namespace App\Domains\HoldSaleReturnItem;

use App\CommonFunctions;
use App\Domains\Sale\SaleQueries;
use App\Models\HoldSaleReturnItem;

class HoldSaleReturnItemQueries
{
    public function addNew(int $holdSaleDetailId, array $item): void
    {
        HoldSaleReturnItem::create([
            'hold_sale_detail_id' => $holdSaleDetailId,
            'sale_item_id' => $item['sale_item_id'],
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'sale_return_reason_id' => array_key_exists(
                'sale_return_reason_id',
                $item
            ) && $item['sale_return_reason_id'] ? $item['sale_return_reason_id'] : null,
            'total_price_paid' => array_key_exists(
                'total_price_paid',
                $item
            ) ? CommonFunctions::numberFormat((float) $item['total_price_paid']) : 0.00,
            'cart_discount_amount' => array_key_exists('cart_discount_amount', $item) ? CommonFunctions::numberFormat(
                (float) $item['cart_discount_amount']
            ) : 0.00,
            'item_discount_amount' => array_key_exists('item_discount_amount', $item) ? CommonFunctions::numberFormat(
                (float) $item['item_discount_amount']
            ) : 0.00,
            'total_discount_amount' => array_key_exists('total_discount_amount', $item) ? CommonFunctions::numberFormat(
                (float) $item['total_discount_amount']
            ) : 0.00,
            'total_tax_amount' => array_key_exists('total_tax_amount', $item) ? CommonFunctions::numberFormat(
                (float) $item['total_tax_amount']
            ) : 0.00,
        ]);
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,hold_sale_detail_id,sale_item_id,product_id,quantity,sale_return_reason_id,total_price_paid,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $saleQueries = resolve(SaleQueries::class);

        $holdSaleReturnItems = HoldSaleReturnItem::query()
            ->select('id', 'hold_sale_detail_id', 'sale_item_id', 'product_id')
            ->whereHas('saleItem', function ($query) use ($companyId, $saleQueries): void {
                $query->whereHas('sale', $saleQueries->filterByCompanyId($companyId));
            })
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($holdSaleReturnItems as $holdSaleReturnItem) {
            $holdSaleReturnItem->product_id = $newProductId;
            $holdSaleReturnItem->save();
        }
    }
}
