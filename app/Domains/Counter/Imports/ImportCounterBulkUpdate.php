<?php

declare(strict_types=1);

namespace App\Domains\Counter\Imports;

use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\Enums\CounterImportColumns;
use App\Domains\Counter\Services\CounterService;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Models\ImportRecord;

class ImportCounterBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $counterDetails, ImportRecord $importRecord): array
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $validationErrors = [];

        if (! array_key_exists('name', $counterDetails) || ! $counterDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }

        if (! array_key_exists('is_locked', $counterDetails) || ! $counterDetails['is_locked']) {
            $validationErrors[] = 'The is_locked is required.';
        }

        $locationExist = $locationQueries->existsByName(
            (string) $counterDetails['location'],
            $importRecord->company_id
        );
        $counterExist = $counterQueries->counterExists((string) $counterDetails['name'], $importRecord->company_id);

        if (! $locationExist) {
            $validationErrors[] = 'The specified location is not available in our records.';
        }

        if (! $counterExist) {
            $validationErrors[] = 'The specified counter is not available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $counterDetails, ImportRecord $importRecord): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getIdByName((string) $counterDetails['location'], $importRecord->company_id);

        $counterService = resolve(CounterService::class);
        $counterData = $counterService->getCounterData($counterDetails, (int) $locationId);

        $counterQueries = resolve(CounterQueries::class);
        $counterQueries->updateByName($counterData->all(), (string) $counterDetails['name']);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(CounterImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
