<?php

declare(strict_types=1);

namespace App\Domains\Category\Imports;

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\Enums\CategoryImportColumns;
use App\Domains\Category\Services\CategoryService;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Models\ImportRecord;

class ImportCategoryBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $categoryDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $categoryQueries = resolve(CategoryQueries::class);

        if (! array_key_exists('name', $categoryDetails) || ! $categoryDetails['name']) {
            $validationErrors[] = 'A name is required.';
        } elseif (! $categoryQueries->existsByNameAndCompanyId(
            (string) $categoryDetails['name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified category is not available in our records.';
        }

        if (null !== $categoryDetails['code'] && $categoryQueries->codeTakenByAnotherCategory(
            (string) $categoryDetails['code'],
            (string) $categoryDetails['name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'Specified category code is already assign';
        }

        return $validationErrors;
    }

    public function save(array $categoryDetails, ImportRecord $importRecord): void
    {
        $categoryQueries = resolve(CategoryQueries::class);

        $categoryService = resolve(CategoryService::class);
        $categoryData = $categoryService->getCategoryData($categoryDetails);

        $categoryQueries->updateByName(
            $categoryData->all(),
            (string) $categoryDetails['name'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(CategoryImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
