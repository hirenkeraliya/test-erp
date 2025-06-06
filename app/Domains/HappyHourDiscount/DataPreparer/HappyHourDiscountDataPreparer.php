<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\DataPreparer;

use App\Models\Director;
use App\Models\Employee;
use App\Models\HappyHourDiscountTransaction;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HappyHourDiscountDataPreparer
{
    public static function getOfflineIds(Collection $happyHourDiscountTransactions): array
    {
        if ($happyHourDiscountTransactions->isEmpty()) {
            return [];
        }

        return $happyHourDiscountTransactions->pluck('offline_id')->toArray();
    }

    public static function getHappenedAtDatesForApi(Collection $happyHourDiscountTransactions): array
    {
        if ($happyHourDiscountTransactions->isEmpty()) {
            return [];
        }

        return $happyHourDiscountTransactions->pluck('happened_at')->toArray();
    }

    public static function getHappenedAtDates(Collection $happyHourDiscountTransactions): array
    {
        if ($happyHourDiscountTransactions->isEmpty()) {
            return [];
        }

        return $happyHourDiscountTransactions->map(
            function (HappyHourDiscountTransaction $happyHourDiscountTransaction): string {
                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happyHourDiscountTransaction->happened_at);

                return $happenedAtFormat->format('d-m-Y h:i:s A');
            }
        )->toArray();
    }

    public static function getAuthorizerNames(Collection $happyHourDiscountTransactions): array
    {
        if ($happyHourDiscountTransactions->isEmpty()) {
            return [];
        }

        return $happyHourDiscountTransactions->map(
            function (HappyHourDiscountTransaction $happyHourDiscountTransaction): ?string {
                /** @var Director|StoreManager $authorizer */
                $authorizer = $happyHourDiscountTransaction->authorizer;

                /** @var ?Employee $employee */
                $employee = $authorizer->employee;

                return $employee instanceof Employee ? $employee->getFullName() . ' (' . $happyHourDiscountTransaction->authorizer_type . ')' : null;
            }
        )->toArray();
    }
}
