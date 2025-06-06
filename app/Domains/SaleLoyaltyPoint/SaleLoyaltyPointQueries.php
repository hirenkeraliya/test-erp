<?php

declare(strict_types=1);

namespace App\Domains\SaleLoyaltyPoint;

use App\Models\SaleLoyaltyPoint;

class SaleLoyaltyPointQueries
{
    public function addNew(int $loyaltyPoints, float $amount, ?int $saleId, ?int $productId): SaleLoyaltyPoint
    {
        return SaleLoyaltyPoint::create([
            'loyalty_points' => $loyaltyPoints,
            'amount' => $amount,
            'sale_id' => $saleId,
            'product_id' => $productId,
        ]);
    }
}
