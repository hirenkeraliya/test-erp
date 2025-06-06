<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Imports;

use App\Domains\DreamPrice\Enums\DreamPriceImportColumns;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;

class ImportDreamPrice implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);

        if (! $this->upcExists($productDetails)) {
            $validationErrors[] = 'A UPC is required.';
        }

        if ($this->upcExists($productDetails)) {
            $product = $productQueries->getByUpcAndCompanyId(
                (string) $productDetails['upc'],
                $importRecord->company_id
            );

            if (! $product) {
                $validationErrors[] = 'The specified UPC is not available in our records.';

                return $validationErrors;
            }

            if (true === $product->is_non_selling_item) {
                $validationErrors[] = 'The specified product is non selling item.';
            }

            if (Statuses::ARCHIVED->value === $product->status) {
                $validationErrors[] = 'The specified UPC has already been archived.';
            }
        }

        if (! $this->priceExists($productDetails)) {
            $validationErrors[] = 'The price is required.';
        }

        if ($this->upcExists($productDetails)) {
            $product = $productQueries->getProductTypeAndPrice((string) $productDetails['upc']);

            if (null === $product) {
                return $validationErrors;
            }

            if ($this->priceExists($productDetails) && (float) $productDetails['price'] >= $product->retail_price) {
                $validationErrors[] = 'The upload price should not exceed the product retail price.';
            }

            if ($product->type_id !== ProductTypes::REGULAR_PRODUCT->value) {
                $validationErrors[] = 'The product type is not regular.';
            }
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $productQueries = resolve(ProductQueries::class);

        $dreamPriceProductData = [
            'dream_price_id' => $importRecord->module_id,
            'product_id' => $productQueries->getIdByUpc((string) $productDetails['upc'], $importRecord->company_id),
            'price' => $productDetails['price'],
        ];

        $dreamPriceProductQueries->addNew($dreamPriceProductData);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(DreamPriceImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    private function upcExists(array $productDetails): bool
    {
        return array_key_exists('upc', $productDetails) && $productDetails['upc'];
    }

    private function priceExists(array $productDetails): bool
    {
        return array_key_exists('price', $productDetails) && $productDetails['price'];
    }
}
