<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscountTransaction;

use App\CommonFunctions;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Models\HappyHourDiscountTransaction;

class HappyHourDiscountTransactionQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,happy_hour_discount_id,counter_update_id,offline_id,authorizer_id,authorizer_type,happened_at';
    }

    public function generateUniqueOfflineId(): string
    {
        $offlineId = CommonFunctions::getTwelveDigitNumber();

        $existOfflineId = HappyHourDiscountTransaction::whereCaseSensitive('offline_id', $offlineId)->exists();

        if ($existOfflineId) {
            return $this->generateUniqueOfflineId();
        }

        return $offlineId;
    }

    public function doesOfflineIdExist(string $offlineId, int $companyId): bool
    {
        $happyHourDiscountQueries = resolve(HappyHourDiscountQueries::class);

        return HappyHourDiscountTransaction::query()
            ->where('offline_id', $offlineId)
            ->whereHas('happyHourDiscount', $happyHourDiscountQueries->filterByCompany($companyId))
            ->exists();
    }

    public function addNew(int $happyHourDiscountId, array $happyHourDiscountDetails): void
    {
        HappyHourDiscountTransaction::create([
            'happy_hour_discount_id' => $happyHourDiscountId,
            'counter_update_id' => $happyHourDiscountDetails['counter_update_id'],
            'offline_id' => $happyHourDiscountDetails['offline_id'],
            'authorizer_id' => $happyHourDiscountDetails['authorizer_id'],
            'authorizer_type' => $happyHourDiscountDetails['authorizer_type'],
            'happened_at' => $happyHourDiscountDetails['happened_at'],
        ]);
    }
}
