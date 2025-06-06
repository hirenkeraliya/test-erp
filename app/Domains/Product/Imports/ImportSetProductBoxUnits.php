<?php

declare(strict_types=1);

namespace App\Domains\Product\Imports;

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\Enums\SetProductBoxUnitsImportColumns;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;

class ImportSetProductBoxUnits implements ImportRecordClassInterface
{
    /**
     * @return string[]
     */
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $productQueries = resolve(ProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        if (! array_key_exists('upc', $productDetails) || ! $productDetails['upc']) {
            $validationErrors[] = 'The UPC is mandatory.';
        } elseif (! $productQueries->existsByUpc((string) $productDetails['upc'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified upc is not available in our records.';
        }

        if (! array_key_exists('package_type_name', $productDetails) || ! $productDetails['package_type_name']) {
            $validationErrors[] = 'The package type name is mandatory.';
        } elseif (! $packageTypeQueries->existsByName(
            (string) $productDetails['package_type_name'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified package type name is not available in our records.';
        }

        if (! array_key_exists('units', $productDetails) || ! $productDetails['units']) {
            $validationErrors[] = 'The units is mandatory.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $boxProductQueries = resolve(BoxProductQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        $packageTypeId = $packageTypeQueries->getIdByName(
            $productDetails['package_type_name'],
            $importRecord->company_id
        );

        $productId = $productQueries->getIdByUpc((string) $productDetails['upc'], $importRecord->company_id);

        $boxProductQueries->addNew([
            'product_id' => (int) $productId,
            'package_type_id' => (int) $packageTypeId,
            'units' => $productDetails['units'],
            'retail_price' => array_key_exists(
                'retail_price',
                $productDetails
            ) && $productDetails['retail_price'] ? $productDetails['retail_price'] : null,
            'staff_price' => array_key_exists(
                'staff_price',
                $productDetails
            ) && $productDetails['staff_price'] ? $productDetails['staff_price'] : null,
        ]);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(SetProductBoxUnitsImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
