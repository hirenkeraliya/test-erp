<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Imports;

use App\Domains\Batch\BatchQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentImportStoColumns;
use App\Domains\StockAdjustment\Services\StockAdjustmentService;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Batch;
use App\Models\ImportRecord;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;

class ImportStockAdjustmentStoProduct implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);

        $matchProduct = $productQueries->getActiveInventoryProductByUpcForStockAdjustment(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        if (! $matchProduct instanceof Product) {
            $validationErrors[] = 'quantity is required.';

            return $validationErrors;
        }

        $store = null;
        $warehouse = null;

        if (! array_key_exists('location_type', $productDetails) || ! $productDetails['location_type']) {
            $validationErrors[] = 'The Location Type is Required';

            return $validationErrors;
        }

        if (! array_key_exists('location_name', $productDetails) || ! $productDetails['location_name']) {
            $validationErrors[] = 'The Location Name is Required';

            return $validationErrors;
        }

        if (strtoupper($productDetails['location_type']) === LocationTypes::STORE->name) {
            $locationQueries = resolve(LocationQueries::class);
            $store = $locationQueries->getIdAndNameByName(
                $productDetails['location_name'],
                $importRecord->company_id,
                LocationTypes::STORE->value
            );
            if (! $store instanceof Location) {
                $validationErrors[] = 'The Selected Store is Invalid';

                return $validationErrors;
            }
        }

        if (strtoupper($productDetails['location_type']) === LocationTypes::WAREHOUSE->name) {
            $locationQueries = resolve(LocationQueries::class);
            $warehouse = $locationQueries->getIdAndNameByName(
                $productDetails['location_name'],
                $importRecord->company_id,
                LocationTypes::WAREHOUSE->value
            );
            if (! $warehouse instanceof Location) {
                $validationErrors[] = 'The Selected Warehouse is Invalid';

                return $validationErrors;
            }
        }

        /** @var Location $location */
        $location = $store ?? $warehouse;

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getByProductIdWithInventoryUnits($matchProduct->id);

        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $derivative = $unitOfMeasureDerivativeQueries->getDerivativesWithUnitsByName(
            (string) $productDetails['derivative_name'],
            $importRecord->company_id
        );

        $inventory = $inventories->where('product_id', $matchProduct->id)
            ->firstWhere('location_id', $location->id);

        if (! $inventory instanceof Inventory) {
            $validationErrors[] =
            'The specified product UPC ' . $productDetails['upc'] . ' does not exist in our records.';
        }

        if ($inventory instanceof Inventory && null === $productDetails['derivative_name'] && ((float) $inventory->stock + (float) $productDetails['quantity']) < 0) {
            $validationErrors[] =
                'You cannot perform STO for the specified product UPC ' . $productDetails['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . $productDetails['quantity'];
        }

        if ($inventory instanceof Inventory && $derivative instanceof UnitOfMeasureDerivative && null !== $productDetails['derivative_name'] && ((float) $inventory->stock + (float) $productDetails['quantity'] / $derivative->ratio) < 0) {
            $validationErrors[] =
                'You cannot perform STO for the specified product UPC ' . $productDetails['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . (float) $productDetails['quantity'] / $derivative->ratio;
        }

        if ($productDetails['quantity'] > 0) {
            $validationErrors[] =
                'The quantity of the product should be negative for the selected type.';
        }

        $batchQueries = resolve(BatchQueries::class);

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && ! $matchProduct->masterProduct->unit_of_measure_id && $this->isDerivativeNameAttached(
                $productDetails
            )) {
                $validationErrors[] = 'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productDetails['upc'];
            }
        } elseif (! $matchProduct->unit_of_measure_id && $this->isDerivativeNameAttached($productDetails)) {
            $validationErrors[] = 'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productDetails['upc'];
        }

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && $matchProduct->masterProduct->unit_of_measure_id && $this->isDerivativeNameAttached(
                $productDetails
            )) {
                if (! $derivative) {
                    $validationErrors[] =
                        'Derivate name `' . $productDetails['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productDetails['upc'] . '.';

                    return $validationErrors;
                }

                /** @var UnitOfMeasure $unitOfMeasure */
                $unitOfMeasure = $derivative->unitOfMeasure;

                /** @var UnitOfMeasure $productUnitOfMeasure */
                $productUnitOfMeasure = $matchProduct->masterProduct->unitOfMeasure;

                if ($matchProduct->masterProduct->unit_of_measure_id !== $derivative->unit_of_measure_id) {
                    $validationErrors[] =
                        'Derivate name `' . $productDetails['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productDetails['upc'] . ' have UOM `' . $productUnitOfMeasure->name;
                }
            }
        } elseif ($matchProduct->unit_of_measure_id && $this->isDerivativeNameAttached($productDetails)) {
            if (! $derivative) {
                $validationErrors[] =
                    'Derivate name `' . $productDetails['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productDetails['upc'] . '.';

                return $validationErrors;
            }

            /** @var UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $derivative->unitOfMeasure;
            /** @var UnitOfMeasure $productUnitOfMeasure */
            $productUnitOfMeasure = $matchProduct->unitOfMeasure;
            if ($matchProduct->unit_of_measure_id !== $derivative->unit_of_measure_id) {
                $validationErrors[] =
                    'Derivate name `' . $productDetails['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productDetails['upc'] . ' have UOM `' . $productUnitOfMeasure->name;
            }
        }

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && ! $matchProduct->masterProduct->has_batch) {
                return $validationErrors;
            }
        } elseif (! $matchProduct->has_batch) {
            return $validationErrors;
        }

        if (! array_key_exists('batch_number', $productDetails) || ! $productDetails['batch_number']) {
            $validationErrors[] = 'A batch number is required for this product';
        }

        if (! array_key_exists('batch_expiry_date', $productDetails) || ! $productDetails['batch_expiry_date']) {
            $validationErrors[] = 'Batch expiry date is required for this product';
        }

        /** @var ?Batch $batch */
        $batch = $batchQueries->getByNumber((string) $productDetails['batch_number'], $importRecord->company_id);

        if (null === $batch) {
            return $validationErrors;
        }

        if ($batch->product_id !== $matchProduct->id) {
            $validationErrors[] =
                'The batch number of the product with UPC: ' . $productDetails['upc'] . ' has already been used for another product.';
        }

        if ($batch->expiry_date !== $productDetails['batch_expiry_date']) {
            $validationErrors[] =
                'The provided expiry date does not match the current expiry date of the batch with the given number: ' . $batch->number;
        }

        if (! $batch instanceof Batch) {
            $validationErrors[] =
                'Batch number ' . $productDetails['batch_number'] . ' does not exist in our records.';
        }

        $inventoryUnits = $inventory->inventoryUnits;

        $inventoryUnit = $inventoryUnits->firstWhere('batch_id', $batch->id);

        if (! $inventoryUnit instanceof InventoryUnit) {
            $validationErrors[] =
                'inventory unit of upc ' . $productDetails['upc'] . ' does not exist in the our records.';
        }

        $inventoryUnitQuantity = $inventoryUnits->where('batch_id', $batch->id)->sum('quantity');

        if ($inventoryUnitQuantity + (float) $productDetails['quantity'] < 0) {
            $validationErrors[] =
                'You cannot perform STO for the specified product UPC ' . $productDetails['upc'] . ' Because the specified batch number ' . $batch->number . ' only has a stock of ' . $inventoryUnitQuantity . ' quantity, and you have requested to decrease it by  ' . $productDetails['quantity'] . '.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);

        /** @var Product $product */
        $product = $productQueries->getActiveInventoryProductByUpcForStockAdjustment(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $derivative = $unitOfMeasureDerivativeQueries->getDerivativesWithUnitsByName(
            (string) $productDetails['derivative_name'],
            $importRecord->company_id
        );

        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);
        $stockAdjustment = $stockAdjustmentQueries->getById(
            (int) $importRecord->module_id,
            $importRecord->company_id
        );

        $stockAdjustmentService = resolve(StockAdjustmentService::class);
        $stockAdjustmentService->addItemAndInventory(
            $stockAdjustment,
            $productDetails,
            $product,
            $derivative,
            $importRecord->createdBy,
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(StockAdjustmentImportStoColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    private function isDerivativeNameAttached(array $uploadedProduct): bool
    {
        return array_key_exists('derivative_name', $uploadedProduct) && $uploadedProduct['derivative_name'];
    }
}
