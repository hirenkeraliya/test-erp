<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Imports;

use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\Enums\PromoterCommissionByDepartmentImportColumns;
use App\Domains\Promoter\Enums\PromoterCommissionByPromoterImportColumns;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\services\PromoterService;
use App\Models\ImportRecord;

class ImportPromoter implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $promoterDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        $validationErrors = [];

        if (! array_key_exists('first_name', $promoterDetails) || ! $promoterDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('username', $promoterDetails) || ! $promoterDetails['username']) {
            $validationErrors[] = 'The username is required.';
        } elseif ($promoterQueries->doExistsByPromoterUsername(
            (string) $promoterDetails['username'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified username is already assigned as a promoter.';
        }

        if (! array_key_exists('password', $promoterDetails) || ! $promoterDetails['password']) {
            $validationErrors[] = 'The password is required.';
        }

        if (! array_key_exists('mobile_number', $promoterDetails) || ! $promoterDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $employeeQueries->doEmployeeNameExist(
            $promoterDetails['first_name'],
            (string) $promoterDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified employee is not available in our records.';
        }

        if (! array_key_exists('locations', $promoterDetails) || ! $promoterDetails['locations']) {
            $validationErrors[] = 'The location requires.';
        } elseif (! $locationQueries->doStoreNamesExists(
            array_map('trim', explode(',', $promoterDetails['locations'])),
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified location is not available in our records.';
        }

        $promoterCommissionType = $companyQueries->getByIdWithPromoterCommissionDetails($importRecord->company_id);

        if ($promoterCommissionType->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
            if (! array_key_exists('monthly_sales_target', $promoterDetails)) {
                $validationErrors[] = 'The monthly sales target is required.';
            }

            if (! array_key_exists('default_commission_amount_percentage', $promoterDetails)) {
                $validationErrors[] = 'The default commission amount percentage is mandatory.';
            }

            if (! array_key_exists('monthly_target_commission_percentage', $promoterDetails)) {
                $validationErrors[] = 'The monthly target commission percentage is mandatory.';
            }
        }

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $promoterDetails['first_name'],
            (string) $promoterDetails['mobile_number'],
            $importRecord->company_id
        );

        if ($promoterQueries->doExistsByEmployeeId($employeeId)) {
            $validationErrors[] = 'The specified employee has already been assigned as a promoter';
        }

        return $validationErrors;
    }

    public function save(array $promoterDetails, ImportRecord $importRecord): void
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            $promoterDetails['first_name'],
            (string) $promoterDetails['mobile_number'],
            $importRecord->company_id
        );

        if (null === $employeeId) {
            return;
        }

        $promoterLocations = explode(',', $promoterDetails['locations']);

        $locations = $locationQueries->getIdAndNameByNames(
            array_map('trim', $promoterLocations),
            $importRecord->company_id
        );
        $locationIds = $locations->map(fn ($location) => $location->id)->toArray();

        $promoterDetails['password'] = bcrypt($promoterDetails['password']);

        $promoterService = resolve(PromoterService::class);
        $promoterData = $promoterService->getPromoterData($promoterDetails, $locationIds, (int) $employeeId);

        $promoterQueries->addNew($promoterData, $importRecord->createdBy);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $promoterCommissionType = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);
        $importRecordService = resolve(ImportRecordService::class);

        if ($promoterCommissionType->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
            $requiredHeaderColumns = collect(PromoterCommissionByPromoterImportColumns::cases())->pluck(
                'value'
            )->toArray();

            return [
                'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
                'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
            ];
        }

        $requiredHeaderColumns = collect(PromoterCommissionByDepartmentImportColumns::cases())->pluck(
            'value'
        )->toArray();

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
