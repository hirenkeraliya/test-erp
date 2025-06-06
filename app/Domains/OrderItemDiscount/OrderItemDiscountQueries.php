<?php

declare(strict_types=1);

namespace App\Domains\OrderItemDiscount;

use App\Models\OrderItemDiscount;

class OrderItemDiscountQueries
{
    public function addNew(
        int $orderItemId,
        int $discountableId,
        string $discountableType,
        float $amount,
        ?string $promoCode = null
    ): void {
        OrderItemDiscount::create([
            'order_item_id' => $orderItemId,
            'discountable_id' => $discountableId,
            'discountable_type' => $discountableType,
            'amount' => $amount,
            'promo_code' => $promoCode,
        ]);
    }
}
