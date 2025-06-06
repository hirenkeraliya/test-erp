<?php

declare(strict_types=1);

namespace App\Domains\PurchaseAmount;

use App\CommonFunctions;
use App\Models\PurchaseAmount;

class PurchaseAmountQueries
{
    public function addNewAndGetId(array $purchaseAmountDetails): int
    {
        $landedCost = CommonFunctions::numberFormat(
            $purchaseAmountDetails['fob'] +
            $purchaseAmountDetails['freight_charges'] +
            $purchaseAmountDetails['insurance_charges'] +
            $purchaseAmountDetails['duty'] +
            $purchaseAmountDetails['sst'] +
            $purchaseAmountDetails['handling_charges'] +
            $purchaseAmountDetails['other_charges']
        );

        return PurchaseAmount::firstOrCreate([
            'landed_cost' => $landedCost,
            'fob' => $purchaseAmountDetails['fob'],
            'freight_charges' => $purchaseAmountDetails['freight_charges'],
            'insurance_charges' => $purchaseAmountDetails['insurance_charges'],
            'duty' => $purchaseAmountDetails['duty'],
            'sst' => $purchaseAmountDetails['sst'],
            'handling_charges' => $purchaseAmountDetails['handling_charges'],
            'other_charges' => $purchaseAmountDetails['other_charges'],
        ])->id;
    }

    public function addBlankRecord(): int
    {
        return PurchaseAmount::firstOrCreate([
            'landed_cost' => 0,
            'fob' => null,
            'freight_charges' => null,
            'insurance_charges' => null,
            'duty' => null,
            'sst' => null,
            'handling_charges' => null,
            'other_charges' => null,
        ])->id;
    }

    public function getColumnNames(): string
    {
        return 'id,landed_cost,fob,freight_charges,insurance_charges,duty,sst,handling_charges,other_charges';
    }

    public function getLandedCostColumn(): string
    {
        return 'id,landed_cost';
    }
}
