<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;

class SellThroughAggregateServices
{
    public function updateProductIdDuringProductMerge(int $oldProductId, int $newProductId): void
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $sellThroughAggregateWithOldAndNewProducts = $sellThroughAggregateQueries->getByOldOrNewProductId(
            $oldProductId,
            $newProductId
        );

        $sellThroughAggregateWithOldAndNewProducts->groupBy(
            fn ($item): string => $item->date . '_' . $item->location_id
        )->each(
            function ($group) use ($oldProductId, $newProductId, $sellThroughAggregateQueries): void {
                $oldProduct = $group->firstWhere('product_id', $oldProductId);
                $newProduct = $group->firstWhere('product_id', $newProductId);

                if ($newProduct && $oldProduct) {
                    $sellThroughAggregateQueries->updateTheNumberColumnsAndDeleteOldProduct($oldProduct, $newProduct);
                } elseif ($oldProduct) {
                    $sellThroughAggregateQueries->updateOldProductToNewProduct($oldProduct, $newProductId);
                }
            }
        );
    }
}
