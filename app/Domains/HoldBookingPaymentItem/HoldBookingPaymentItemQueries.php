<?php

declare(strict_types=1);

namespace App\Domains\HoldBookingPaymentItem;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Models\HoldBookingPaymentItem;

class HoldBookingPaymentItemQueries
{
    public function addNew(int $holdSaleDetailId, array $item): void
    {
        HoldBookingPaymentItem::create([
            'hold_sale_detail_id' => $holdSaleDetailId,
            'product_id' => $item['id'],
            'quantity' => $item['quantity'],
        ]);
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,hold_sale_detail_id,product_id,quantity';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $holdBookingPaymentItems = HoldBookingPaymentItem::query()
            ->select('id', 'hold_sale_detail_id', 'product_id')
            ->whereHas(
                'holdSaleDetail',
                function ($query) use ($counterUpdateQueries, $companyId): void {
                    $query->select('id', 'hold_sale_id')
                        ->whereHas('holdSale', function ($query) use ($counterUpdateQueries, $companyId): void {
                            $query->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
                        });
                }
            )
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($holdBookingPaymentItems as $holdBookingPaymentItem) {
            $holdBookingPaymentItem->product_id = $newProductId;
            $holdBookingPaymentItem->save();
        }
    }
}
