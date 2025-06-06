<?php

declare(strict_types=1);

namespace App\Domains\OrderLoyaltyPoint;

use App\Models\OrderLoyaltyPoint;

class OrderLoyaltyPointQueries
{
    public function addNew(int $loyaltyPoints, float $amount, int $orderId): OrderLoyaltyPoint
    {
        return OrderLoyaltyPoint::create([
            'loyalty_points' => $loyaltyPoints,
            'amount' => $amount,
            'order_id' => $orderId,
        ]);
    }
}
