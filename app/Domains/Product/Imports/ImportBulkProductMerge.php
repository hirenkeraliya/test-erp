<?php

declare(strict_types=1);

namespace App\Domains\Product\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Product\Enums\BulkProductMergeImportColumns;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Jobs\ProductMergeJob;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\ImportRecord;
use App\Models\Product;

class ImportBulkProductMerge implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $productQueries = resolve(ProductQueries::class);

        $oldUpc = (string) $productDetails['old_upc'];
        $newUpc = (string) $productDetails['new_upc'];

        if ($oldUpc === $newUpc) {
            $validationErrors[] = 'Both UPCs cannot be the same.';
        }

        $oldProduct = $productQueries->getByUpcAndCompanyIdForImportMerge($oldUpc, $importRecord->company_id);
        $newProduct = $productQueries->getByUpcAndCompanyIdForImportMerge($newUpc, $importRecord->company_id);

        if ($oldProduct instanceof Product) {
            if (Statuses::ARCHIVED->value === $oldProduct->status) {
                $validationErrors[] = 'The old product is already in archived state. You cannot merge it.';
            }
        } else {
            $validationErrors[] = 'The old UPC is not available in our records.';
        }

        if ($newProduct instanceof Product) {
            if (Statuses::ARCHIVED->value === $newProduct->status) {
                $validationErrors[] = 'The new product is already in archived state. You cannot merge it.';
            }
        } else {
            $validationErrors[] = 'The new UPC is not available in our records.';
        }

        if ($oldProduct && $newProduct) {
            if (null !== $oldProduct->master_product_id && null !== $newProduct->master_product_id && $oldProduct->master_product_id !== $newProduct->master_product_id) {
                $validationErrors[] = 'Same Master Product only can be merge.';
            }

            if (config('app.product_variant')) {
                if ($oldProduct->masterProduct?->id !== $newProduct->masterProduct?->id) {
                    $validationErrors[] = 'Same Master Product only can be merge.';
                }

                if ($oldProduct->masterProduct?->type_id !== $newProduct->masterProduct?->type_id) {
                    $validationErrors[] = 'Same Product type only can be merge. Like Regular v/s Regular.';
                }

                if (null !== $newProduct->masterProduct?->article_number && null !== $oldProduct->masterProduct?->article_number && $newProduct->masterProduct->article_number !== $oldProduct->masterProduct->article_number) {
                    $validationErrors[] = "Same Article Number's product only can be merge.";
                }

                if (! (null !== $newProduct->masterProduct?->article_number && null !== $oldProduct->masterProduct?->article_number)) {
                    $validationErrors[] =
                        'One of the product have no article number. Hence, Both products must have no Article Number to be merged.';
                }
            }

            if ($oldProduct->type_id !== $newProduct->type_id) {
                $validationErrors[] = 'Same Product type only can be merge. Like Regular v/s Regular.';
            }

            if (null !== $newProduct->article_number && null !== $oldProduct->article_number && $newProduct->article_number !== $oldProduct->article_number) {
                $validationErrors[] = "Same Article Number's product only can be merge.";
            }

            if (! (null !== $newProduct->article_number && null !== $oldProduct->article_number)) {
                $validationErrors[] = 'Both products must have no Article Number to be merged.';
            }
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);

        $oldUpc = (string) $productDetails['old_upc'];
        $newUpc = (string) $productDetails['new_upc'];

        $oldProduct = $productQueries->getByUpcAndCompanyId($oldUpc, $importRecord->company_id);
        $newProduct = $productQueries->getByUpcAndCompanyId($newUpc, $importRecord->company_id);

        if ($oldProduct instanceof Product && $newProduct instanceof Product) {
            $productQueries->markAsArchived($oldProduct->id, $importRecord->company_id);
            $productQueries->markAsArchived($newProduct->id, $importRecord->company_id);

            $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
            $productCollectionProductQueries->removeByProductId($oldProduct->id, $importRecord->company_id);
            ProductCollectionUpdateByProductJob::dispatch($newProduct->id, $importRecord->company_id)->onQueue(
                'medium'
            );

            ProductMergeJob::dispatch(
                $importRecord->createdBy,
                $oldProduct->id,
                $newProduct->id,
                $importRecord->company_id
            )->onQueue('high');
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(BulkProductMergeImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
