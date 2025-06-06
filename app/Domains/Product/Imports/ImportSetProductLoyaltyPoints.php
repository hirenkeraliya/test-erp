<?php

declare(strict_types=1);

namespace App\Domains\Product\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Product\Enums\SetProductLoyaltyPointsImportColumns;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Models\ImportRecord;

class ImportSetProductLoyaltyPoints implements ImportRecordClassInterface
{
    /**
     * @return string[]
     */
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $membershipQueries = resolve(MembershipQueries::class);
        $productQueries = resolve(ProductQueries::class);

        if (! array_key_exists('membership', $productDetails) || ! $productDetails['membership']) {
            $validationErrors[] = 'The membership is required.';
        } elseif (! $membershipQueries->existsByName($productDetails['membership'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified membership is not available in our records.';
        }

        if (! array_key_exists('loyalty_points', $productDetails) || ! $productDetails['loyalty_points']) {
            $validationErrors[] = 'The loyalty point is required.';
        }

        if (! array_key_exists('upc', $productDetails) || ! $productDetails['upc']) {
            $validationErrors[] = 'The UPC is mandatory.';
        } elseif (! $productQueries->existsByUpc((string) $productDetails['upc'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified upc is not available in our records.';
        }

        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $membershipId = $membershipQueries->getIdByName(
            (string) $productDetails['membership'],
            $importRecord->company_id
        );
        $productId = $productQueries->getIdByUpcForLoyaltyPoint(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        if (null !== $membershipId && null !== $productId && $productLoyaltyPointQueries->existByProductLoyaltyPoint(
            $membershipId,
            $productId
        )) {
            $validationErrors[] = 'This product has already been assigned membership benefits and loyalty points.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $productQueries = resolve(ProductQueries::class);

        $membershipId = $membershipQueries->getIdByName($productDetails['membership'], $importRecord->company_id);
        $productId = $productQueries->getIdByUpcForLoyaltyPoint(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        $productLoyaltyPointQueries->addNew(
            (int) $productId,
            (int) $membershipId,
            (int) $productDetails['loyalty_points']
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(SetProductLoyaltyPointsImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
