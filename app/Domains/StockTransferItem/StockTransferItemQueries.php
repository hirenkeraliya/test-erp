<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\Services\StockTransferInventoryService;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItemBatch\StockTransferItemBatchQueries;
use App\Domains\StockTransferItemTransaction\StockTransferItemTransactionQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StockTransferItemQueries
{
    public function getByStockTransferId(int $stockTransferId, int $companyId): Collection
    {
        return $this->commonGetByStockTransferId($stockTransferId, $companyId)
            ->get();
    }

    public function getByStockTransferIdWithProductAndBatches(int $stockTransferId, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return StockTransferItem::query()
                ->select(
                    'id',
                    'stock_transfer_id',
                    'product_id',
                    'unit_of_measure_derivative_id',
                    'quantity',
                    'received_quantity',
                    'discrepancy_type',
                    'is_extra_item'
                )
                ->with([
                    'product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                    'batches.batch:' . $batchQueries->getBasicColumnNames(),
                    'transactions:' . $stockTransferItemTransactionQueries->getBasicColumns(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                    'unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                ])
                ->where('stock_transfer_id', $stockTransferId)
                ->whereHas('stockTransfer', $stockTransferQueries->filterByCompanyAndDiscrepancyStatus($companyId))
                ->get();
        }

        return StockTransferItem::query()
            ->select(
                'id',
                'stock_transfer_id',
                'product_id',
                'unit_of_measure_derivative_id',
                'quantity',
                'received_quantity',
                'discrepancy_type',
                'is_extra_item'
            )
            ->with([
                'product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'batches.batch:' . $batchQueries->getBasicColumnNames(),
                'transactions:' . $stockTransferItemTransactionQueries->getBasicColumns(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->where('stock_transfer_id', $stockTransferId)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompanyAndDiscrepancyStatus($companyId))
            ->get();
    }

    public function uploadDiscrepancyProof(array $validatedData, int $stockTransferItemId): StockTransferItem
    {
        $stockTransferItem = StockTransferItem::query()
            ->select('id')
            ->findOrFail($stockTransferItemId);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $validatedData['discrepancy_proof'];

        $mediaQueries = resolve(MediaQueries::class);

        $stockTransferItem->addMedia($uploadedFile)->toMediaCollection('discrepancy_proof');
        $stockTransferItem->load('media:' . $mediaQueries->getBasicColumnNames());

        return $stockTransferItem;
    }

    public function removeDiscrepancyProof(int $stockTransferItemId): void
    {
        $stockTransferItem = StockTransferItem::query()
            ->select('id', 'stock_transfer_id')
            ->findOrFail($stockTransferItemId);

        $stockTransferItem->clearMediaCollection('discrepancy_proof');

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferQueries->setUpdatedAtById($stockTransferItem->stock_transfer_id);
    }

    public function getColumnNames(): string
    {
        return 'id,stock_transfer_id,product_id,unit_of_measure_derivative_id,quantity,received_quantity,discrepancy_type,package_type_id,package_quantity,is_extra_item';
    }

    public function getColumnNamesForPrint(): string
    {
        return 'id,stock_transfer_id,package_type_id,package_quantity,package_total_quantity,product_id,quantity,discrepancy_type,received_quantity,unit_of_measure_derivative_id';
    }

    public function getColumnNamesForRequestEdit(): string
    {
        return 'id,stock_transfer_id,package_type_id,unit_of_measure_derivative_id,package_quantity,package_total_quantity,product_id,quantity,discrepancy_type,received_quantity';
    }

    public function addNew(array $itemDetails): StockTransferItem
    {
        return StockTransferItem::create($itemDetails);
    }

    public function createMany(StockTransfer $stockTransfer, array $itemDetails): void
    {
        $stockTransfer->items()->createMany($itemDetails);
    }

    public function updateReceivedQuantityAndDiscrepancyStatusByIdAndStockTransferId(
        array $stockTransferItemDetails,
        int $stockTransferId,
        int $companyId
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferItem = StockTransferItem::query()
            ->select(
                'id',
                'is_extra_item',
                'received_quantity',
                'quantity',
                'discrepancy_type',
                'package_total_quantity'
            )
            ->where('stock_transfer_id', $stockTransferId)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId))
            ->findOrFail((int) $stockTransferItemDetails['item_id']);

        $stockTransferItem->received_quantity = $stockTransferItemDetails['received_quantity'];
        $stockTransferItem->package_total_quantity = $stockTransferItemDetails['received_quantity'];
        $stockTransferItem->discrepancy_type = false === $stockTransferItem->is_extra_item ? $stockTransferItemDetails['status'] : null;
        $stockTransferItem->save();
    }

    public function updateShippingDetailsRecordsById(
        array $stockTransferItemDetails,
        int $stockTransferId,
        int $companyId
    ): void {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferItem = StockTransferItem::query()
            ->where('stock_transfer_id', $stockTransferId)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId))
            ->findOrFail((int) $stockTransferItemDetails['id']);
        $stockTransferItem->package_type_id = $stockTransferItemDetails['package_type_id'] ?? null;
        $stockTransferItem->package_quantity = $stockTransferItemDetails['package_quantity'] ?? null;
        $stockTransferItem->package_total_quantity = $stockTransferItemDetails['package_total_quantity'] ?? null;
        $stockTransferItem->save();
    }

    public function setReceivedQuantitySameAsQuantity(int $stockTransferId, int $companyId): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferItems = StockTransferItem::query()
            ->select('id', 'received_quantity', 'quantity')
            ->where('stock_transfer_id', $stockTransferId)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId))
            ->get();

        foreach ($stockTransferItems as $stockTransferItem) {
            $stockTransferItem->update([
                'received_quantity' => $stockTransferItem->quantity,
                'discrepancy_type' => null,
            ]);

            $stockTransferItem->clearMediaCollection('discrepancy_proof');
        }

        $stockTransferQueries->setUpdatedAtById($stockTransferId);
    }

    public function loadUnits(StockTransferItem $stockTransferItem): StockTransferItem
    {
        return $stockTransferItem->load('units');
    }

    public function getByIdWithRelations(int $stockTransferItemId): StockTransferItem
    {
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferItemBatchQueries = resolve(StockTransferItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);

        return StockTransferItem::query()
            ->select(
                'id',
                'stock_transfer_id',
                'product_id',
                'quantity',
                'received_quantity',
                'discrepancy_type',
                'package_type_id',
                'package_quantity',
            )
            ->with(
                'batches:' . $stockTransferItemBatchQueries->getBasicColumnNames(),
                'batches.batch:' . $batchQueries->getBasicColumnNames(),
                'units:' . $stockTransferItemUnitQueries->getColumnNames(),
                'units.batch:' . $batchQueries->getBasicColumnNames(),
            )
            ->findOrFail($stockTransferItemId);
    }

    public function deleteItemAndBatches(StockTransfer $stockTransfer): void
    {
        $stockTransferItems = StockTransferItem::select('id')->where(
            'stock_transfer_id',
            $stockTransfer->id
        )->lockForUpdate()->get();
        $stockTransferInventoryService = resolve(StockTransferInventoryService::class);
        $stockTransferItems->each(function ($item) use ($stockTransferInventoryService): void {
            $stockTransferInventoryService->revertReservedStock($item);
            $item->batches()->lockForUpdate()->delete();
            $item->units()->lockForUpdate()->delete();
            $item->delete();
        });
    }

    public function getStatusById(int $stockTransferItemId): int
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferItem = StockTransferItem::select('id', 'stock_transfer_id')
            ->with('stockTransfer:' . $stockTransferQueries->getStatusColumn())
            ->where('id', $stockTransferItemId)
            ->firstOrFail();

        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $stockTransferItem->stockTransfer;

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferQueries->setUpdatedAt($stockTransfer);

        return $stockTransfer->status;
    }

    public function removeAdditionalItemAndRelations(int $stockTransferItemId): void
    {
        $stockTransferItem = StockTransferItem::select('id', 'stock_transfer_id')
            ->where('is_extra_item', true)
            ->where('id', $stockTransferItemId)
            ->firstOrFail();

        $stockTransferItem->delete();

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferQueries->setUpdatedAtById($stockTransferItem->stock_transfer_id);
    }

    public function getProductIdsBy(int $stockTransferId): Collection
    {
        return StockTransferItem::select('id', 'product_id')
            ->where('stock_transfer_id', $stockTransferId)
            ->get();
    }

    public function getWithProductAndStockTransferForStockOutReport(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return StockTransferItem::query()
            ->select('id', 'stock_transfer_id', 'product_id', 'received_quantity')
            ->with(
                'stockTransfer:' . $stockTransferQueries->getColumnsForReport(),
                'stockTransfer.destinationLocation:' . $stockTransferQueries->getLocationColumnName(),
                'product:' . $productQueries->getColumnsForInventoryReports(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.brand:' . $brandQueries->getIdAndNameColumnNames(),
            )
            ->where('received_quantity', '>', 0)
            ->whereHas('product', function ($query): void {
                $query->where('retail_price', '>', 0);
            })
            ->when($filterData['department_id'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', $productQueries->filterByDepartmentId((int) $filterData['department_id']));
            })
            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when(null !== $filterData['brand_id'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->where('brand_id', $filterData['brand_id']);
                });
            })
            ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('id')
                        ->from('products')
                        ->where('article_number', $filterData['article_number']);
                });
            })
            ->whereHas('stockTransfer', $stockTransferQueries->filterForStockOutReport($companyId, $filterData))
            ->get();
    }

    public function getWithProductAndStockTransferForStockInReport(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return StockTransferItem::query()
            ->select('id', 'stock_transfer_id', 'product_id', 'received_quantity')
            ->with(
                'stockTransfer:' . $stockTransferQueries->getColumnsForReport(),
                'stockTransfer.sourceLocation:' . $stockTransferQueries->getLocationColumnName(),
                'product:' . $productQueries->getColumnsForInventoryReports(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.brand:' . $brandQueries->getIdAndNameColumnNames(),
            )
            ->whereHas('product', function ($query): void {
                $query->where('retail_price', '>', 0);
            })
            ->where('received_quantity', '>', 0)
            ->when($filterData['department_id'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', $productQueries->filterByDepartmentId((int) $filterData['department_id']));
            })
            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when(null !== $filterData['brand_id'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->where('brand_id', $filterData['brand_id']);
                });
            })
            ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('id')
                        ->from('products')
                        ->where('article_number', $filterData['article_number']);
                });
            })
            ->whereHas('stockTransfer', $stockTransferQueries->filterForStockInReport($companyId, $filterData))
            ->get();
    }

    public function getByDateAndLocationWithProduct(array $filterData, int $companyId): Collection
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $locationQuery = function ($query) use ($filterData): void {
            $query->where(function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('source_location_id', $filterData['location_ids']);
            })
            ->orWhere(function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('destination_location_id', $filterData['location_ids']);
            });
        };

        $relations = [
            'product' => $productQueries->getSellingProduct(),
            'stockTransfer:' . $stockTransferQueries->getStockTransferColumnsForReport(),
            'stockTransfer.sourceLocation:' . $stockTransferQueries->getLocationColumnName(),
            'stockTransfer.destinationLocation:' . $stockTransferQueries->getLocationColumnName(),
            'packageType:' . $packageTypeQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return StockTransferItem::query()
            ->select('id', 'product_id', 'stock_transfer_id', 'package_type_id', 'quantity', 'received_quantity')
            ->with($relations)
            ->whereHas('product', function ($query): void {
                if (config('app.product_variant')) {
                    $query->select('id', 'master_product_id')
                        ->whereHas('masterProduct', function ($query): void {
                            $query->where('is_non_selling_item', false);
                        });
                } else {
                    $query->select('id')
                        ->where('is_non_selling_item', false);
                }
            })
            ->whereHas('stockTransfer', function ($query) use (
                $companyId,
                $filterData,
                $stockTransferQueries,
                $locationQuery
            ): void {
                $query->where($stockTransferQueries->filterByCompany($companyId))
                    ->where($stockTransferQueries->filterByDateType($filterData))
                    ->where(function ($query) use ($locationQuery): void {
                        $query->where(function ($query) use ($locationQuery): void {
                            $query->where('status', StatusTypes::DRAFT->value)
                                ->where($locationQuery);
                        })->orWhere(function ($query) use ($locationQuery): void {
                            $query->where('status', StatusTypes::SYSTEM_GENERATED->value)
                                ->where($locationQuery);
                        })->orWhere(function ($query) use ($locationQuery): void {
                            $query->whereNot('status', StatusTypes::DRAFT->value)
                                ->whereNot('status', StatusTypes::SYSTEM_GENERATED->value)
                                ->where($locationQuery);
                        });
                    })
                    ->when(
                        (int) $filterData['transfer_type'] === TransferTypeForReport::REQUEST_ORDER->value,
                        function ($query): void {
                            $query->where('transfer_type', StockTransferTypes::REQUEST_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                            ]);
                        }
                    )
                    ->when(
                        (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_ORDER->value,
                        function ($query): void {
                            $query->where('transfer_type', StockTransferTypes::TRANSFER_ORDER->value)
                            ->whereIntegerInRaw('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ]);
                        }
                    )
                    ->when(
                        (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_IN->value,
                        function ($query) use ($filterData): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ])
                            ->whereIntegerInRaw('destination_location_id', $filterData['location_ids'])
                            ->when(
                                $filterData['additional_location_id'],
                                function ($query) use ($filterData): void {
                                    $query->where('source_location_id', $filterData['additional_location_id']);
                                }
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['transfer_type'] === TransferTypeForReport::TRANSFER_OUT->value,
                        function ($query) use ($filterData): void {
                            $query->whereNotIn('status', [
                                StatusTypes::DRAFT->value,
                                StatusTypes::SYSTEM_GENERATED->value,
                                StatusTypes::OPEN->value,
                                StatusTypes::CANCELLED->value,
                                StatusTypes::REJECTED->value,
                                StatusTypes::APPROVED->value,
                            ])
                            ->whereIntegerInRaw('source_location_id', $filterData['location_ids'])
                            ->when(
                                $filterData['additional_location_id'],
                                function ($query) use ($filterData): void {
                                    $query->where('destination_location_id', $filterData['additional_location_id']);
                                }
                            );
                        }
                    )
                    ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when($filterData['status_type'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('status', $filterData['status_type']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
            })
            ->get();
    }

    public function getProductIdWithQuantity(
        string $referenceNumber,
        int $selectedModuleFilter,
        int $companyId
    ): Collection {
        return StockTransferItem::query()
            ->select('id', 'stock_transfer_id', 'product_id', 'quantity', 'received_quantity')
            ->where('stock_transfer_id', function ($query) use (
                $selectedModuleFilter,
                $referenceNumber,
                $companyId
            ): void {
                $query->select('id')
                    ->from('stock_transfers')
                    ->when(
                        $selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_ORDER->value,
                        function ($query) use ($referenceNumber): void {
                            $query->where('transfer_order_number', $referenceNumber);
                        }
                    )
                    ->when(
                        $selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_IN->value,
                        function ($query) use ($referenceNumber): void {
                            $query->where('transfer_in_number', $referenceNumber);
                        }
                    )
                    ->when(
                        $selectedModuleFilter === BarcodePrintModuleTypes::TRANSFER_OUT->value,
                        function ($query) use ($referenceNumber): void {
                            $query->where('transfer_out_number', $referenceNumber);
                        }
                    )
                    ->when(
                        $selectedModuleFilter === BarcodePrintModuleTypes::REQUEST_ORDER->value,
                        function ($query) use ($referenceNumber): void {
                            $query->where('request_order_number', $referenceNumber);
                        }
                    )
                    ->where('company_id', $companyId);
            })
            ->get();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferItems = StockTransferItem::query()
            ->select('id', 'stock_transfer_id', 'product_id')
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($stockTransferItems as $stockTransferItem) {
            $stockTransferItem->product_id = $newProductId;
            $stockTransferItem->save();
        }
    }

    public function getMorphUserBasicColumns(): string
    {
        return 'id,employee_id';
    }

    public function getStockTransferIdColumn(): string
    {
        return 'id,stock_transfer_id';
    }

    public function getByPaginatedStockTransferId(array $filterData, int $companyId): LengthAwarePaginator
    {
        $productQueries = resolve(ProductQueries::class);

        return $this->commonGetByStockTransferId((int) $filterData['id'], $companyId)
            ->whereHas('product', $productQueries->searchByNameAndUpc($filterData['search_text']))
            ->paginate($filterData['per_page']);
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getStockTransferWithLocation(): Closure
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);

        return fn ($query) => $query->select('id', 'stock_transfer_id')
            ->with([
                'stockTransfer:' . $stockTransferQueries->getStockTransferColumnsForStockCardPrint(),
                'stockTransfer.sourceLocation:' . $stockTransferQueries->getLocationColumnName(),
                'stockTransfer.destinationLocation:' . $stockTransferQueries->getLocationColumnName(),
            ]);
    }

    private function commonGetByStockTransferId(int $stockTransferId, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $stockTransferItemTransactionQueries = resolve(StockTransferItemTransactionQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return StockTransferItem::query()
                ->select(
                    'id',
                    'stock_transfer_id',
                    'product_id',
                    'unit_of_measure_derivative_id',
                    'quantity',
                    'received_quantity',
                    'discrepancy_type',
                    'is_extra_item'
                )
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'transactions:' . $stockTransferItemTransactionQueries->getColumns(),
                    'transactions.user:' . $this->getMorphUserBasicColumns(),
                    'transactions.user.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                    'unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames()
                )
                ->where('stock_transfer_id', $stockTransferId)
                ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId));
        }

        return StockTransferItem::query()
            ->select(
                'id',
                'stock_transfer_id',
                'product_id',
                'unit_of_measure_derivative_id',
                'quantity',
                'received_quantity',
                'discrepancy_type',
                'is_extra_item'
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'transactions:' . $stockTransferItemTransactionQueries->getColumns(),
                'transactions.user:' . $this->getMorphUserBasicColumns(),
                'transactions.user.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'unitOfMeasureDerivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames()
            )
            ->where('stock_transfer_id', $stockTransferId)
            ->whereHas('stockTransfer', $stockTransferQueries->filterByCompany($companyId));
    }
}
