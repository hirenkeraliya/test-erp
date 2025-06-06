<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataPreparer;

use App\Models\SaleItem;

class SaleItemDataPreparer
{
    public function getDerivative(SaleItem $saleItem): ?array
    {
        if ($saleItem->derivatives) {
            $unitOfMeasureDerivative = $saleItem->derivatives;

            return [
                'id' => $unitOfMeasureDerivative->id,
                'name' => $unitOfMeasureDerivative->name,
                'ratio' => $unitOfMeasureDerivative->ratio,
                'price_based_on_derivative' => $saleItem->price_based_on_derivative,
                'quantity_of_derivative' => $saleItem->quantity_of_derivative,
                'price_paid_of_derivative' => $saleItem->price_paid_of_derivative,
            ];
        }

        return null;
    }
}
