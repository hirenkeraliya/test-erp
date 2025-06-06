<?php

declare(strict_types=1);

namespace App\Domains\Region\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Region\Enums\RegionImportColumns;
use App\Domains\Region\RegionQueries;
use App\Domains\Region\Services\RegionService;
use App\Models\ImportRecord;

class ImportRegionBulkUpdate implements ImportRecordClassInterface
{
    public function validate(array $regionDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $regionQueries = resolve(RegionQueries::class);

        if (! array_key_exists('name', $regionDetails) || ! $regionDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif (! $regionQueries->existsByName((string) $regionDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'Specified region name is not available in our records.';
        }

        if (array_key_exists('code', $regionDetails) && $regionQueries->existsByCodeExceptCurrentRecord(
            (string) $regionDetails['code'],
            (string) $regionDetails['name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified region code is already assign';
        }

        if (array_key_exists(
            'manager_email',
            $regionDetails
        ) && ! empty($regionDetails['manager_email']) && ! filter_var(
            $regionDetails['manager_email'],
            FILTER_VALIDATE_EMAIL
        )) {
            $validationErrors[] = 'Specified email format is not valid';
        }

        return $validationErrors;
    }

    public function save(array $regionDetails, ImportRecord $importRecord): void
    {
        $regionService = resolve(RegionService::class);
        $regionData = $regionService->getRegionData($regionDetails);

        $regionQueries = resolve(RegionQueries::class);
        $regionQueries->updateByName($regionData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(RegionImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
