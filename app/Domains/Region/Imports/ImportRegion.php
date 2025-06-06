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

class ImportRegion implements ImportRecordClassInterface
{
    public function validate(array $regionDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $regionQueries = resolve(RegionQueries::class);

        if (! array_key_exists('name', $regionDetails) || ! $regionDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($regionQueries->existsByName((string) $regionDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'Specified region name is already assign';
        }

        if (array_key_exists('code', $regionDetails) && $regionQueries->existsByCode(
            (string) $regionDetails['code'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified region code is already assign';
        }

        return $validationErrors;
    }

    public function save(array $regionDetails, ImportRecord $importRecord): void
    {
        $regionService = resolve(RegionService::class);
        $regionData = $regionService->getRegionData($regionDetails);

        $regionQueries = resolve(RegionQueries::class);
        $regionQueries->addNew($regionData, $importRecord->company_id);
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
