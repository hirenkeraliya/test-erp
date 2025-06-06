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
use App\Domains\Promoter\Enums\PromoterBulkUpdateCommissionImportColumns;
use App\Domains\Promoter\Enums\PromoterBulkUpdateImportColumns;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\services\PromoterService;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\ImportRecord;
use App\Models\PromoterGroup;

class ImportPromoterBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $promoterDetails, ImportRecord $importRecord): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        $validationErrors = [];

        if (! array_key_exists('first_name', $promoterDetails) || ! $promoterDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('username', $promoterDetails) || ! $promoterDetails['username']) {
            $validationErrors[] = 'The username is required.';
        } elseif ($promoterQueries->usernameTakenByAnotherPromoter(
            (string) $promoterDetails['username'],
            (string) $promoterDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified username is already in use by another promoter.';
        }

        if (! array_key_exists('mobile_number', $promoterDetails) || ! $promoterDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $employeeQueries->doEmployeeNameExist(
            (string) $promoterDetails['first_name'],
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

        $employeeId = $employeeQueries->getIdByNameAndMobileNumber(
            (string) $promoterDetails['first_name'],
            (string) $promoterDetails['mobile_number'],
            $importRecord->company_id
        );

        if (! $promoterQueries->doExistsByEmployeeId($employeeId)) {
            $validationErrors[] = 'The specified employee has not yet been assigned as a promoter.';
        }

        if (array_key_exists(
            'group',
            $promoterDetails
        ) && null !== $promoterDetails['group'] && ! $promoterGroupQueries->doExistsByName(
            (string) $promoterDetails['group'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified group is not available in our records.';
        }

        $company = $companyQueries->getByIdWithPromoterCommissionDetails($importRecord->company_id);

        if ($company->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
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

        return $validationErrors;
    }

    public function save(array $promoterDetails, ImportRecord $importRecord): void
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $promoterGroup = null;
        if ($promoterDetails['group']) {
            $promoterGroup = $promoterGroupQueries->getByName($promoterDetails['group'], $importRecord->company_id);
        }

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

        $groupId = $promoterGroup instanceof PromoterGroup ? $promoterGroup->id : null;

        $promoterDetails['password'] = null;
        $promoterService = resolve(PromoterService::class);
        $promoterData = $promoterService->getPromoterData($promoterDetails, $locationIds, (int) $employeeId, $groupId);

        $promoterQueries->updateByEmployeeId($promoterData->all(), $employeeId, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);
        $importRecordService = resolve(ImportRecordService::class);

        if ($company->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
            $requiredHeaderColumns = collect(PromoterBulkUpdateCommissionImportColumns::cases())->pluck(
                'value'
            )->toArray();

            return [
                'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
                'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
            ];
        }

        $requiredHeaderColumns = collect(PromoterBulkUpdateImportColumns::cases())->pluck('value')->toArray();

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
