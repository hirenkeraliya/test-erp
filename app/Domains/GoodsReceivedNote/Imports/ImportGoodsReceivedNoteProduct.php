<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Imports;

use App\Domains\Batch\BatchQueries;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteImportColumns;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Batch;
use App\Models\ImportRecord;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Carbon\Carbon;

class ImportGoodsReceivedNoteProduct implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);

        $matchProduct = $productQueries->getActiveInventoryProductByUpcForGRN(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        if (null === $matchProduct) {
            $validationErrors[] = 'Provided UPC is not matching in our records.';

            return $validationErrors;
        }

        if (null === $productDetails['quantity']) {
            $validationErrors[] = 'Quantity is required.';

            return $validationErrors;
        }

        $productTypeId = config(
            'app.product_variant'
        ) ? $matchProduct->masterProduct?->type_id : $matchProduct->type_id;
        $hasBatch = config('app.product_variant') ? $matchProduct->masterProduct?->has_batch : $matchProduct->has_batch;
        $unitOfMeasureId = config(
            'app.product_variant'
        ) ? $matchProduct->masterProduct?->unit_of_measure_id : $matchProduct->unit_of_measure_id;

        if ($productTypeId === ProductTypes::SERIAL_PRODUCT->value && (! array_key_exists(
            'serial_number',
            $productDetails
        ) || null === $productDetails['serial_number'])) {
            $validationErrors[] = 'This product is serial product so please enter the serial number.';
        }

        if (array_key_exists('serial_number', $productDetails) && null !== $productDetails['serial_number']) {
            $isSerialProductExists = $this->checkSerialProductExists(
                $importRecord->company_id,
                trim((string) $productDetails['serial_number'])
            );

            if ($isSerialProductExists) {
                $validationErrors[] = 'Serial Number is already exists in our records.';
            }

            if ($hasBatch) {
                $validationErrors[] = 'Batch product is not allow the serial number.';
            }

            if ($productTypeId !== ProductTypes::SERIAL_PRODUCT->value) {
                $validationErrors[] = 'This product is not the serial product.';
            }

            if ($unitOfMeasureId) {
                $validationErrors[] = 'Derivative product is not allow the serial number.';
            }

            if (1 != $productDetails['quantity']) {
                $validationErrors[] = 'Only one quantity allow when you have serial number.';
            }
        }

        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $derivative = $unitOfMeasureDerivativeQueries->getDerivativesWithUnitsByName(
            (string) $productDetails['derivative_name'],
            $importRecord->company_id
        );

        $batchQueries = resolve(BatchQueries::class);
        $batch = $batchQueries->getByNumber((string) $productDetails['batch_number'], $importRecord->company_id);

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && ! $matchProduct->masterProduct->unit_of_measure_id && $this->isDerivativeNameAttached(
                $productDetails
            )) {
                $validationErrors[] =
                    'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productDetails['upc'] . '.';
            }
        } elseif (! $matchProduct->unit_of_measure_id && $this->isDerivativeNameAttached($productDetails)) {
            $validationErrors[] =
                'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productDetails['upc'] . '.';
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
                $productUnitOfMeasure = $derivative->unitOfMeasure;

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
            $productUnitOfMeasure = $derivative->unitOfMeasure;
            if ($matchProduct->unit_of_measure_id !== $derivative->unit_of_measure_id) {
                $validationErrors[] =
                    'Derivate name `' . $productDetails['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productDetails['upc'] . ' have UOM `' . $productUnitOfMeasure->name;
            }
        }

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && ! $matchProduct->masterProduct->has_batch && (! array_key_exists(
                'batch_number',
                $productDetails
            ) || ! array_key_exists(
                'batch_expiry_date',
                $productDetails
            ) || $productDetails['batch_number'] || $productDetails['batch_expiry_date'])) {
                $validationErrors[] =
                    'Batch number is not required for the product with UPC ' . $productDetails['upc'] . '.';
            }
        } elseif (! $matchProduct->has_batch && (! array_key_exists(
            'batch_number',
            $productDetails
        ) || ! array_key_exists(
            'batch_expiry_date',
            $productDetails
        ) || $productDetails['batch_number'] || $productDetails['batch_expiry_date'])) {
            $validationErrors[] =
                'Batch number is not required for the product with UPC ' . $productDetails['upc'] . '.';
        }

        if (config('app.product_variant')) {
            if ($matchProduct->masterProduct && ! $matchProduct->masterProduct->has_batch) {
                return $validationErrors;
            }
        } elseif (! $matchProduct->has_batch) {
            return $validationErrors;
        }

        if (! array_key_exists('batch_number', $productDetails) || ! $productDetails['batch_number']) {
            $validationErrors[] =
                'Batch number is required for the batch product with UPC ' . $productDetails['upc'] . '.';
        }

        if (! array_key_exists('batch_expiry_date', $productDetails) || ! $productDetails['batch_expiry_date']) {
            $validationErrors[] =
                'Batch expiry date is required for the batch product with UPC ' . $productDetails['upc'] . '.';
        }

        if ($productDetails['batch_expiry_date'] < Carbon::now()->format('Y-m-d')) {
            $validationErrors[] =
                'Batch expiry date must be a date in the future. But the specified date is ' . $productDetails['batch_expiry_date'] . '.';
        }

        if (! $batch instanceof Batch) {
            return $validationErrors;
        }

        if ($batch->product_id !== $matchProduct->id) {
            $validationErrors[] =
                'Batch number of the batch product with UPC: ' . $productDetails['upc'] . ' is already used for another product.';
        }

        if ($batch->expiry_date !== $productDetails['batch_expiry_date']) {
            $validationErrors[] =
                'The provided expiry date' . $productDetails['batch_expiry_date'] . ' does not match with the current expiry date of the batch with number: ' . $batch->number;
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);

        /** @var Product $actualProducts */
        $actualProducts = $productQueries->getActiveInventoryProductByUpcForGRN(
            (string) $productDetails['upc'],
            $importRecord->company_id
        );

        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $derivative = $unitOfMeasureDerivativeQueries->getDerivativesWithUnitsByName(
            (string) $productDetails['derivative_name'],
            $importRecord->company_id
        );

        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $goodsReceivedNote = $goodsReceivedNoteQueries->getById(
            (int) $importRecord->module_id,
            $importRecord->company_id
        );

        $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceivedNote,
            $productDetails,
            $actualProducts,
            $importRecord->createdBy,
            $importRecord->company_id,
            $derivative
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(GoodsReceivedNoteImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    public function checkSerialProductExists(int $companyId, string $serialNumber): bool
    {
        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $serialNumber = $serialNumberQueries->checkSerialNumberExists($serialNumber, $companyId);

        if (! $serialNumber) {
            return false;
        }

        if ($serialNumber->status === SerialNumberStatus::DELETED->value) {
            return false;
        }

        if ($serialNumber->status !== SerialNumberStatus::SOLD->value) {
            return true;
        }

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->existsBySerialNumberIdAndInventoryId($serialNumber->id);

        return ! $inventoryUnit;
    }

    private function isDerivativeNameAttached(array $uploadedProduct): bool
    {
        return array_key_exists('derivative_name', $uploadedProduct) && $uploadedProduct['derivative_name'];
    }
}
