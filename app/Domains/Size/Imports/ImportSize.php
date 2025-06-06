<?php

declare(strict_types=1);

namespace App\Domains\Size\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Size\Enums\SizeImportColumns;
use App\Domains\Size\Services\SizeService;
use App\Domains\Size\SizeQueries;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\ImportRecord;

class ImportSize implements ImportRecordClassInterface
{
    public function validate(array $sizeDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $sizeQueries = resolve(SizeQueries::class);

        if (! array_key_exists('name', $sizeDetails) || ! $sizeDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($sizeQueries->existsByName((string) $sizeDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified name is already available in our records.';
        }

        if (array_key_exists('code', $sizeDetails) && null !== $sizeDetails['code'] && $sizeQueries->existsByCode(
            (string) $sizeDetails['code'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified code is already available in our records.';
        }

        if (array_key_exists('size_group', $sizeDetails) && null !== $sizeDetails['size_group']) {
            $sizeGroupQueries = resolve(SizeGroupQueries::class);
            $sizeGroup = $sizeGroupQueries->getIdByName(trim($sizeDetails['size_group']), $importRecord->company_id);
            if (! $sizeGroup) {
                $validationErrors[] = 'The specified size group is not available in our records.';
            }
        }

        if (array_key_exists('create_after', $sizeDetails) && $sizeDetails['create_after'] &&
            ! $sizeQueries->existsByName((string) $sizeDetails['create_after'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'The specified create after name is not available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $sizeDetails, ImportRecord $importRecord): void
    {
        $sizeQueries = resolve(SizeQueries::class);
        $sizeGroupQueries = resolve(SizeGroupQueries::class);
        $sizeGroupId = null;
        if (array_key_exists('size_group', $sizeDetails) && null !== $sizeDetails['size_group']) {
            $sizeGroup = $sizeGroupQueries->getIdByName($sizeDetails['size_group'], $importRecord->company_id);
            $sizeGroupId = $sizeGroup?->id;
        }

        $sortOrderId = $sizeQueries->getIdBySortName((string) $sizeDetails['create_after'], $importRecord->company_id);

        $sizeDetails['sort_order_id'] = $sortOrderId ?? 0;
        $sizeDetails['size_group_id'] = $sizeGroupId;

        $sizeService = resolve(SizeService::class);
        $sizeData = $sizeService->getSizeData($sizeDetails);

        $sizeQueries->addNew($sizeData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(SizeImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
