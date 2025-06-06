<?php

declare(strict_types=1);

namespace App\Domains\SizeGroup\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\SizeGroup\Enums\SizeGroupImportColumns;
use App\Domains\SizeGroup\Services\SizeGroupService;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\ImportRecord;

class ImportSizeGroup implements ImportRecordClassInterface
{
    public function validate(array $sizeGroupDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $sizeGroupQueries = resolve(SizeGroupQueries::class);

        if (! array_key_exists('name', $sizeGroupDetails) || ! $sizeGroupDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($sizeGroupQueries->existsByName((string) $sizeGroupDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'Specified size group name is already assign';
        }

        if (array_key_exists('code', $sizeGroupDetails) && $sizeGroupQueries->existsByCode(
            (string) $sizeGroupDetails['code'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified size group code is already assign';
        }

        return $validationErrors;
    }

    public function save(array $sizeGroupDetails, ImportRecord $importRecord): void
    {
        $sizeGroupQueries = resolve(SizeGroupQueries::class);

        $sizeGroupService = resolve(SizeGroupService::class);
        $sizeGroupData = $sizeGroupService->getSizeGroupData($sizeGroupDetails);

        $sizeGroupQueries->addNew($sizeGroupData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(SizeGroupImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
