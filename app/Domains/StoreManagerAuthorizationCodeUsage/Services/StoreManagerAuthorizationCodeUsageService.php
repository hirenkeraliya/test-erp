<?php

declare(strict_types=1);

namespace App\Domains\StoreManagerAuthorizationCodeUsage\Services;

use App\CommonFunctions;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\StoreManagerAuthorizationCodeUsageQueries;
use Illuminate\Support\Collection;

class StoreManagerAuthorizationCodeUsageService
{
    public function addStoreManagerAuthorizationCodeUsage(
        int $usageTypeId,
        int $referenceId,
        string $referenceType,
        ?string $storeManagerAuthorizationCode,
    ): void {
        if (! $storeManagerAuthorizationCode) {
            return;
        }

        $storeManagerAuthorizationCodeQueries = resolve(StoreManagerAuthorizationCodeQueries::class);
        $storeManagerAuthorizationCode = $storeManagerAuthorizationCodeQueries->getByCode(
            $storeManagerAuthorizationCode
        );

        if (! $storeManagerAuthorizationCode) {
            return;
        }

        $storeManagerAuthorizationCodeUsageQueries = resolve(StoreManagerAuthorizationCodeUsageQueries::class);
        $storeManagerAuthorizationCodeUsageQueries->addNew([
            'store_manager_authorization_code_id' => $storeManagerAuthorizationCode->id,
            'usage_type_id' => $usageTypeId,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }

    public function checkStoreManagerAuthorizationCode(
        Collection $mismatches,
        int $storeManagerId,
        ?string $storeManagerAuthorizationCode,
        ?string $happenedAt = null,
    ): void {
        if (! $storeManagerAuthorizationCode) {
            return;
        }

        $storeManagerAuthorizationCodeQueries = resolve(StoreManagerAuthorizationCodeQueries::class);

        $storeManagerAuthorizationCode = $storeManagerAuthorizationCodeQueries->getByCode(
            $storeManagerAuthorizationCode
        );

        if (! $storeManagerAuthorizationCode) {
            $saleMismatchMessage = 'Specified Store manager authorization code does not correspond with our records.';
            CommonFunctions::addMismatchOrAbort($mismatches, $saleMismatchMessage);

            return;
        }

        if ($storeManagerAuthorizationCode->store_manager_id !== $storeManagerId) {
            $saleMismatchMessage = 'Specified Store manager authorization code and store manager not match.';
            CommonFunctions::addMismatchOrAbort($mismatches, $saleMismatchMessage);
        }

        if (StoreManagerAuthorizationCodeStatuses::ACTIVE !== $storeManagerAuthorizationCode->status) {
            $saleMismatchMessage = 'Specified Store manager authorization code is not active.';
            CommonFunctions::addMismatchOrAbort($mismatches, $saleMismatchMessage);
        }

        if (! $happenedAt) {
            $happenedAt = now()->format('Y-m-d H:i:s');
        }

        if ($storeManagerAuthorizationCode->expiry_date >= $happenedAt) {
            return;
        }

        $saleMismatchMessage = 'Specified Store manager authorization code is expiry.';
        CommonFunctions::addMismatchOrAbort($mismatches, $saleMismatchMessage);
    }
}
