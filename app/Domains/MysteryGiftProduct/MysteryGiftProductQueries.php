<?php

declare(strict_types=1);

namespace App\Domains\MysteryGiftProduct;

use App\Models\MysteryGiftProduct;
use Illuminate\Support\Facades\DB;

class MysteryGiftProductQueries
{
    public function addNew(array $mysteryGiftProductDataRecord): void
    {
        MysteryGiftProduct::create($mysteryGiftProductDataRecord);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,mystery_gift_id,product_id,quantity';
    }

    public function getRandomProductId(int $mysteryGiftId): ?MysteryGiftProduct
    {
        return MysteryGiftProduct::select('mystery_gift_products.product_id')
            ->leftJoinSub(
                DB::table('mystery_gift_usages')
                    ->selectRaw('mystery_gift_id, product_id, COUNT(*) AS usage_count')
                    ->where('mystery_gift_id', $mysteryGiftId)
                    ->groupBy('mystery_gift_id', 'product_id'),
                'mgu',
                function ($join) use ($mysteryGiftId): void {
                    $join->on('mystery_gift_products.mystery_gift_id', '=', 'mgu.mystery_gift_id')
                        ->on('mystery_gift_products.product_id', '=', 'mgu.product_id')
                        ->where('mystery_gift_products.mystery_gift_id', $mysteryGiftId);
                }
            )
            ->whereRaw(
                'COALESCE(mgu.usage_count, 0) < mystery_gift_products.quantity OR mystery_gift_products.quantity = 0'
            )
            ->where('mystery_gift_products.mystery_gift_id', $mysteryGiftId)
            ->inRandomOrder()
            ->limit(1)
            ->first();
    }
}
