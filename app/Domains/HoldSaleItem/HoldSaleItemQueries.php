<?php

declare(strict_types=1);

namespace App\Domains\HoldSaleItem;

use App\CommonFunctions;
use App\Domains\Sale\SaleQueries;
use App\Models\HoldSaleItem;

class HoldSaleItemQueries
{
    public function addNew(int $holdSaleDetailId, array $item): void
    {
        HoldSaleItem::create([
            'hold_sale_detail_id' => $holdSaleDetailId,
            'product_id' => $item['id'],
            'derivative_id' => array_key_exists(
                'derivative_id',
                $item
            ) && $item['derivative_id'] ? $item['derivative_id'] : null,
            'quantity' => $item['quantity'],
            'group_id' => array_key_exists('group_id', $item) && $item['group_id'] ? $item['group_id'] : null,
            'original_price_per_unit' => array_key_exists(
                'original_price_per_unit',
                $item
            ) ? CommonFunctions::numberFormat((float) $item['original_price_per_unit']) : 0.00,
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
            'price_paid_per_unit' => array_key_exists('price_paid_per_unit', $item) ? CommonFunctions::numberFormat(
                (float) $item['price_paid_per_unit']
            ) : 0.00,
            'total_price_paid' => array_key_exists('total_price_paid', $item) ? CommonFunctions::numberFormat(
                (float) $item['total_price_paid']
            ) : 0.00,
            'is_exchange' => array_key_exists('is_exchange', $item) && $item['is_exchange'],
        ]);
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,hold_sale_detail_id,product_id,derivative_id,quantity,original_sale_item_id,returned_quantity,original_price_per_unit,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,price_paid_per_unit,total_price_paid,group_id,is_exchange';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $saleQueries = resolve(SaleQueries::class);

        $holdSaleItems = HoldSaleItem::query()
            ->select('id', 'hold_sale_detail_id', 'product_id', 'original_sale_item_id')
            ->whereHas('saleItem', function ($query) use ($companyId, $saleQueries): void {
                $query->whereHas('sale', $saleQueries->filterByCompanyId($companyId));
            })
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($holdSaleItems as $holdSaleItem) {
            $holdSaleItem->product_id = $newProductId;
            $holdSaleItem->save();
        }
    }
}
