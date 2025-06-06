<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Services;

use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Models\Cashier;
use App\Models\HappyHourDiscount;
use Illuminate\Support\Collection;

class HappyHourDiscountService
{
    public function addHappyHourDiscount(
        HappyHourDiscountDataForPos $happyHourDiscountDataForPos,
        int $companyId,
        int $counterUpdateId,
        int $locationId,
        Cashier $cashier,
    ): HappyHourDiscount {
        $happyHourDiscountQueries = resolve(HappyHourDiscountQueries::class);

        return $happyHourDiscountQueries->addNew(
            $happyHourDiscountDataForPos,
            $companyId,
            $cashier,
            $locationId,
            $counterUpdateId,
        );
    }

    public function saveHappyHourDiscountMismatches(
        Collection $happyHourDiscountMismatches,
        HappyHourDiscount $happyHourDiscount
    ): void {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        foreach ($happyHourDiscountMismatches as $happyHourDiscountMismatch) {
            $posMismatchQueries->addNew($happyHourDiscount, $happyHourDiscountMismatch);
        }
    }
}
