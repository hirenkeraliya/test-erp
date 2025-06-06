<?php

declare(strict_types=1);

namespace App\Domains\Employee\Imports;

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\EmployeeImportColumns;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Member\Services\MemberService;
use App\Models\ImportRecord;

class ImportEmployee implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $employeeDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $validationErrors = [];

        if (! array_key_exists('designation_name', $employeeDetails) || ! $employeeDetails['designation_name']) {
            $validationErrors[] = 'A designation name is required.';
        }

        if (! array_key_exists('first_name', $employeeDetails) || ! $employeeDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('mobile_number', $employeeDetails) || ! $employeeDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
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

        if (array_key_exists('mobile_number', $employeeDetails) && null !== $employeeDetails['mobile_number']) {
            $mobileNumberExist = $employeeQueries->mobileNumberExist(
                (string) $employeeDetails['mobile_number'],
                $importRecord->company_id
            );

            if ($mobileNumberExist) {
                $validationErrors[] = 'The mobile number ' . $employeeDetails['mobile_number'] . ' is a duplicate.';
            }
        }

        if (array_key_exists('email', $employeeDetails) && null !== $employeeDetails['email']) {
            $emailExist = $employeeQueries->emailExist((string) $employeeDetails['email'], $importRecord->company_id);

            if ($emailExist) {
                $validationErrors[] = 'The email ' . $employeeDetails['email'] . ' is a duplicate.';
            }
        }

        return $validationErrors;
    }

    public function save(array $employeeDetails, ImportRecord $importRecord): void
    {
        $designationQueries = resolve(DesignationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $designationId = $designationQueries->getIdByName(
            (string) $employeeDetails['designation_name'],
            $importRecord->company_id
        );

        $employeeData = new EmployeeData(
            company_id: $importRecord->company_id,
            designation_id: $designationId,
            first_name: (string) $employeeDetails['first_name'],
            last_name: (string) $employeeDetails['last_name'] ?: null,
            email: (string) $employeeDetails['email'] ?: null,
            mobile_number: (string) $employeeDetails['mobile_number'],
            home_contact: (string) $employeeDetails['home_contact'],
            address_line_1: (string) $employeeDetails['address_line_1'],
            address_line_2: (string) $employeeDetails['address_line_2'] ?: null,
            city: (string) $employeeDetails['city'] ?: null,
            area_code: (string) $employeeDetails['area_code'],
            date_of_joining: (string) $employeeDetails['date_of_joining'] ?: null,
            primary_contact_name: (string) $employeeDetails['primary_contact_name'] ?: null,
            primary_contact_phone: (string) $employeeDetails['primary_contact_phone'],
            staff_id: (string) $employeeDetails['staff_id'],
            ic_number: (string) $employeeDetails['ic_number'],
            job_type: JobTypes::getValueByCaseName((string) $employeeDetails['job_type']),
            status: true,
            photo: null,
        );

        $employee = $employeeQueries->addNew($employeeData, $importRecord->createdBy);
        $memberService = resolve(MemberService::class);
        $memberService->addNewEmployeeMember($employee);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(EmployeeImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
