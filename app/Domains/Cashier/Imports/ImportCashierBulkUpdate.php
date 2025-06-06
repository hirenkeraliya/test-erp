<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Imports;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\Enums\CashierBulkUpdateImportColumns;
use App\Domains\Cashier\Services\CashierService;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Models\ImportRecord;

class ImportCashierBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $cashierDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);
        $validationErrors = [];

        if (! array_key_exists('first_name', $cashierDetails) || ! $cashierDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('username', $cashierDetails) || ! $cashierDetails['username']) {
            $validationErrors[] = 'The username is required.';
        }

        if (array_key_exists('username', $cashierDetails) && $cashierQueries->usernameTakenByAnotherCashier(
            (string) $cashierDetails['username'],
            (string) $cashierDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified cashier username is already assign';
        }

        if (! array_key_exists('mobile_number', $cashierDetails) || ! $cashierDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $employeeQueries->doEmployeeNameExist(
            $cashierDetails['first_name'],
            (string) $cashierDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified employee is not available in our records.';
        }

        if (! array_key_exists('cashier_group', $cashierDetails) || ! $cashierDetails['cashier_group']) {
            $validationErrors[] = 'The cashier group is required';
        } elseif (! $cashierGroupQueries->existsByName(
            (string) $cashierDetails['cashier_group'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified cashier group is not available in our records.';
        }

        if (! array_key_exists('locations', $cashierDetails) || ! $cashierDetails['locations']) {
            $validationErrors[] = 'The location requires.';
        } elseif (! $locationQueries->doStoreNamesExists(
            array_map('trim', explode(',', $cashierDetails['locations'])),
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified location is not available in our records.';
        }

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $cashierDetails['first_name'],
            (string) $cashierDetails['mobile_number'],
            $importRecord->company_id
        );

        if (! $cashierQueries->doExistsByEmployeeId($employeeId)) {
            $validationErrors[] = 'The specified employee has not yet been assigned as a cashier.';
        }

        return $validationErrors;
    }

    public function save(array $cashierDetails, ImportRecord $importRecord): void
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $cashierDetails['first_name'],
            (string) $cashierDetails['mobile_number'],
            $importRecord->company_id
        );

        if (null === $employeeId) {
            return;
        }

        $cashierDetails['pin'] = null;

        $cashierService = resolve(CashierService::class);
        $cashierData = $cashierService->getCashierData($cashierDetails, $employeeId, $importRecord->company_id);

        $cashierQueries = resolve(CashierQueries::class);
        $cashierQueries->updateByMobileNumber(
            $cashierData->all(),
            (string) $cashierDetails['mobile_number'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(CashierBulkUpdateImportColumns::cases())->pluck('value')->toArray();

        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
