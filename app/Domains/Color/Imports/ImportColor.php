<?php

declare(strict_types=1);

namespace App\Domains\Color\Imports;

use App\Domains\Color\ColorQueries;
use App\Domains\Color\Enums\ColorImportColumns;
use App\Domains\Color\Services\ColorService;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Models\ImportRecord;

class ImportColor implements ImportRecordClassInterface
{
    public function validate(array $colorDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $colorQueries = resolve(ColorQueries::class);

        if (! array_key_exists('name', $colorDetails) || ! $colorDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($colorQueries->existsByName((string) $colorDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified name is already available in our records.';
        }

        if (array_key_exists('code', $colorDetails) && null !== $colorDetails['code'] && $colorQueries->existsByCode(
            (string) $colorDetails['code'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified code is already available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $colorDetails, ImportRecord $importRecord): void
    {
        $colorQueries = resolve(ColorQueries::class);
        $colorGroupQueries = resolve(ColorGroupQueries::class);
        $colorGroupId = null;
        if (array_key_exists('color_group', $colorDetails) && null !== $colorDetails['color_group']) {
            $colorGroup = $colorGroupQueries->getIdByName($colorDetails['color_group'], $importRecord->company_id);
            $colorGroupId = $colorGroup?->id;
        }

        $colorDetails['color_group_id'] = $colorGroupId;

        $colorService = resolve(ColorService::class);
        $colorData = $colorService->getColorData($colorDetails);

        $colorQueries->addNew($colorData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(ColorImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
