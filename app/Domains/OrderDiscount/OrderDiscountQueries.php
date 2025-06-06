<?php

declare(strict_types=1);

namespace App\Domains\OrderDiscount;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\OrderDiscount;

class OrderDiscountQueries
{
    public function addNew(
        int $orderId,
        int $discountableId,
        string $discountableType,
        float $amount,
        ?string $promoCode = null
    ): void {
        OrderDiscount::create([
            'order_id' => $orderId,
            'discountable_id' => $discountableId,
            'discountable_type' => $discountableType,
            'amount' => $amount,
            'promo_code' => $promoCode,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,discountable_id,discountable_type,order_id,amount,promo_code';
    }

    public function getVoucherIdByOrder(int $orderId): ?int
    {
        return OrderDiscount::select('id', 'discountable_id')
            ->where('discountable_type', ModelMapping::VOUCHER->name)
            ->where('order_id', $orderId)
            ->first()
            ?->discountable_id;
    }
}
