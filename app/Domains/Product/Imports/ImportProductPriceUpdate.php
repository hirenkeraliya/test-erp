<?php

declare(strict_types=1);

namespace App\Domains\Product\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\Enums\BulkProductPriceUpdateImportColumns;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;
use Illuminate\Support\Str;

class ImportProductPriceUpdate implements ImportRecordClassInterface
{
    /**
     * @return string[]|mixed[]
     */
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        if (! array_key_exists('upc', $productDetails)) {
            $validationErrors[] = 'UPC is required.';

            return $validationErrors;
        }

        if (! array_key_exists('retail_price', $productDetails) || ! $productDetails['retail_price']) {
            $validationErrors[] = 'The retail price is required.';
        }

        $desiredKeys = collect(BulkProductPriceUpdateImportColumns::cases())->pluck('value')->toArray();

        $invalidKeys = array_keys(array_diff_key(array_flip($desiredKeys), $productDetails));

        if ([] !== $invalidKeys) {
            $validationErrors[] = 'Original Column Key Names Are: ' . implode(',', $invalidKeys);
        }

        return $this->checkUpcColumnValidation($productDetails, $validationErrors, $importRecord->company_id);
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);

        $productPriceData = [
            'retail_price' => (float) $productDetails['retail_price'],
            'franchise_price_1' => (float) $productDetails['franchise_price_1'],
            'franchise_price_2' => (float) $productDetails['franchise_price_2'],
            'franchise_price_3' => (float) $productDetails['franchise_price_3'],
            'wholesale_price' => (float) $productDetails['wholesale_price'],
            'company_or_tender_price' => (float) $productDetails['company_or_tender_price'],
            'branch_price' => (float) $productDetails['branch_price'],
            'minimum_price' => (float) $productDetails['minimum_price'],
            'original_capital_price' => (float) $productDetails['original_capital_price'],
            'capital_price' => (float) $productDetails['capital_price'],
            'staff_price' => (float) $productDetails['staff_price'],
            'purchase_cost' => (float) $productDetails['purchase_cost'],
            'online_price' => (float) $productDetails['online_price'],
        ];

        $productQueries->updateProductPrice(
            $productPriceData,
            (string) $productDetails['upc'],
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        $optionalColumns = array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($requiredPermissions, $allowedPermissionLists)
        );

        $importRecordService = resolve(ImportRecordService::class);

        if ([] !== $optionalColumns) {
            $requiredHeaderColumns = collect(BulkProductPriceUpdateImportColumns::cases())
                ->whereNotIn('value', $optionalColumns)
                ->pluck('value')
                ->toArray();

            $invalidColumns = array_intersect($optionalColumns, array_keys($uploadHeaderColumns));

            if ([] !== $invalidColumns) {
                return [
                    'type' => ColumnValidationIssueTypes::PERMISSION_ISSUE->value,
                    'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
                    'columns' => $optionalColumns,
                ];
            }

            return [
                'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
                'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
            ];
        }

        $requiredHeaderColumns = collect(BulkProductPriceUpdateImportColumns::cases())->pluck('value')->toArray();

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    /**
     * @return mixed[]
     */
    private function checkUpcColumnValidation(array $productDetails, array $validationErrors, int $companyId): array
    {
        $productQueries = resolve(ProductQueries::class);

        if (! $productDetails['upc']) {
            $validationErrors[] = 'A UPC is required.';
        }

        if ($productDetails['upc']) {
            $product = $productQueries->getByUpcAndCompanyId((string) $productDetails['upc'], $companyId);

            if (! $product) {
                $validationErrors[] = 'The specified UPC does not exist in our records.';

                return $validationErrors;
            }

            if (Statuses::ARCHIVED->value === $product->status) {
                $validationErrors[] = 'The specified UPC has already been archived.';
            }
        }

        return $validationErrors;
    }
}
