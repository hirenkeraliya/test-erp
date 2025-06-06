<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\ProductVariantFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForStoreManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForWarehouseManagerAppData;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteFilterTypes;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\Admin;
use App\Models\GoodsReceivedNote;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GoodsReceivedNoteQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->goodReceiveNoteQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function listQueryForStoreManager(array $filterData, int $companyId, int $locationId): LengthAwarePaginator
    {
        return $this->goodReceiveNoteQuery($filterData, $companyId)
            ->where('location_id', $locationId)
            ->paginate($filterData['per_page']);
    }

    public function listQueryForWarehouseManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->goodReceiveNoteQuery($filterData, $companyId)
            ->where('location_id', $locationId)
            ->paginate($filterData['per_page']);
    }

    public function addNew(
        GoodsReceivedNoteData $goodsReceivedNoteData,
        int $companyId,
        string $grnReferenceNumber,
        Admin|StoreManager|WarehouseManager $user,
    ): GoodsReceivedNote {
        return GoodsReceivedNote::create([
            'company_id' => $companyId,
            'vendor_id' => $goodsReceivedNoteData->vendor_id,
            'location_id' => $goodsReceivedNoteData->location_id,
            'grn_reference' => $grnReferenceNumber,
            'purchase_order_reference' => $goodsReceivedNoteData->purchase_order_reference,
            'delivery_order_reference' => $goodsReceivedNoteData->delivery_order_reference,
            'notes' => $goodsReceivedNoteData->notes,
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->id,
        ]);
    }

    public function addNewForExternalPurchaseOrder(array $goodsReceivedNoteData): GoodsReceivedNote
    {
        return GoodsReceivedNote::create($goodsReceivedNoteData);
    }

    public function addNewForInternalApplication(
        GoodsReceivedNoteStoreForWarehouseManagerAppData|GoodsReceivedNoteStoreForStoreManagerAppData $goodsReceivedNoteData,
        int $companyId,
        string $grnReferenceNumber,
        WarehouseManager|StoreManager $user,
    ): GoodsReceivedNote {
        return GoodsReceivedNote::create([
            'company_id' => $companyId,
            'vendor_id' => $goodsReceivedNoteData->vendor_id,
            'location_id' => $goodsReceivedNoteData->location_id,
            'grn_reference' => $grnReferenceNumber,
            'purchase_order_reference' => $goodsReceivedNoteData->purchase_order_reference,
            'delivery_order_reference' => $goodsReceivedNoteData->delivery_order_reference,
            'notes' => $goodsReceivedNoteData->notes,
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->id,
        ]);
    }

    public function generateGrnReference(string $grnFormat, int $companyId): string
    {
        $goodsReceivedNote = GoodsReceivedNote::select('id', 'grn_reference')
            ->where('company_id', $companyId)
            ->orderByRaw("cast(REPLACE(grn_reference,'" . $grnFormat . "','') as unsigned) DESC")
            ->first();

        $lastGrnReference = null !== $goodsReceivedNote ? $goodsReceivedNote->grn_reference : '0';

        $generatedGrnReference = (int) str_replace($grnFormat, '', (string) $lastGrnReference) + 1;

        return $grnFormat . $generatedGrnReference;
    }

    public function grnReferenceExists(string $grnReferenceNumber, int $companyId): bool
    {
        return GoodsReceivedNote::whereCaseSensitive('grn_reference', $grnReferenceNumber)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getByIdWithGoodsReceivedNoteProduct(int $goodsReceivedNoteId, int $companyId): GoodsReceivedNote
    {
        $companyQueries = resolve(CompanyQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return GoodsReceivedNote::query()
                ->select(
                    'id',
                    'grn_reference',
                    'vendor_id',
                    'purchase_order_reference',
                    'delivery_order_reference',
                    'notes',
                    'company_id',
                    'location_id',
                    'created_by_type',
                    'created_by_id',
                    'created_at',
                )
                ->with(
                    'location:' . $locationQueries->getNameColumnName(),
                    'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                    'company.media:' . $mediaQueries->getBasicColumnNames(),
                    'goodsReceivedNoteProducts:' . $goodsReceivedNoteProductQueries->getBasicColumnNames(),
                    'goodsReceivedNoteProducts.product:' . $productQueries->getBasicColumnNames(),
                    'goodsReceivedNoteProducts.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'goodsReceivedNoteProducts.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'goodsReceivedNoteProducts.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'goodsReceivedNoteProducts.batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                    'vendor:' . $vendorQueries->getBasicColumnNames(),
                    'createdBy:' . $this->basicColumnsForTheCreatedBy(),
                    'createdBy.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                )
                ->where('company_id', $companyId)
                ->findOrFail($goodsReceivedNoteId);
        }

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'grn_reference',
                'vendor_id',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'company_id',
                'location_id',
                'created_by_type',
                'created_by_id',
                'created_at',
            )
            ->with(
                'location:' . $locationQueries->getNameColumnName(),
                'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'company.media:' . $mediaQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts:' . $goodsReceivedNoteProductQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts.product:' . $productQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts.product.color:' . $colorQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts.product.size:' . $sizeQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'goodsReceivedNoteProducts.batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
                'createdBy:' . $this->basicColumnsForTheCreatedBy(),
                'createdBy.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($goodsReceivedNoteId);
    }

    public function getById(int $goodsReceivedNoteId, int $companyId): GoodsReceivedNote
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'grn_reference',
                'vendor_id',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'company_id',
                'location_id',
                'created_by_type',
                'created_by_id',
                'created_at',
            )
            ->with(['importRecord:' . $importRecordQueries->getBasicColumns()])
            ->where('company_id', $companyId)
            ->findOrFail($goodsReceivedNoteId);
    }

    public function getByIdWithSerialNumberRelation(int $goodsReceivedNoteId, int $companyId): GoodsReceivedNote
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $serialNumberQueries = resolve(SerialNumberQueries::class);

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'grn_reference',
                'vendor_id',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'company_id',
                'location_id',
                'created_by_type',
                'created_by_id',
                'created_at',
                'cancelled_at',
            )
            ->with([
                'importRecord:' . $importRecordQueries->getBasicColumns(),
                'goodsReceivedNoteProducts:' . $goodsReceivedNoteProductQueries->getBasicColumnNames(),
                'goodsReceivedNoteProducts.serialNumber:' . $serialNumberQueries->getBasicColumnNames(),
            ])
            ->lockForUpdate()
            ->where('company_id', $companyId)
            ->findOrFail($goodsReceivedNoteId);
    }

    public function getByDateAndLocationsWithGoodsReceivedNoteProduct(array $filterData, int $companyId): Collection
    {
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'location_id',
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'company_id',
                'created_at',
                'created_by_type',
                'created_by_id'
            )
            ->with([
                'createdBy:' . $this->basicColumnsForTheCreatedBy(),
                'createdBy.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            ])
            ->withWhereHas('goodsReceivedNoteProducts', function ($query) use (
                $filterData,
                $goodsReceivedNoteProductQueries,
                $productVariantFilterService
            ): void {
                $query->select(explode(',', $goodsReceivedNoteProductQueries->getQuantityColumnName()))
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_inventory', false);
                                });
                        } else {
                            $query->select('id')
                                ->where('is_non_inventory', false);
                        }
                    })
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value,
                        function ($query) use ($filterData): void {
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
                        }
                    )

                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT_COLLECTION->value,
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_BRAND->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'brand_id',
                                    $filterData['brand_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'department_id',
                                    $filterData['department_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT->value,
                        function ($query) use ($filterData): void {
                            $query->where('product_id', (int) $filterData['product_id']);
                        }
                    );
            })
            ->when(
                (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_VENDOR->value,
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('vendor_id', $filterData['vendor_ids']);
                }
            )
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->whereNull('cancelled_at')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->get();
    }

    public function getByDateAndLocationsWithGRNProductAndProduct(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = ['product:' . $productQueries->getBasicColumnNames()];

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

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'location_id',
                'company_id',
                'created_at',
                'created_by_id',
                'created_by_type',
            )
            ->with([
                'location:' . $locationQueries->getNameColumnName(),
                'createdBy:' . $this->basicColumnsForTheCreatedBy(),
                'createdBy.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            ])
            ->withWhereHas('goodsReceivedNoteProducts', function ($query) use (
                $filterData,
                $goodsReceivedNoteProductQueries,
                $productVariantFilterService,
                $relations
            ): void {
                $query->select(explode(',', $goodsReceivedNoteProductQueries->getBasicColumnNamesForCustomReport()))
                    ->with($relations)
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value,
                        function ($query) use ($filterData): void {
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
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT_COLLECTION->value,
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_BRAND->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'brand_id',
                                    $filterData['brand_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'department_id',
                                    $filterData['department_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT->value,
                        function ($query) use ($filterData): void {
                            $query->where('product_id', (int) $filterData['product_id']);
                        }
                    );
            })
            ->when(
                (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_VENDOR->value,
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('vendor_id', $filterData['vendor_ids']);
                }
            )
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->whereNull('cancelled_at')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function filterByCompanyAndLocation(int $companyId, array $filterData): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('company_id', $companyId)
            ->where('location_id', $filterData['location_id']);
    }

    public function getMorphLocationBasicColumns(): string
    {
        return 'id,name';
    }

    public function getColumns(): string
    {
        return 'id,grn_reference,location_id,vendor_id';
    }

    public function getColumnsForStockCardPrint(): string
    {
        return 'id,grn_reference,notes,created_at';
    }

    public function getLocationColumns(): string
    {
        return 'id,location_id';
    }

    public function getGoodeReceiveNotesExport(array $filterData, int $companyId): Collection
    {
        return $this->goodReceiveNoteQuery($filterData, $companyId)->get();
    }

    public function getGoodeReceiveNotesExportForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->goodReceiveNoteQuery($filterData, $companyId)
            ->where('location_id', $locationId)
            ->get();
    }

    public function getGoodeReceiveNotesExportForWarehouseManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->goodReceiveNoteQuery($filterData, $companyId)
            ->where('location_id', $locationId)
            ->get();
    }

    public function isReferenceNumberExists(string $referenceNumber, int $companyId): bool
    {
        return GoodsReceivedNote::query()
            ->where('company_id', $companyId)
            ->where('grn_reference', $referenceNumber)
            ->exists();
    }

    public function getByDateAndLocationWithProduct(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product:' . $productQueries->getBasicColumnNames(),
            'purchaseAmount:' . $purchaseAmountQueries->getLandedCostColumn(),
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

        return GoodsReceivedNote::query()
            ->select('id', 'location_id')
            ->with(['location:' . $locationQueries->getNameColumnName()])
            ->where('company_id', $companyId)
            ->whereNull('cancelled_at')
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->when(
                (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_VENDOR->value,
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('vendor_id', $filterData['vendor_ids']);
                }
            )
            ->withWhereHas('goodsReceivedNoteProducts', function ($query) use (
                $filterData,
                $goodsReceivedNoteProductQueries,
                $productVariantFilterService,
                $relations
            ): void {
                $query->select(explode(',', $goodsReceivedNoteProductQueries->getBasicColumnNamesForCustomReport()))
                    ->with($relations)
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value,
                        function ($query) use ($filterData): void {
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
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT_COLLECTION->value,
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', $filterData['product_collection_id']);
                            });
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_BRAND->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'brand_id',
                                    $filterData['brand_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value,
                        function ($query) use ($filterData, $productVariantFilterService): void {
                            $query->whereIn(
                                'product_id',
                                $productVariantFilterService->filterByDepartmentAndBrandIds(
                                    'department_id',
                                    $filterData['department_ids']
                                )
                            );
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === GoodsReceivedNoteFilterTypes::BY_PRODUCT->value,
                        function ($query) use ($filterData): void {
                            $query->where('product_id', (int) $filterData['product_id']);
                        }
                    );
            })
            ->get();
    }

    public function listQueryForStoreManagerApi(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->goodReceiveNoteQueryForApi($filterData, $companyId)
            ->where('location_id', $locationId)
            ->paginate($filterData['per_page']);
    }

    public function listQueryForWarehouseManagerApi(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->goodReceiveNoteQueryForApi($filterData, $companyId)
            ->where('location_id', $locationId)
            ->paginate($filterData['per_page']);
    }

    public function markAsCancel(GoodsReceivedNote $goodsReceivedNote, string $remarks, User $user): void
    {
        $goodsReceivedNote->cancelled_at = now()->format('Y-m-d H:i:s');
        $goodsReceivedNote->cancelled_by_type = ModelMapping::getCaseName($user::class);
        $goodsReceivedNote->cancelled_by_id = $user->id;
        $goodsReceivedNote->remarks = $remarks;
        $goodsReceivedNote->save();
    }

    private function basicColumnsForTheCreatedBy(): string
    {
        return 'id,employee_id';
    }

    private function goodReceiveNoteQuery(array $filterData, int $companyId): Builder
    {
        $vendorQueries = resolve(VendorQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return GoodsReceivedNote::query()
            ->select(
                'id',
                'vendor_id',
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'location_id',
                'remarks',
                'cancelled_at',
                'created_at'
            )
            ->where('company_id', $companyId)
            ->with([
                'importRecord:' . $importRecordQueries->getBasicColumns(),
                'importRecord.media:' . $mediaQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getNameColumnName(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['grn_reference', 'purchase_order_reference', 'id'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when($filterData['grn_number'], function ($query) use ($filterData): void {
                $query->where('grn_reference', $filterData['grn_number']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function goodReceiveNoteQueryForApi(array $filterData, int $companyId): Builder
    {
        $vendorQueries = resolve(VendorQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return GoodsReceivedNote::query()
            ->select(
                'goods_received_notes.id',
                'vendor_id',
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'location_id',
                'goods_received_notes.created_at'
            )
            ->where('goods_received_notes.company_id', $companyId)
            ->with([
                'location:' . $locationQueries->getNameColumnName(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['grn_reference', 'purchase_order_reference'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('vendor' === $filterData['sort_by']) {
                    $query->leftJoin('vendors', 'goods_received_notes.vendor_id', '=', 'vendors.id')
                        ->orderBy('vendors.name', $filterData['sort_direction']);
                } else {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('goods_received_notes.id', 'desc');
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where(
                    'goods_received_notes.created_at',
                    '>=',
                    CommonFunctions::addStartTime($filterData['date_range'][0])
                )->where(
                    'goods_received_notes.created_at',
                    '<=',
                    CommonFunctions::addEndTime($filterData['date_range'][1])
                );
            });
    }
}
