<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Services;

use App\Domains\HoldSaleReturnItem\HoldSaleReturnItemQueries;

class SaveHoldSaleReturnDetailsService
{
    public function saveSaleReturnDetails(
        int $holdSaleDetailId,
        CheckHoldSaleDetailsService $checkHoldSaleDetailsService,
    ): void {
        $holdSaleReturnService = $checkHoldSaleDetailsService->holdSaleReturnService;
        if (! $holdSaleReturnService->hasReturnItems()) {
            return;
        }

        $holdSaleReturnItemQueries = resolve(HoldSaleReturnItemQueries::class);
        foreach ($holdSaleReturnService->returnItems as $returnItem) {
            $returnedSaleItem = $holdSaleReturnService->returnedSaleItems->firstWhere(
                'id',
                $returnItem['sale_item_id']
            );

            foreach ($returnItem['sale_return_details'] as $returnItemDetails) {
                $returnItemDetails['sale_item_id'] = $returnedSaleItem->id;
                $returnItemDetails['product_id'] = $returnedSaleItem->product_id;
                $holdSaleReturnItemQueries->addNew($holdSaleDetailId, $returnItemDetails);
            }
        }
    }
}
