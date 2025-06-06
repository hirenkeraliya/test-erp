<?php

declare(strict_types=1);

namespace App\Domains\Employee\Imports;

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\EmployeeBulkUpdateImportColumns;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Models\ImportRecord;

class ImportEmployeesBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $employeeDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $validationErrors = [];

        if (! array_key_exists('designation_name', $employeeDetails) || ! $employeeDetails['designation_name']) {
            $validationErrors[] = 'A designation name is required.';
        }

        if (! array_key_exists('first_name', $employeeDetails) || ! $employeeDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('mobile_number', $employeeDetails) || ! $employeeDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $employeeQueries->mobileNumberExist(
            (string) $employeeDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified mobile number is not available in our records.';
        }

        if (! array_key_exists('address_line_1', $employeeDetails) || ! $employeeDetails['address_line_1']) {
            $validationErrors[] = 'Address line 1 is required.';
        }

        if (! array_key_exists('staff_id', $employeeDetails) || ! $employeeDetails['staff_id']) {
            $validationErrors[] = 'A staff ID is required.';
        }

        if (! array_key_exists('job_type', $employeeDetails) || ! $employeeDetails['job_type']) {
            $validationErrors[] = 'A job type is required.';
        }

        if (
            array_key_exists('job_type', $employeeDetails) &&
            $employeeDetails['job_type'] &&
            ! JobTypes::getValueByCaseName((string) $employeeDetails['job_type'])
        ) {
            $validationErrors[] = 'The specified job type is not available in our records..';
        }

        if (array_key_exists('email', $employeeDetails) && $employeeDetails['email'] &&
            $employeeQueries->emailTakenByAnotherEmployee(
                $employeeDetails['email'],
                $importRecord->company_id,
                (string) $employeeDetails['mobile_number']
            )) {
            $validationErrors[] = 'The specified email address is already taken by another member.';
        }

        if (array_key_exists('group_name', $employeeDetails) && null !== $employeeDetails['group_name']) {
            $employeeGroupExist = $employeeGroupQueries->employeeGroupExists(
                (string) $employeeDetails['group_name'],
                $importRecord->company_id
            );

            if (! $employeeGroupExist) {
                $validationErrors[] = 'The specified employee group  is not available in our records.';
            }
        }

        return $validationErrors;
    }

    public function save(array $employeeDetails, ImportRecord $importRecord): void
    {
        $designationQueries = resolve(DesignationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $designationId = $designationQueries->getIdByName(
            (string) $employeeDetails['designation_name'],
            $importRecord->company_id
        );

        $groupId = $employeeGroupQueries->getIdByName(
            (string) $employeeDetails['group_name'],
            $importRecord->company_id
        );

        if (null === $groupId) {
            $groupId = null;
        }

        $employeeData = [
            'company_id' => $importRecord->company_id,
            'designation_id' => $designationId,
            'group_id' => $groupId,
            'first_name' => (string) $employeeDetails['first_name'],
            'last_name' => (string) $employeeDetails['last_name'] ?: null,
            'email' => (string) $employeeDetails['email'] ?: null,
            'mobile_number' => (string) $employeeDetails['mobile_number'],
            'home_contact' => (string) $employeeDetails['home_contact'],
            'address_line_1' => (string) $employeeDetails['address_line_1'],
            'address_line_2' => (string) $employeeDetails['address_line_2'] ?: null,
            'city' => (string) $employeeDetails['city'] ?: null,
            'area_code' => (string) $employeeDetails['area_code'],
            'date_of_joining' => (string) $employeeDetails['date_of_joining'] ?: null,
            'primary_contact_name' => (string) $employeeDetails['primary_contact_name'] ?: null,
            'primary_contact_phone' => (string) $employeeDetails['primary_contact_phone'],
            'staff_id' => (string) $employeeDetails['staff_id'],
            'ic_number' => (string) $employeeDetails['ic_number'],
            'job_type' => JobTypes::getValueByCaseName((string) $employeeDetails['job_type']),
        ];

        $employeeQueries->updateByMobileNumber(
            $employeeData,
            (string) $employeeDetails['mobile_number'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(EmployeeBulkUpdateImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
