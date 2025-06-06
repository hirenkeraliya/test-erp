<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Imports;

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\Enums\StoreManagerBulkUpdateImportColumns;
use App\Domains\StoreManager\Services\StoreManagerService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\ImportRecord;

class ImportStoreManagerBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $storeManagerDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        $validationErrors = [];

        if (! array_key_exists('first_name', $storeManagerDetails) || ! $storeManagerDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('username', $storeManagerDetails) || ! $storeManagerDetails['username']) {
            $validationErrors[] = 'The username is required.';
        }

        if ($storeManagerQueries->usernameTakenByAnotherStoreManager(
            (string) $storeManagerDetails['username'],
            (string) $storeManagerDetails['first_name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified store manager username is already assign';
        }

        if (! array_key_exists('mobile_number', $storeManagerDetails) || ! $storeManagerDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $employeeQueries->doEmployeeNameExist(
            $storeManagerDetails['first_name'],
            (string) $storeManagerDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified employee is not available in our records.';
        }

        if (! array_key_exists('locations', $storeManagerDetails) || ! $storeManagerDetails['locations']) {
            $validationErrors[] = 'The location requires.';
        } elseif (! $locationQueries->doStoreNamesExists(
            array_map('trim', explode(',', $storeManagerDetails['locations'])),
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified location is not available in our records.';
        }

        if (! array_key_exists('roles', $storeManagerDetails) || ! $storeManagerDetails['roles']) {
            $validationErrors[] = 'The role requires.';
        } elseif (! $roleQueries->doRoleNamesExists(
            array_map('trim', explode(',', $storeManagerDetails['roles'])),
            'store_manager'
        )) {
            $validationErrors[] = 'The specified roles is not available in our records.';
        }

        if (array_key_exists(
            'brands',
            $storeManagerDetails
        ) && null !== $storeManagerDetails['brands'] && ! $brandQueries->doBrandNamesExists(
            array_map('trim', explode(',', $storeManagerDetails['brands'])),
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified brand is not available in our records.';
        }

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $storeManagerDetails['first_name'],
            (string) $storeManagerDetails['mobile_number'],
            $importRecord->company_id
        );

        if (! $storeManagerQueries->doExistsByEmployeeId($employeeId)) {
            $validationErrors[] = 'The specified employee is not available in store manager';
        }

        if (! array_key_exists(
            'price_override_type',
            $storeManagerDetails
        ) || ! $storeManagerDetails['price_override_type']) {
            $validationErrors[] = 'A price override type is required.';
        } elseif (! PriceOverrideTypes::getValueByCaseName($storeManagerDetails['price_override_type'])) {
            $validationErrors[] = 'The specified price override type is invalid.';
        } elseif (PriceOverrideTypes::getValueByCaseName(
            $storeManagerDetails['price_override_type']
        ) === PriceOverrideTypes::PERCENTAGE->value && null === $storeManagerDetails['price_override_limit_percentage_for_item']) {
            $validationErrors[] = 'A price override limit percentage for item is required when price override type is '.$storeManagerDetails['price_override_type'];
        }

        $companyConfiguration = $companyQueries->getConfigurationColumnsById($importRecord->company_id);

        if (
            $companyConfiguration->allow_price_override_cart_level
            && (! array_key_exists('price_override_limit_percentage_for_cart', $storeManagerDetails)
            || null === $storeManagerDetails['price_override_limit_percentage_for_cart'])
        ) {
            $validationErrors[] = 'The price override limit percentage for cart is required.';
        }

        return $validationErrors;
    }

    public function save(array $storeManagerDetails, ImportRecord $importRecord): void
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $storeManagerDetails['first_name'],
            (string) $storeManagerDetails['mobile_number'],
            $importRecord->company_id
        );

        if (null === $employeeId) {
            return;
        }

        $storeManagerDetails['password'] = null;
        $storeManagerDetails['passcode'] = null;
        $storeManagerService = resolve(StoreManagerService::class);
        $storeManagerData = $storeManagerService->preparedImportRecords(
            $storeManagerDetails,
            $importRecord,
            (int) $employeeId
        );

        $storeManagerQueries = resolve(StoreManagerQueries::class);

        $storeManagerQueries->updateByMobileNumber(
            $storeManagerData->all(),
            (string) $storeManagerDetails['mobile_number'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(StoreManagerBulkUpdateImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
