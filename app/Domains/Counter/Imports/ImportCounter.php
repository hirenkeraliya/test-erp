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

class ImportCounter implements ImportRecordClassInterface
{
    public function validate(array $counterDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $locationQueries = resolve(LocationQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        if (! array_key_exists('location', $counterDetails) || ! $counterDetails['location']) {
            $validationErrors[] = 'The location requires.';
        }

        $locationId = null;
        if (array_key_exists('location', $counterDetails) && null !== $counterDetails['location']) {
            $location = $locationQueries->getIdOnlyByName(
                (string) $counterDetails['location'],
                $importRecord->company_id
            );
            $locationId = $location?->id;
            if (! $locationId) {
                $validationErrors[] = 'The specified location is not available in our records.';
            }
        }

        if (! array_key_exists('name', $counterDetails) || ! $counterDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($locationId && $counterQueries->existsByName((string) $counterDetails['name'], $locationId)) {
            $validationErrors[] = 'Specified counter name is already assign to location';
        }

        if (! array_key_exists('is_locked', $counterDetails) || ! $counterDetails['is_locked']) {
            $validationErrors[] = 'The is locked is required.';
        }

        return $validationErrors;
    }

    public function save(array $counterDetails, ImportRecord $importRecord): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getIdByName($counterDetails['location'], $importRecord->company_id);

        $counterService = resolve(CounterService::class);
        $counterData = $counterService->getCounterData($counterDetails, (int) $locationId);

        $counterQueries = resolve(CounterQueries::class);
        $counterQueries->addNew($counterData);
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
