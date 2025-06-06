<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\Imports;

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\Enums\CashierGroupImportColumns;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\CashierGroup\Services\CashierGroupService;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Models\ImportRecord;

class ImportCashierGroup implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $cashierGroupDetails, ImportRecord $importRecord): array
    {
        $cashierGroupQueries = resolve(CashierGroupQueries::class);
        $validationErrors = [];

        if (! array_key_exists('name', $cashierGroupDetails) || ! $cashierGroupDetails['name']) {
            $validationErrors[] = 'A name is required.';
        } elseif ($cashierGroupQueries->existsByName(
            (string) $cashierGroupDetails['name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified name is already assigned as a cashier group.';
        }

        if (! array_key_exists('permissions', $cashierGroupDetails) || ! $cashierGroupDetails['permissions']) {
            $validationErrors[] = 'The permissions is required.';
        } else {
            $permissions = explode(',', $cashierGroupDetails['permissions']);
            $status = false;

            foreach ($permissions as $permission) {
                if (PermissionTypes::getValueByCaseName(trim($permission)) === null) {
                    $status = true;
                }
            }

            if ($status) {
                $validationErrors[] = 'The specified permission is not available.';
            }
        }

        $companyQueries = resolve(CompanyQueries::class);
        $isAllowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel($importRecord->company_id);

        if ($isAllowPriceOverrideCartLevel &&
            (! array_key_exists(
                'price_override_limit_percentage_for_cart',
                $cashierGroupDetails
            ) || ! $cashierGroupDetails['price_override_limit_percentage_for_cart'])
        ) {
            $validationErrors[] = 'The price_override_limit_percentage_for_cart is required.';
        }

        if (! array_key_exists(
            'price_override_type',
            $cashierGroupDetails
        ) || ! $cashierGroupDetails['price_override_type']) {
            $validationErrors[] = 'The price_override_type is required.';
        } elseif (! PriceOverrideTypes::getValueByCaseName(trim($cashierGroupDetails['price_override_type']))) {
            $validationErrors[] = 'The price_override_type type is invalid.';
        }

        if (PriceOverrideTypes::getValueByCaseName(
            trim($cashierGroupDetails['price_override_type'])
        ) === PriceOverrideTypes::PERCENTAGE->value &&
            (! array_key_exists(
                'price_override_limit_percentage_for_item',
                $cashierGroupDetails
            ) || null === $cashierGroupDetails['price_override_limit_percentage_for_item'])
        ) {
            $validationErrors[] = 'The price_override_limit_percentage_for_item is required.';
        }

        return $validationErrors;
    }

    public function save(array $cashierGroupDetails, ImportRecord $importRecord): void
    {
        $cashierGroupQueries = resolve(CashierGroupQueries::class);
        $cashierGroupService = resolve(CashierGroupService::class);

        $cashierGroupData = $cashierGroupService->getCashierGroupData($cashierGroupDetails);

        $cashierGroupQueries->addNew($cashierGroupData, $importRecord->company_id, $importRecord->createdBy);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(CashierGroupImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
