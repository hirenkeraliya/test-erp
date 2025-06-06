<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTake\Enums\StockTakeImportColumns;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Models\Color;
use App\Models\ImportRecord;
use App\Models\Product;
use App\Models\Size;

class ImportStockTakes implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);

        if (! array_key_exists('upc', $productDetails) || ! $productDetails['upc']) {
            $validationErrors[] = 'A UPC is required.';
        }

        if (array_key_exists('upc', $productDetails) && $productDetails['upc']) {
            $product = $productQueries->getByUpcAndCompanyId(
                (string) $productDetails['upc'],
                $importRecord->company_id
            );

            if (! $product) {
                $validationErrors[] = 'The specified UPC is not available in our records.';

                return $validationErrors;
            }

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            if ((string) $productDetails['product_name'] !== (string) $product->name) {
                $validationErrors[] = 'Product ' . $productDetails['product_name'] . " does not match the system's product name, " . $product->name . '.';

                return $validationErrors;
            }

            if ($color instanceof Color && (string) $productDetails['color'] !== (string) $color->name) {
                $validationErrors[] = 'Product color ' . $productDetails['color'] . " does not match the system's product color, " . $color->name . '.';

                return $validationErrors;
            }

            if ($size instanceof Size && (string) $productDetails['size'] !== (string) $size->name) {
                $validationErrors[] = 'Product size ' . $productDetails['size'] . " does not match the system's product size, " . $size->name . '.';

                return $validationErrors;
            }

            if (Statuses::ARCHIVED->value === $product->status) {
                $validationErrors[] = 'The specified UPC has already been archived.';
            }
        }

        if (! array_key_exists('submitted_stock', $productDetails)) {
            $validationErrors[] = 'The stock submitted is required.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $productQueries = resolve(ProductQueries::class);

        /** @var Product $product */
        $product = $productQueries->getByUpcAndCompanyId((string) $productDetails['upc'], $importRecord->company_id);

        $stockTakesBulkData = [
            'stock_take_id' => $importRecord->module_id,
            'product_id' => $product->id,
            'submitted_stock' => $productDetails['submitted_stock'] > 0 ? $productDetails['submitted_stock'] : 0,
        ];
        $stockTakeProductQueries->bulkUpdateSubmitStock($stockTakesBulkData);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(StockTakeImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
