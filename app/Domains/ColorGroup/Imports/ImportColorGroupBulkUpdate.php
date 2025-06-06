<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup\Imports;

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\Enums\ColorGroupImportColumns;
use App\Domains\ColorGroup\Services\ColorGroupService;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Models\ImportRecord;

class ImportColorGroupBulkUpdate implements ImportRecordClassInterface
{
    public function validate(array $colorGroupDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $colorGroupQueries = resolve(ColorGroupQueries::class);

        if (! array_key_exists('name', $colorGroupDetails) || ! $colorGroupDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif (! $colorGroupQueries->existsByName((string) $colorGroupDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified color group is not available in our records.';
        }

        if (array_key_exists('code', $colorGroupDetails) && $colorGroupQueries->codeTakenByAnotherColorGroup(
            (string) $colorGroupDetails['code'],
            (string) $colorGroupDetails['name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified color group code is already assign';
        }

        return $validationErrors;
    }

    public function save(array $colorGroupDetails, ImportRecord $importRecord): void
    {
        $colorGroupQueries = resolve(ColorGroupQueries::class);

        $colorGroupService = resolve(ColorGroupService::class);
        $colorGroupData = $colorGroupService->getColorGroupData($colorGroupDetails);

        $colorGroupQueries->updateByName(
            $colorGroupData->all(),
            (string) $colorGroupDetails['name'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(ColorGroupImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
