<?php

declare(strict_types=1);

namespace App\Domains\Product;

use App\CommonFunctions;
use App\Domains\Admin\AdminQueries;
use App\Domains\AssemblyMasterProduct\AssemblyChildMasterProductQueries;
use App\Domains\AssemblyProduct\AssemblyChildProductQueries;
use App\Domains\AttachedTemplate\AttachedTemplateQueries;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\BoxProductLoyaltyPoint\BoxProductLoyaltyPointQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CustomFieldValue\CustomFieldValueQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\DraftProductTransaction\DraftProductTransactionQueries;
use App\Domains\DraftProductTransaction\Jobs\CreateDraftProductTransactionsJob;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\Media\MediaQueries;
use App\Domains\MergeProductTransaction\MergeProductTransactionQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductDataForIntegration;
use App\Domains\Product\DataObjects\ProductImageUploadByArticleNumberData;
use App\Domains\Product\DataObjects\ProductImageUploadData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\PurchaseType;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Services\PosProductExportZipService;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Domains\ProductAgeingReport\Enums\AgeCategories;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\Admin;
use App\Models\Product;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class ProductQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $tagQueries = new TagQueries();
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $adminQueries = resolve(AdminQueries::class);
        $draftProductTransactionQueries = resolve(DraftProductTransactionQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $selectedColumns = [
            'id',
            'name',
            'code',
            'department_id',
            'upc',
            'verification_qr_code',
            'article_number',
            'retail_price',
            'purchase_cost',
            'status',
            'created_by_id',
            'created_by_type',
            'created_at',
            'updated_at',
            'original_created_at',
            'last_editor_by_id',
            'last_editor_by_type',
            'description',
            'unit_of_measure_id',
            'season_id',
            'sub_department_id',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'is_temporarily_unavailable',
            'has_batch',
            'type_id',
            'is_non_selling_item',
            'is_non_inventory',
            'staff_price',
            'purchase_cost',
            'online_price',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'vendor_id',
        ];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, ['master_product_id']);
        } else {
            $selectedColumns = array_merge($selectedColumns, ['brand_id', 'color_id', 'size_id', 'style_id']);
        }

        $relations = [
            'productChannelReference:' . $productChannelReferenceQueries->getBasicColumnNames(),
            'draftProductTransaction:' . $draftProductTransactionQueries->getBasicColumnNames(),
            'draftProductTransaction.approvedBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'lastEditorBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'season:' . $seasonQueries->getBasicColumnNames(),
            'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            'vendor:' . $vendorQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'masterProduct.createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ]);
        }

        return Product::query()
            ->select(...$selectedColumns)
            ->with($relations)
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $this->searchForList($query, $filterData, $brandQueries, $categoryQueries);
                    $this->searchForList($query, $filterData, $brandQueries, $categoryQueries);
                });
            })
            ->when($filterData['product_sync_type_id'], function ($query) use (
                $filterData,
                $saleChannelQueries
            ): void {
                $query->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::SYNC_WITH_ECOMMERCE->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->whereHas('productChannelReference', function ($query) use (
                            $saleChannelQueries
                        ): void {
                            $query->whereHas(
                                'saleChannel',
                                $saleChannelQueries->filterByTypeId(SaleChannelTypes::ECOMMERCE->value)
                            );
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::SYNC_WITH_WEBSPERT->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->whereHas('productChannelReference', function ($query) use (
                            $saleChannelQueries
                        ): void {
                            $query->whereHas(
                                'saleChannel',
                                $saleChannelQueries->filterByTypeId(SaleChannelTypes::WEBSPERT_ECOMMERCE->value)
                            );
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::NOT_SYNC_WITH_ECOMMERCE->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->where(function ($query) use ($saleChannelQueries): void {
                            $query->whereDoesntHave('productChannelReference')
                                ->orWhereHas('productChannelReference', function ($query) use (
                                    $saleChannelQueries
                                ): void {
                                    $query->whereHas(
                                        'saleChannel',
                                        $saleChannelQueries->filterByTypeId(SaleChannelTypes::ECOMMERCE->value, '!=')
                                    );
                                });
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::NOT_SYNC_WITH_WEBSPERT->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->where(function ($query) use ($saleChannelQueries): void {
                            $query->whereDoesntHave('productChannelReference')
                                ->orWhereHas('productChannelReference', function ($query) use (
                                    $saleChannelQueries
                                ): void {
                                    $query->whereHas(
                                        'saleChannel',
                                        $saleChannelQueries->filterByTypeId(
                                            SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
                                            '!='
                                        )
                                    );
                                });
                        });
                    }
                );
            })
            ->where('company_id', $companyId)
            ->whereNot('status', Statuses::DRAFT->value)
            ->tap(fn ($query): Builder => $this->commonFilterQuery($query, $filterData))
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }
            })
            ->paginate($filterData['per_page']);
    }

    public function fetchDraftList(array $filterData, int $companyId): LengthAwarePaginator
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $tagQueries = new TagQueries();
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $adminQueries = resolve(AdminQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $productVariantEnabled = config('app.product_variant');
        $commonColumns = $this->commonSelectedColumnsForDraftProducts();
        $query = $this->getCommonQueryForDraftProduct($commonColumns, $filterData, $companyId);

        $relations = [
            'media:' . $mediaQueries->getBasicColumnNames(),
            'tags:' . $tagQueries->getBasicColumnNames(),
        ];

        if ($productVariantEnabled) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'masterProduct.createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
            ]);
        }

        return $query->with($relations)->paginate($filterData['per_page']);
    }

    public function matchProductCount(Product $draftProduct, int $companyId): int
    {
        return $this->applyConditionForMatchProduct(Product::where('company_id', $companyId), $draftProduct)->count();
    }

    public function getMatchActiveProductsByDraftIdAndCompanyId(int $productId, int $companyId): Collection
    {
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        if (config('app.product_variant')) {
            /** @var Product $product */
            $product = $this->commonSelectedColumnsForDraftProducts()
                ->where('company_id', $companyId)
                ->with([
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                ])
                ->where('status', Statuses::DRAFT->value)
                ->findOrFail($productId);

            return $this->applyConditionForMatchProduct(
                $this->commonSelectedColumnsForDraftProducts()
                    ->where('company_id', $companyId)
                    ->with([
                        'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                        'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                        'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                        'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                        'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                        'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                        'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    ]),
                $product
            )->get();
        }

        /** @var Product $product */
        $product = $this->commonSelectedColumnsForDraftProducts()
            ->where('company_id', $companyId)
            ->with('categories:' . $categoryQueries->getBasicColumnNames())
            ->where('status', Statuses::DRAFT->value)
            ->findOrFail($productId);

        return $this->applyConditionForMatchProduct(
            $this->commonSelectedColumnsForDraftProducts()
                ->where('company_id', $companyId)
                ->with([
                    'brand:' . $brandQueries->getBasicColumnNames(),
                    'color:' . $colorQueries->getBasicColumnNames(),
                    'size:' . $sizeQueries->getBasicColumnNames(),
                    'style:' . $styleQueries->getBasicColumnNames(),
                    'department:' . $departmentQueries->getBasicColumnNames(),
                    'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'season:' . $seasonQueries->getBasicColumnNames(),
                    'categories:' . $categoryQueries->getBasicColumnNames(),
                ]),
            $product
        )->get();
    }

    public function getCurrentUserProductCount(array $draftProductIds, int $userId): int
    {
        if (config('app.product_variant')) {
            return Product::whereIntegerInRaw('id', $draftProductIds)
                ->where('status', Statuses::DRAFT->value)
                ->whereHas('masterProduct', function ($query) use ($userId): void {
                    $query->where('created_by_id', $userId)
                        ->where('created_by_type', ModelMapping::ADMIN->name);
                })
                ->count();
        }

        return Product::whereIntegerInRaw('id', $draftProductIds)
            ->where('status', Statuses::DRAFT->value)
            ->where('created_by_id', $userId)
            ->where('created_by_type', ModelMapping::ADMIN->name)
            ->count();
    }

    public function getDraftProductIdsByExceptLoginUser(array $filterData, int $companyId, int $userId): Collection
    {
        return $this->getCommonQueryForDraftProduct(
            $this->commonSelectedColumnsForDraftProducts(),
            $filterData,
            $companyId
        )
            ->select('id')
            ->whereNot('created_by_id', $userId)
            ->where('created_by_type', ModelMapping::ADMIN->name)
            ->get();
    }

    public function getDraftProductIdsByCompanyLevel(array $filterData, int $companyId): Collection
    {
        return $this->getCommonQueryForDraftProduct(
            $this->commonSelectedColumnsForDraftProducts(),
            $filterData,
            $companyId
        )
            ->select('id')
            ->get();
    }

    private function getCommonQueryForDraftProduct(Builder $query, array $filterData, int $companyId): Builder
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $query->where('company_id', $companyId)->where('status', Statuses::DRAFT->value)
            ->when(config('app.product_variant'), fn ($q) => $q->whereNotNull('master_product_id'))
            ->when($filterData['search_text'], fn ($q) => $q->where(
                fn ($q) => $q
                    ->whereAny(
                        array_filter([
                            'compound_product_name',
                            'code',
                            'upc',
                            'retail_price',
                            'purchase_cost',
                            'ean',
                            'custom_sku',
                            config('app.product_variant') ? null : 'article_number',
                        ]),
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                    ->when(
                        config('app.product_variant'),
                        fn ($q) => $q
                            ->orWhereHas(
                                'masterProduct',
                                $masterProductQueries->searchArticleNumber($filterData['search_text'])
                            )
                            ->orWhereHas('masterProduct.brand', $brandQueries->searchByName($filterData['search_text']))
                            ->orWhereHas(
                                'masterProduct.categories',
                                $categoryQueries->searchByName($filterData['search_text'])
                            )
                    )
                    ->when(
                        ! config('app.product_variant'),
                        fn ($q) => $q
                            ->orWhereHas('brand', $brandQueries->searchByName($filterData['search_text']))
                            ->orWhereHas('categories', $categoryQueries->searchByName($filterData['search_text']))
                    )
            ))
            ->when(
                $filterData['sort_by'],
                fn ($q) => $q->orderBy($filterData['sort_by'], $filterData['sort_direction']),
                fn ($q) => $q->orderBy('id', 'desc')
            )
            ->when(
                $filterData['date_range'],
                fn ($q) => $q->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            )
            ->when(
                $filterData['product_type_id'],
                fn ($q) => $q->where('type_id', (int) $filterData['product_type_id'])
            )
            ->when(
                $filterData['attributes'],
                fn ($q) => $q->whereHas('productVariantValues', fn ($q) => $q->select('id')->whereIn(
                    'value',
                    $filterData['attributes']
                ))
            )
            ->when(
                $filterData['category_ids'],
                fn ($q) => $q->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']))
            )
            ->when(
                $filterData['tag_ids'],
                fn ($q) => $q->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']))
            )
            ->when(
                $filterData['brand_ids'],
                fn ($q) => $q->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids'])
            )
            ->when(
                $filterData['color_ids'],
                fn ($q) => $q->whereIntegerInRaw('color_id', (array) $filterData['color_ids'])
            )
            ->when(
                $filterData['size_ids'],
                fn ($q) => $q->whereIntegerInRaw('size_id', (array) $filterData['size_ids'])
            )
            ->when(
                $filterData['department_ids'],
                fn ($q) => $q->whereIntegerInRaw('department_id', (array) $filterData['department_ids'])
            )
            ->when(
                $filterData['style_ids'],
                fn ($q) => $q->whereIntegerInRaw('style_id', (array) $filterData['style_ids'])
            )
            ->when(
                $filterData['article_numbers'],
                fn ($q) => $q->whereIn('article_number', (array) $filterData['article_numbers'])
            )
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], fn ($q) => $q->where('has_batch', true))
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], fn ($q) => $q->where('has_batch', false))
            ->when(
                $filterData['employee_id'],
                fn ($q) => $q->where(
                    'created_by_id',
                    (int) $filterData['employee_id']
                )->where('created_by_type', ModelMapping::ADMIN->name)
            );

        return $query;
    }

    public function listQueryForStoreManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->productLists($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getByIds(array $productIds): Collection
    {
        return Product::select('name')
            ->whereIntegerInRaw('id', $productIds)
            ->get();
    }

    public function getProductsWithRelationsForPrint(array $filterData, int $companyId): Collection
    {
        $brandQueries = new BrandQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $departmentQueries = new DepartmentQueries();
        $categoryQueries = new CategoryQueries();
        $styleQueries = new StyleQueries();
        $productChannelReferenceQueries = new ProductChannelReferenceQueries();
        $productCollectionProductQueries = new ProductCollectionProductQueries();
        $adminQueries = new AdminQueries();
        $draftProductTransactionQueries = new DraftProductTransactionQueries();
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $selectedColumns = [
            'id',
            'name',
            'code',
            'brand_id',
            'department_id',
            'sub_department_id',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'upc',
            'article_number',
            'retail_price',
            'type_id',
            'created_at',
            'updated_at',
            'original_created_at',
            'created_by_id',
            'created_by_type',
            'last_editor_by_id',
            'last_editor_by_type',
        ];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, ['master_product_id']);
        } else {
            $selectedColumns = array_merge($selectedColumns, ['color_id', 'size_id', 'style_id']);
        }

        $relations = [
            'draftProductTransaction:' . $draftProductTransactionQueries->getBasicColumnNames(),
            'draftProductTransaction.approvedBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'lastEditorBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'productChannelReference:' . $productChannelReferenceQueries->getBasicColumnNames(),
            ]);
        }

        return Product::query()
            ->select(...$selectedColumns)
            ->with($relations)
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $this->searchForList($query, $filterData, $brandQueries, $categoryQueries);
                });
            })
            ->whereNot('status', Statuses::DRAFT->value)
            ->where('company_id', $companyId)
            ->tap(fn ($query): Builder => $this->commonFilterQuery($query, $filterData))
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }
            })
            ->when($filterData['style_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('style_id', (array) $filterData['style_ids']);
            })
            ->when($filterData['product_collection_ids'], function ($query) use (
                $filterData,
                $productCollectionProductQueries
            ): void {
                $query->whereHas(
                    'productCollectionProducts',
                    $productCollectionProductQueries->filterByProductCollectionIds(
                        $filterData['product_collection_ids']
                    )
                );
            })
            ->get();
    }

    public function getActivePaginatedRegularProductsForEcommerce(
        int $companyId,
        array $filterData,
    ): LengthAwarePaginator {
        $categoryQueries = new CategoryQueries();
        $tagQueries = resolve(TagQueries::class);
        $brandQueries = new BrandQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $departmentQueries = new DepartmentQueries();
        $seasonQueries = new SeasonQueries();
        $styleQueries = new StyleQueries();
        $mediaQueries = new MediaQueries();
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Product::query()
            ->select(
                'id',
                'name',
                'description',
                'code',
                'season_id',
                'department_id',
                'master_product_id',
                'brand_id',
                'color_id',
                'size_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'retail_price',
                'online_price',
                'status',
                'created_at',
                'updated_at',
            )
            ->with([
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNamesForEcommerce(),
                'color:' . $colorQueries->getBasicColumnNamesForEcommerce(),
                'size:' . $sizeQueries->getBasicColumnNamesForEcommerce(),
                'department:' . $departmentQueries->getBasicColumnNamesForEcommerce(),
                'season:' . $seasonQueries->getBasicColumnNamesForEcommerce(),
                'style:' . $styleQueries->getBasicColumnNamesForEcommerce(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getBasicColumnNamesForEcommerce(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNamesForEcommerce(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->when(config('app.product_variant'), function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false)
                    ->where('type_id', ProductTypes::REGULAR_PRODUCT->value);
                });
            }, function ($query): void {
                $query->where('is_non_inventory', false)
                    ->where('type_id', ProductTypes::REGULAR_PRODUCT->value);
            })
            ->isAvailableInEcommerce()
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when(
                null !== $filterData['article_number'],
                function ($query) use ($filterData): void {
                    $query->when(config('app.product_variant'), function ($query) use ($filterData): void {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->where('article_number', $filterData['article_number']);
                        });
                    }, function ($query) use ($filterData): void {
                        $query->where('article_number', $filterData['article_number']);
                    });
                }
            )
            ->when(
                null !== $filterData['has_article_number'],
                function ($query) use ($filterData): void {
                    $query->when(true === $filterData['has_article_number'], function ($query): void {
                        $query->when(config('app.product_variant'), function ($query): void {
                            $query->whereHas('masterProduct', function ($query): void {
                                $query->whereNotNull('article_number');
                            });
                        }, function ($query): void {
                            $query->whereNotNull('article_number');
                        });
                    }, function ($query): void {
                        $query->where(function ($query): void {
                            $query->when(config('app.product_variant'), function ($query): void {
                                $query->whereHas('masterProduct', function ($query): void {
                                    $query->whereNull('article_number')
                                        ->orWhere('article_number', '');
                                });
                            }, function ($query): void {
                                $query->whereNull('article_number')
                                    ->orWhere('article_number', '');
                            });
                        });
                    });
                }
            )
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function searchByNameUpcAndArticleNumber(?string $searchText): Closure
    {
        if (config('app.product_variant')) {
            return fn ($query) => $query->select('id', 'master_product_id')
                ->when($searchText, function ($query) use ($searchText): void {
                    $query->where(function ($query) use ($searchText): void {
                        $query->whereAny(['name', 'upc'], 'LIKE', '%' . $searchText . '%');
                    });

                    $query->orWhereHas('masterProduct', function ($query) use ($searchText): void {
                        $query->where('article_number', 'LIKE', '%' . $searchText . '%');
                    });
                });
        }

        return fn ($query) => $query->select('id')
            ->when($searchText, function ($query) use ($searchText): void {
                $query->whereAny(['name', 'upc', 'article_number'], 'LIKE', '%' . $searchText . '%');
            });
    }

    public function searchByNameAndUpc(?string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->when($searchText, function ($query) use ($searchText): void {
                $query->whereAny(['name', 'upc'], 'LIKE', '%' . $searchText . '%');
            });
    }

    public function filterByColorIds(array $colorIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('color_id', $colorIds);
    }

    public function filterBySizeIds(array $sizeIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('size_id', $sizeIds);
    }

    public function filterByDepartmentIds(array $departmentIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('department_id', $departmentIds);
    }

    public function filterByDepartmentId(int $departmentId): Closure
    {
        return fn ($query) => $query->select('id')->where('department_id', $departmentId);
    }

    public function filterByBrandIds(array $brandIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('brand_id', $brandIds);
    }

    public function filterByUpc(string $upc): Closure
    {
        return fn ($query) => $query->select('id')->whereCaseSensitive('upc', $upc);
    }

    public function filterByName(string $name): Closure
    {
        return fn ($query) => $query->whereCaseSensitive('name', $name);
    }

    public function filterByIsNonSellingItem(): Closure
    {
        return fn ($query) => $query->select('id')->where('is_non_selling_item', false);
    }

    public function filterByIsInventory(): Closure
    {
        return fn ($query) => $query->select('id')->where('is_non_inventory', false);
    }

    public function searchByCompoundProductNameUpcAndArticleNumber(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where(
            function ($query) use ($searchText): void {
                $query
                    ->whereAny(['compound_product_name', 'upc', 'article_number'], 'LIKE', '%' . $searchText . '%');
            }
        );
    }

    public function addNew(ProductData $productData, int $companyId, User $user): Product
    {
        $productDetails = $productData->all();
        $productDetails['company_id'] = $companyId;
        $productDetails['created_by_id'] = $user->id;
        $productDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $productDetails['status'] = Statuses::DRAFT->value;

        if ((int) $productData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $productDetails['is_non_inventory'] = true;
        }

        $productDetails = $this->setAvailableInPosAndAvailableInEcommerce($productDetails);

        unset($productDetails['tag_ids'], $productDetails['category_ids'], $productDetails['thumbnail'], $productDetails['tiers'], $productDetails['boxes'], $productDetails['assembly_child_products'], $productDetails['images'], $productDetails['videos'], $productDetails['custom_field_values'], $productDetails['attached_templates'], $productDetails['sale_channel_ids']);

        $productDetails = $this->getCompoundProductName($productDetails, $companyId);
        $product = Product::create($productDetails);

        $this->updateSaleChannels($product, $productData);
        $this->updateTags($product, $productData->tag_ids);
        $this->updateCategories($product, $productData->category_ids);
        $this->uploadPhoto($product, $productData);
        $this->uploadVideo($product, $productData);
        $this->updateLoyaltyPointMembership($product, $productData);
        $this->updateAssemblyProducts($product, $productData);
        $this->updateProductBox($product, $productData);
        $this->uploadOtherImages($product, $productData);
        $this->createOrUpdateCustomFieldValues($product, $productData);

        return $product;
    }

    public function getByIdWithRelationsForPrint(int $productId, int $companyId): Product
    {
        $brandQueries = new BrandQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $departmentQueries = new DepartmentQueries();
        $categoryQueries = new CategoryQueries();
        $categoryQueries = new CategoryQueries();

        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $seasonQueries = new SeasonQueries();
        $styleQueries = new StyleQueries();
        $tagQueries = new TagQueries();
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = ['season:' . $seasonQueries->getBasicColumnNames()];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ]);
        }

        return Product::query()
            ->select(...$this->getAllBasicColumns())
            ->with($relations)
            ->where('company_id', $companyId)
            ->onlyActive()
            ->findOrFail($productId);
    }

    public function checkDraftProduct(int $productId, int $companyId): bool
    {
        return Product::where('id', $productId)
            ->where('company_id', $companyId)
            ->where('status', Statuses::DRAFT->value)
            ->exists();
    }

    public function getByIdDraftProduct(int $productId, int $companyId): Product
    {
        return Product::select('id', 'master_product_id')
            ->where('company_id', $companyId)
            ->where('status', Statuses::DRAFT->value)
            ->findOrFail($productId);
    }

    public function getByIdWithMediaCategoriesAndTags(int $productId, int $companyId): Product
    {
        $status = Statuses::ACTIVE->value;

        return $this->commonQueryForEditProduct($companyId, $productId, $status);
    }

    public function getByIdWithMediaCategoriesAndTagsForDraftProduct(int $productId, int $companyId): Product
    {
        $status = Statuses::DRAFT->value;

        return $this->commonQueryForEditProduct($companyId, $productId, $status);
    }

    public function getDraftProductDetailsById(int $productId, int $companyId): Product
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $adminQueries = resolve(AdminQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Product::query()
                ->select(
                    'id',
                    'name',
                    'code',
                    'upc',
                    'retail_price',
                    'status',
                    'ean',
                    'custom_sku',
                    'manufacturer_sku',
                    'franchise_price_1',
                    'franchise_price_2',
                    'franchise_price_3',
                    'wholesale_price',
                    'company_or_tender_price',
                    'branch_price',
                    'minimum_price',
                    'original_capital_price',
                    'capital_price',
                    'is_temporarily_unavailable',
                    'type_id',
                    'status',
                    'staff_price',
                    'purchase_cost',
                    'online_price',
                    'is_available_in_pos',
                    'is_available_in_ecommerce',
                    'created_at',
                    'updated_at',
                    'master_product_id',
                )
                ->with([
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                    'masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                    'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                    'masterProduct.createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                        $morphTo->constrain([
                            Admin::class => $adminQueries->getEmployeeWithRelation(),
                        ]);
                    },
                ])
                ->where('status', Statuses::DRAFT->value)
                ->where('company_id', $companyId)
                ->findOrFail($productId);
        }

        return Product::query()
            ->select(
                'id',
                'name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'department_id',
                'sub_department_id',
                'brand_id',
                'color_id',
                'size_id',
                'style_id',
                'upc',
                'article_number',
                'retail_price',
                'status',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'is_temporarily_unavailable',
                'has_batch',
                'type_id',
                'is_non_selling_item',
                'is_non_inventory',
                'status',
                'staff_price',
                'purchase_cost',
                'online_price',
                'is_available_in_pos',
                'is_available_in_ecommerce',
                'created_at',
                'updated_at',
                'created_by_id',
                'created_by_type',
            )
            ->with([
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'season:' . $seasonQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
            ])
            ->where('status', Statuses::DRAFT->value)
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function getById(int $productId, int $companyId): Product
    {
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);

        return Product::select(
            'id',
            'name',
            'master_product_id',
            'description',
            'code',
            'unit_of_measure_id',
            'company_id',
            'season_id',
            'company_id',
            'brand_id',
            'color_id',
            'size_id',
            'style_id',
            'department_id',
            'sub_department_id',
            'article_number',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'type_id',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'purchase_cost',
            'upc',
            'is_temporarily_unavailable',
            'has_batch',
            'is_non_inventory',
            'is_non_selling_item'
        )
            ->with([
                'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('status', [Statuses::ACTIVE->value, Statuses::DRAFT->value])
            ->findOrFail($productId);
    }

    public function getByIdForDashboardFilter(int $productId, int $companyId): ?Product
    {
        return Product::select('id', DB::raw('products.compound_product_name as name'))
            ->where('company_id', $companyId)
            ->where('id', $productId)
            ->first();
    }

    public function getProductByIdAndCompanyId(int $productId, int $companyId): Product
    {
        return Product::select('id', 'name')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function uploadImage(ProductImageUploadData $productImageUploadData, int $companyId): void
    {
        $product = Product::select('id')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->findOrFail($productImageUploadData->product_id);

        $product->addMedia($productImageUploadData->image)->toMediaCollection('thumbnail');
        $this->setUpdatedAt($product);
    }

    public function update(ProductData $productData, int $productId, int $companyId, ?User $user = null): Product
    {
        $product = $this->getById($productId, $companyId);
        $productDetails = $productData->all();
        unset($productDetails['tag_ids'], $productDetails['category_ids'], $productDetails['images'], $productDetails['videos'], $productDetails['tiers'], $productDetails['boxes'], $productDetails['assembly_child_products'], $productDetails['thumbnail'], $productDetails['custom_field_values'], $productDetails['attached_templates'], $productDetails['sale_channel_ids']);

        if ($product->upc) {
            unset($productDetails['upc']);
        }

        if ($product->unit_of_measure_id && ! config('app.update_unit_of_measure')) {
            unset($productDetails['unit_of_measure_id']);
        }

        if ((int) $productData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $productDetails['is_non_inventory'] = true;
        }

        $productDetails = $this->setAvailableInPosAndAvailableInEcommerce($productDetails);

        $productDetails = $this->getCompoundProductName($productDetails, $companyId);

        if ($user instanceof User) {
            $productDetails['last_editor_by_id'] = $user->id;
            $productDetails['last_editor_by_type'] = ModelMapping::getCaseName($user::class);
        }

        $product->update($productDetails);

        $this->updateTags($product, $productData->tag_ids);
        $this->updateSaleChannels($product, $productData);
        $this->updateCategories($product, $productData->category_ids);
        $this->uploadPhoto($product, $productData);
        $this->uploadVideo($product, $productData);
        $this->updateLoyaltyPointMembership($product, $productData);
        $this->updateAssemblyProducts($product, $productData);
        $this->updateProductBox($product, $productData);
        $this->uploadOtherImages($product, $productData);
        $this->createOrUpdateCustomFieldValues($product, $productData);
        $this->setUpdatedAt($product);

        $product->refresh();

        ProductCollectionUpdateByProductJob::dispatch($product->id, $companyId)->onQueue('medium');

        return $product;
    }

    public function setUpdatedAt(Product $product): void
    {
        $product->touch();
    }

    public function getActiveProductsByUpc(array $productUpc, int $companyId): Collection
    {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $preparedProducts = collect();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $chunkProductsUpc = array_chunk($productUpc, 1000);

        if (config('app.product_variant')) {
            $relations = [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ];
        }

        foreach ($chunkProductsUpc as $chunkProductUpc) {
            $products = Product::select(
                'id',
                'name',
                'upc',
                'color_id',
                'size_id',
                'has_batch',
                'retail_price',
                'purchase_cost'
            )
                ->with($relations)
                ->onlyActive()
                ->whereInCaseSensitive('upc', $chunkProductUpc)
                ->where('company_id', $companyId)
                ->get();

            $preparedProducts->push($products);
        }

        return $preparedProducts->collapse();
    }

    public function getProductsByUpcAndCompanyId(array $productUpc, int $companyId): Collection
    {
        return Product::select('id', 'name', 'upc', 'has_batch', 'retail_price', 'purchase_cost')
            ->whereInCaseSensitive('upc', $productUpc)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getActiveAndIsSellingProductsByUpc(array $productUpc, int $companyId): Collection
    {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $preparedProducts = collect();

        $chunkProductsUpc = array_chunk($productUpc, 1000);

        if (config('app.product_variant')) {
            foreach ($chunkProductsUpc as $chunkProductUpc) {
                $products = Product::select(
                    'id',
                    'name',
                    'upc',
                    'color_id',
                    'size_id',
                    'has_batch',
                    'retail_price',
                    'compound_product_name',
                    'master_product_id'
                )
                    ->with([
                        'masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                        'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                        'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    ])
                    ->onlyActive()
                    ->isSellingProduct()
                    ->whereInCaseSensitive('upc', $chunkProductUpc)
                    ->where('company_id', $companyId)
                    ->get();

                $preparedProducts->push($products);
            }

            return $preparedProducts->collapse();
        }

        foreach ($chunkProductsUpc as $chunkProductUpc) {
            $products = Product::select(
                'id',
                'name',
                'upc',
                'color_id',
                'size_id',
                'has_batch',
                'retail_price',
                'compound_product_name'
            )
                ->with([
                    'color:' . $colorQueries->getBasicColumnNames(),
                    'size:' . $sizeQueries->getBasicColumnNames(),
                ])
                ->onlyActive()
                ->isSellingProduct()
                ->whereInCaseSensitive('upc', $chunkProductUpc)
                ->where('company_id', $companyId)
                ->get();

            $preparedProducts->push($products);
        }

        return $preparedProducts->collapse();
    }

    public function getActiveInventoryProductsByUpcs(array $productUpcs, int $companyId): Collection
    {
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $preparedProducts = collect();
        $chunkProductsUpc = array_chunk($productUpcs, 1000);
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [];
        if (! $isProductVariant) {
            $relations = array_merge($relations, [
                'color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        }

        foreach ($chunkProductsUpc as $chunkProductUpc) {
            $products = Product::select(
                'id',
                'name',
                'color_id',
                'size_id',
                'upc',
                'has_batch',
                'compound_product_name',
                'master_product_id'
            )
                ->with($relations)
                ->onlyActive()
                ->whereInCaseSensitive('upc', $chunkProductUpc)
                ->where('company_id', $companyId)
                ->when(false === $isProductVariant, function ($query): void {
                    $query->where('is_non_inventory', false);
                })
                ->when($isProductVariant, function ($query): void {
                    $query->whereHas('masterProduct', function ($query): void {
                        $query->where('is_non_inventory', false);
                    });
                })
                ->get();

            $preparedProducts->push($products);
        }

        return $preparedProducts->collapse();
    }

    public function getActiveInventoryProductsByUpcsWithDerivatives(array $productUpcs, int $companyId): Collection
    {
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [];
        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        }

        return Product::select(
            'id',
            'name',
            'upc',
            'color_id',
            'size_id',
            'has_batch',
            'retail_price',
            'is_non_inventory',
            'unit_of_measure_id',
            'compound_product_name',
            'master_product_id'
        )
            ->onlyActive()
            ->with($relations)
            ->whereInCaseSensitive('upc', $productUpcs)
            ->where('company_id', $companyId)
            ->when(false === $isProductVariant, function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when($isProductVariant, function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->get();
    }

    public function getActiveInventoryProductByUpcForGRN(string $productUpc, int $companyId): ?Product
    {
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return Product::select(
            'id',
            'name',
            'unit_of_measure_id',
            'type_id',
            'upc',
            'has_batch',
            'retail_price',
            'is_non_inventory',
            'master_product_id'
        )
            ->with([
                'masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->whereCaseSensitive('upc', $productUpc)
            ->where('company_id', $companyId)
            ->when(false === $isProductVariant, function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when($isProductVariant, function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->first();
    }

    public function getSelectedActiveProductsForBarcodePrint(array $productIds, int $companyId): Collection
    {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $brandQueries = new BrandQueries();
        $styleQueries = new StyleQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return Product::select(
                'id',
                'name',
                'upc',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'master_product_id',
            )
                ->with([
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                ])
                ->onlyActive()
                ->whereIntegerInRaw('id', $productIds)
                ->where('company_id', $companyId)
                ->get();
        }

        return Product::select(
            'id',
            'name',
            'upc',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'brand_id',
            'article_number',
            'color_id',
            'size_id',
            'style_id',
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'brand:' . $brandQueries->getIdAndNameColumnNames(),
                'style:' . $styleQueries->getIdAndNameColumnNames(),
            ])
            ->onlyActive()
            ->whereIntegerInRaw('id', $productIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getActiveInventoryProductsByIds(array $productIds, int $companyId): Collection
    {
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Product::select(
            'id',
            'compound_product_name',
            'unit_of_measure_id',
            'has_batch',
            'upc',
            'is_non_inventory',
            'purchase_cost',
            'master_product_id'
        )
            ->with(['masterProduct:' . $masterProductQueries->getBasicColumnsForInventory()])
            ->onlyActive()
            ->whereIntegerInRaw('id', $productIds)
            ->when(false === $isProductVariant, function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when($isProductVariant, function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBatchProductsByIds(array $productIds, int $companyId): Collection
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return Product::select(
                'id',
                'compound_product_name',
                'has_batch',
                'upc',
                'is_non_inventory',
                'unit_of_measure_id',
                'purchase_cost',
                'master_product_id'
            )
                ->with([
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ])
                ->whereIntegerInRaw('id', $productIds)
                ->where('company_id', $companyId)
                ->get();
        }

        return Product::select(
            'id',
            'compound_product_name',
            'has_batch',
            'upc',
            'is_non_inventory',
            'unit_of_measure_id',
            'purchase_cost'
        )
            ->whereIntegerInRaw('id', $productIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getProductsWithArchivedByIds(array $productIds, int $companyId): Collection
    {
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Product::select(
            'id',
            'compound_product_name',
            'has_batch',
            'upc',
            'status',
            'master_product_id',
            'is_non_inventory'
        )
            ->with(['masterProduct:' . $masterProductQueries->getBasicColumnsForInventory()])
            ->whereIntegerInRaw('id', $productIds)
            ->when(false === $isProductVariant, function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when($isProductVariant, function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,upc,verification_qr_code,has_batch,brand_id,color_id,size_id,department_id,article_number,ean,is_non_inventory,type_id,compound_product_name,retail_price,unit_of_measure_id,purchase_cost,online_price,master_product_id,is_warranty,warranty_month';
    }

    public function getBasicColumnNamesForSerialNumberDetails(): string
    {
        return 'id,name,upc,type_id,compound_product_name,is_warranty,warranty_month';
    }

    public function getBasicColumnNamesForPurchaseOrderInvoice(): string
    {
        return 'id,name,upc,has_batch,article_number,compound_product_name,purchase_cost,unit_of_measure_id,color_id,size_id,master_product_id';
    }

    public function getBasicColumnNamesForSaleByPromoterReport(): string
    {
        return 'id,name,brand_id,department_id,master_product_id';
    }

    public function getBasicColumnNamesForRegularSalesApi(): string
    {
        return 'id,name,upc,color_id,size_id,article_number,type_id,has_batch,unit_of_measure_id,retail_price,master_product_id';
    }

    public function getCommonRelationColumns(): string
    {
        return 'id,name,upc,color_id,size_id,article_number,brand_id,department_id,master_product_id';
    }

    public function getBasicColumnNamesForPartialReceive(): string
    {
        return 'id,name,upc,color_id,size_id,article_number,unit_of_measure_id,master_product_id';
    }

    public function getColumnsForPromoterCommissionReport(): string
    {
        return 'id,name,upc';
    }

    public function getColumnNameAndId(): string
    {
        return 'id,name';
    }

    public function getColumnNameAndIdWithMasterId(): string
    {
        return 'id,name,master_product_id';
    }

    public function getBasicColumnNamesForStockTakeExport(): string
    {
        return 'id,name,color_id,size_id,upc,ean,article_number,brand_id,department_id,unit_of_measure_id,master_product_id';
    }

    public function getBasicColumnNamesInArray(): array
    {
        return ['id', 'name', 'upc', 'has_batch'];
    }

    public function getColumnsForInventoryReports(): string
    {
        return 'id,name,brand_id,color_id,size_id,upc,manufacturer_sku,retail_price,article_number,master_product_id';
    }

    public function getColumnsForBatchExpiryReports(): string
    {
        return 'id,name,brand_id,upc,master_product_id';
    }

    public function getColumnsForReservedInventoryReports(): string
    {
        return 'id,name,color_id,size_id,upc,article_number';
    }

    public function getColumnsForTransitInventoryReports(): string
    {
        return 'id,name,color_id,size_id,upc,article_number';
    }

    public function getColumnsForDiscountReports(): string
    {
        return 'id,name,upc,retail_price,article_number,brand_id,article_number,department_id,style_id,master_product_id';
    }

    public function getPurchaseCostColumn(): string
    {
        return 'id,purchase_cost';
    }

    public function getList(array $filteredData, int $companyId, int $locationId): LengthAwarePaginator
    {
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $seasonQueries = new SeasonQueries();
        $departmentQueries = new DepartmentQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $brandQueries = new BrandQueries();
        $styleQueries = new StyleQueries();
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();
        $inventoryQueries = new InventoryQueries();
        $inventoryUnitQueries = new InventoryUnitQueries();
        $batchQueries = new BatchQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $mergeProductTransactionQueries = resolve(MergeProductTransactionQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return Product::select(
            'id',
            'name',
            'compound_product_name',
            'code',
            'unit_of_measure_id',
            'season_id',
            'department_id',
            'sub_department_id',
            'color_id',
            'size_id',
            'brand_id',
            'style_id',
            'upc',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'article_number',
            'type_id',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'is_temporarily_unavailable',
            'has_batch',
            'is_warranty',
            'warranty_month',
            'status',
            'is_non_inventory',
            'is_non_selling_item',
            'is_available_in_pos',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'master_product_id'
        )
        ->with([
            'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
            'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
            'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
            'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
            'masterProduct.assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
            'masterProduct.assemblyChildMasterProducts.item:' . $this->getColumnNameAndId(),
            'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            'season:' . $seasonQueries->getBasicColumnNames(),
            'department:' . $departmentQueries->getBasicColumnNames(),
            'color:' . $colorQueries->getBasicColumnNames(),
            'size:' . $sizeQueries->getBasicColumnNames(),
            'brand:' . $brandQueries->getBasicColumnNames(),
            'style:' . $styleQueries->getBasicColumnNames(),
            'categories:' . $categoryQueries->getBasicColumnNames(),
            'tags:' . $tagQueries->getBasicColumnNames(),
            'inventory' => $inventoryQueries->getInventoryByLocationAndType($locationId),
            'inventory:' . $inventoryQueries->getBasicColumnNames(),
            'inventory.inventoryUnits' => $inventoryUnitQueries->positiveQuantityRecordsOnly(),
            'inventory.inventoryUnits.batch:' . $batchQueries->getBasicColumnNames(),
            'media:' . $mediaQueries->getBasicColumnNames(),
            'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
            'boxes:' . $boxProductQueries->getBasicColumnNames(),
            'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
            'boxes.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'mergeProductTransactions:' . $mergeProductTransactionQueries->getBasicColumnsName(),
            'mergeProductTransactions.oldProduct:' . $this->getIdAndUpc(),
            'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
            'assemblyChildProducts.product:' . $this->getColumnNameAndId(),
            'serialNumbers' => $serialNumberQueries->filterByCompanyIdAndStatus(
                $companyId,
                SerialNumberStatus::ACTIVE->value
            ),
        ])
        ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
            $query->where('compound_product_name', 'like', '%' . $filteredData['search_text'] . '%');
        })
        ->when($filteredData['after_updated_at'], function ($query) use ($filteredData): void {
            $query->where('updated_at', '>=', $filteredData['after_updated_at']);
        }, function ($query): void {
            $query->onlyActive()
                ->isSellingProduct()
                ->isAvailableInPos();
        })
        ->where('company_id', $companyId)
        ->paginate($filteredData['per_page']);
    }

    public function getByCodeAndCompanyId(string $code, int $companyId): ?Product
    {
        // do not use onActive() or status condition as import record job does not set errors and getting mysql error.
        return Product::select('id', 'status')
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->first();
    }

    public function existsByCodeUsingUpc(string $code, int $companyId, string $upc): ?Product
    {
        // do not use onActive() or status condition as import record job does not set errors and getting mysql error.
        return Product::select('id', 'status')
            ->whereCaseSensitive('code', $code)
            ->whereNotCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getByUpcAndCompanyId(string $upc, int $companyId): ?Product
    {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();

        // do not use onActive() or status condition as import record job does not set errors and getting mysql error.
        return Product::select('id', 'status', 'is_non_selling_item', 'color_id', 'size_id', 'name')
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->withTrashed()
            ->first();
    }

    public function getByUpcAndCompanyIdForImportMerge(string $upc, int $companyId): ?Product
    {
        $masterProductQueries = resolve(MasterProductQueries::class);

        // do not use onActive() or status condition as import record job does not set errors and getting mysql error.
        return Product::select('id', 'name', 'status', 'article_number', 'type_id', 'master_product_id')
            ->when(config('app.product_variant'), function ($query) use ($masterProductQueries): void {
                $query->with(['masterProduct:', $masterProductQueries->getBasicColumnNames()]);
            })
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->withTrashed()
            ->first();
    }

    public function getByIdForEcommerce(string $upc, int $companyId): ?int
    {
        return Product::select('id')
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->first()?->id;
    }

    public function existsByUpc(string $upc, int $companyId): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function checkProductByUpc(string $upc, int $companyId): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_non_inventory', false)
            ->exists();
    }

    public function getByIdsWithBrandAndCategories(array $productIds, int $companyId): Collection
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        return Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'department_id',
                'sub_department_id',
                'color_id',
                'size_id',
                'brand_id',
                'style_id',
                'vendor_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'purchase_cost',
                'created_by_id',
                'created_by_type',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'vendor_id',
                'is_sold_as_single_item',
                'sell_item_via_derivative',
            )->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'productCollectionProducts:' . $productCollectionProductQueries->getProductCollectionAndProductIdColumns(),
                'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'vendor' => $vendorQueries->filterByIsConsignmentTrue(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
                'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
                'assemblyChildProducts.product:' . $this->getBasicColumnNames(),
            ])
            ->whereIntegerInRaw('id', $productIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getAllBasicColumns(): array
    {
        $columns = [
            'id',
            'master_product_id',
            'name',
            'description',
            'code',
            'unit_of_measure_id',
            'season_id',
            'brand_id',
            'color_id',
            'vendor_id',
            'size_id',
            'style_id',
            'department_id',
            'sub_department_id',
            'article_number',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'type_id',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'purchase_cost',
            'online_price',
            'upc',
            'is_temporarily_unavailable',
            'has_batch',
            'is_non_inventory',
            'is_non_selling_item',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'created_by_id',
            'created_by_type',
            'original_created_at',
            'is_warranty',
            'warranty_month',
            'verification_qr_code',
            'width',
            'height',
            'weight',
        ];

        if (config('app.product_variant')) {
            return array_merge($columns, ['master_product_id']);
        }

        return $columns;
    }

    public function doAllActiveProductsExist(int $companyId, array $productIds): bool
    {
        $totalRecords = Product::select('id')
            ->whereIntegerInRaw('id', $productIds)
            ->where('company_id', $companyId)
            ->onlyActive()
            ->count();

        return count($productIds) === $totalRecords;
    }

    public function doAllProductsExist(int $companyId, array $productIds): bool
    {
        $totalRecords = Product::select('id')
            ->whereIntegerInRaw('id', $productIds)
            ->onlyActive()
            ->where('company_id', $companyId)
            ->count();

        return count($productIds) === $totalRecords;
    }

    public function getRetailPriceByIds(int $companyId, array $productIds): Collection
    {
        return Product::select('id', 'retail_price')
            ->onlyActive()
            ->whereIntegerInRaw('id', $productIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getActiveFilteredProductsQuery(array $filterData, int $companyId): Builder
    {
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return Product::query()
                ->select(
                    'id',
                    DB::raw('products.compound_product_name as name'),
                    'has_batch',
                    'color_id',
                    'size_id',
                    'unit_of_measure_id',
                    'article_number',
                    'master_product_id',
                )
                ->with([
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                ])
                ->whereNotNull('master_product_id')
                ->where('company_id', $companyId)
                ->when($filterData['search_text'], $this->searchByCompoundNameWithUpc($filterData))
                ->when($filterData['number_of_records'], function ($query) use ($filterData): void {
                    $query->limit($filterData['number_of_records']);
                })
                ->onlyActive()
                ->orderBy('name');
        }

        return Product::query()
            ->select(
                'id',
                DB::raw('products.compound_product_name as name'),
                'has_batch',
                'color_id',
                'size_id',
                'unit_of_measure_id',
                'article_number'
            )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchByCompoundNameWithUpc($filterData))
            ->when($filterData['number_of_records'], function ($query) use ($filterData): void {
                $query->limit($filterData['number_of_records']);
            })
            ->onlyActive()
            ->orderBy('name');
    }

    public function getActiveFilteredProductVariantsQuery(array $filterData, int $companyId): Builder
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Product::query()
            ->select(
                'id',
                DB::raw('products.compound_product_name as name'),
                'has_batch',
                'unit_of_measure_id',
                'master_product_id'
            )
            ->with([
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchByCompoundNameWithUpc($filterData))
            ->when($filterData['number_of_records'], function ($query) use ($filterData): void {
                $query->limit($filterData['number_of_records']);
            })
            ->onlyActive()
            ->orderBy('name');
    }

    public function getActiveFilteredProducts(array $filterData, int $companyId): Collection
    {
        return $this->getActiveFilteredProductsQuery($filterData, $companyId)->get();
    }

    public function getActiveFilteredInventoryProducts(array $filterData, int $companyId): Collection
    {
        return $this->getActiveFilteredProductsQuery($filterData, $companyId)
            ->when(false === config('app.product_variant'), function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->get();
    }

    public function getActiveProductsFilteredByNameBrandAndCategoryQuery(array $filterData, int $companyId): Builder
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $selectedColumns = ['id', 'name', 'brand_id', 'has_batch', 'article_number', 'unit_of_measure_id'];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, ['master_product_id']);
        } else {
            $selectedColumns = array_merge($selectedColumns, ['color_id', 'size_id']);
        }

        $relations = [];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        }

        return Product::query()
            ->select(...$selectedColumns)
            ->with($relations)
            ->where('company_id', $companyId)
            ->when(config('app.product_variant'), fn ($q) => $q->whereNotNull('master_product_id'))
            ->when($filterData['search_text'], $this->searchByCompoundNameWithUpc($filterData))
            ->when($filterData['category_id'], function ($query) use (
                $categoryQueries,
                $filterData,
                $companyId
            ): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use (
                        $filterData,
                        $categoryQueries,
                        $companyId
                    ): void {
                        $q->whereHas(
                            'categories',
                            $categoryQueries->filterByIdAndCompany($companyId, (int) $filterData['category_id'])
                        );
                    });
                } else {
                    $query->whereHas(
                        'categories',
                        $categoryQueries->filterByIdAndCompany($companyId, (int) $filterData['category_id'])
                    );
                }
            })->when($filterData['brand_id'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData): void {
                        $q->where('brand_id', $filterData['brand_id']);
                    });
                } else {
                    $query->where('brand_id', $filterData['brand_id']);
                }
            })
            ->onlyActive()
            ->orderBy('name');
    }

    public function getActiveInventoryProductsFilteredByName(array $filterData, int $companyId): Collection
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $batchQueries = resolve(BatchQueries::class);

        return Product::query()
            ->select(
                'id',
                'name',
                'brand_id',
                'size_id',
                'color_id',
                'has_batch',
                'article_number',
                'upc',
                'retail_price',
                'wholesale_price',
                'minimum_price',
                'type_id',
                'unit_of_measure_id',
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'boxes.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'inventory:' . $inventoryQueries->getBasicColumnNames(),
                'inventory.inventoryUnits' => $inventoryUnitQueries->positiveQuantityRecordsOnly(),
                'inventory.inventoryUnits.batch:' . $batchQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchByCompoundNameWithUpc($filterData))
            ->onlyActive()
            ->isSellingProduct()
            ->orderBy('name')
            ->take(6)
            ->get();
    }

    public function getActiveProductsFilteredByNameBrandAndCategory(array $filterData, int $companyId): Collection
    {
        return $this->getActiveProductsFilteredByNameBrandAndCategoryQuery($filterData, $companyId)->get();
    }

    public function getActiveInventoryProductsFilteredByNameBrandAndCategory(
        array $filterData,
        int $companyId,
    ): Collection {
        return $this->getActiveProductsFilteredByNameBrandAndCategoryQuery($filterData, $companyId)
            ->where('is_non_inventory', false)
            ->when(
                array_key_exists('has_inventory', $filterData) && (bool) $filterData['has_inventory'],
                function ($query) use ($filterData): void {
                    $query->whereHas('inventories', function ($query) use ($filterData): void {
                        $query->where('stock', '>', 0)
                            ->when(
                                array_key_exists('location_id', $filterData),
                                function ($query) use ($filterData): void {
                                    $query->where('location_id', $filterData['location_id']);
                                }
                            );
                    });
                }
            )
            ->get();
    }

    public function getActiveProductWithBasicColumnsById(int $productId, int $companyId): Product
    {
        return Product::select('id', DB::raw('products.compound_product_name as name'))
            ->onlyActive()
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function getFilteredArticleNumberByCompanyId(string $searchText, int $companyId): LazyCollection
    {
        if (config('app.product_variant')) {
            return Product::query()
                ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                ->select(DB::raw('DISTINCT master_products.article_number'))
                ->where('products.company_id', $companyId)
                ->where('master_products.article_number', 'like', '%' . $searchText . '%')
                ->where('products.status', Statuses::ACTIVE->value)
                ->orderBy(DB::raw('CHAR_LENGTH(master_products.article_number)'), 'asc')
                ->limit(5)
                ->cursor()
                ->remember();
        }

        return Product::query()
            ->onlyActive()
            ->select(DB::raw('DISTINCT article_number'))
            ->where('company_id', $companyId)
            ->where('article_number', 'like', '%' . $searchText . '%')
            ->orderBy(DB::raw('CHAR_LENGTH(article_number)'), 'asc')
            ->limit(5)
            ->cursor()
            ->remember();
    }

    public function getProductColumnsForPos(): string
    {
        return 'id,name,brand_id';
    }

    public function getProductNameColumn(): string
    {
        return 'id,name';
    }

    public function getProductColumnsForPosMemberApi(): string
    {
        return 'id';
    }

    public function markAsArchived(int $productId, int $companyId): void
    {
        $product = Product::query()
            ->select('id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
        $product->status = Statuses::ARCHIVED->value;
        $product->save();
    }

    public function restore(int $productId, int $companyId): void
    {
        $product = Product::query()
            ->select('id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
        $product->status = Statuses::ACTIVE->value;
        $product->save();
    }

    public function getBasicColumns(): string
    {
        return 'id,name,unit_of_measure_id,season_id,department_id,sub_department_id,color_id,size_id,brand_id,style_id,upc,article_number,retail_price,master_product_id';
    }

    public function getIdAndUpc(): string
    {
        return 'id,upc';
    }

    public function getIdAndUpcAndUnitOfMeasure(): string
    {
        return 'id,upc,unit_of_measure_id';
    }

    public function getBasicColumnsForPrint(): string
    {
        return 'id,name,color_id,size_id,article_number,upc,unit_of_measure_id';
    }

    public function getColumnsForStockTransferEdit(): string
    {
        return 'id,color_id,size_id,unit_of_measure_id,has_batch,compound_product_name,master_product_id';
    }

    public function getIdAndDepartmentIdAndBrandColumnName(): string
    {
        return 'id,department_id,brand_id';
    }

    public function searchByCompoundProductNameUpcAndSku(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->from('products')
            ->whereAny(
                ['upc', 'compound_product_name', 'manufacturer_sku', 'article_number', 'ean'],
                'LIKE',
                '%' . $searchText . '%'
            );
    }

    public function getBasicColumnsName(): string
    {
        return 'id,name,code,unit_of_measure_id,season_id,department_id,sub_department_id,color_id,size_id,brand_id,style_id,upc,ean,custom_sku,manufacturer_sku,article_number,retail_price,master_product_id';
    }

    public function searchByCompoundName(string $searchText): Closure
    {
        return fn ($query) => $query->where('compound_product_name', 'like', '%' . $searchText . '%');
    }

    public function searchByProductAndRelationalColumns(string $searchText): Closure
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $styleQueries = resolve(StyleQueries::class);

        return fn ($query) => $query
            ->whereAny(
                [
                    'compound_product_name',
                    'code',
                    'ean',
                    'custom_sku',
                    'manufacturer_sku',
                    'retail_price',
                    'franchise_price_1',
                    'franchise_price_2',
                    'franchise_price_3',
                    'wholesale_price',
                    'company_or_tender_price',
                    'branch_price',
                    'minimum_price',
                    'original_capital_price',
                    'capital_price',
                ],
                'LIKE',
                '%' . $searchText . '%'
            )
            ->orWhereIntegerInRaw('sub_department_id', SubDepartments::getMatchingCases($searchText))
            ->orWhereIntegerInRaw('unit_of_measure_id', function ($query) use (
                $searchText,
                $unitOfMeasureQueries
            ): void {
                $query->select('id')
                    ->from('unit_of_measures')
                    ->where($unitOfMeasureQueries->searchByName($searchText));
            })
            ->orWhereIntegerInRaw('season_id', function ($query) use ($searchText, $seasonQueries): void {
                $query->select('id')
                    ->from('seasons')
                    ->where($seasonQueries->searchByColumns($searchText));
            })
            ->orWhereIntegerInRaw('style_id', function ($query) use ($searchText, $styleQueries): void {
                $query->select('id')
                    ->from('styles')
                    ->where($styleQueries->searchByColumns($searchText));
            });
    }

    public function searchByProductArticleNumber(string $articleNumber): Closure
    {
        return fn ($query) => $query
            ->where('article_number', 'like', '%' . $articleNumber . '%');
    }

    public function getActiveProductIds(int $companyId, int $locationId): Collection
    {
        return Product::select('id')
            ->onlyActive()
            ->whereHas('latestInventoryUpdate', function ($query) use ($locationId): void {
                $query->where('closing_stock', '>', 0)
                    ->where('location_id', $locationId);
            })
            ->where('company_id', $companyId)
            ->where('is_non_inventory', false)
            ->where('is_non_selling_item', false)
            ->pluck('id');
    }

    public function getProductsWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        return $this->productLists($filterData, $companyId)->get();
    }

    public function getProductsExportCount(array $filterData, int $companyId): int
    {
        return $this->productLists($filterData, $companyId)->count();
    }

    public function exportProductRecords(array $filterData, int $companyId, int $skip, int $limit): Collection
    {
        return $this->productLists($filterData, $companyId)
            ->skip($skip)->limit($limit)->get();
    }

    public function getStockByStoreIdProductIdsAndDate(
        int $locationId,
        array $productIds,
        string $compareStockDate,
    ): Collection {
        $commonClosure = function ($query) use ($compareStockDate, $locationId): void {
            $query->select('id', 'product_id', 'closing_stock')
                ->where('happened_at', '<=', CommonFunctions::addEndTime($compareStockDate))
                ->where('location_id', $locationId);
        };

        return Product::query()
            ->select('id')
            ->onlyActive()
            ->whereIntegerInRaw('id', $productIds)
            ->with('latestInventoryUpdate', $commonClosure)
            ->whereHas('latestInventoryUpdate', $commonClosure)
            ->get();
    }

    public function updateProductPrice(array $productPriceData, string $upc, int $companyId): void
    {
        /** @var Product $product */
        $product = Product::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->when($upc, function ($query) use ($upc): void {
                $query->where('upc', $upc);
            })
            ->first();

        $product->update($productPriceData);

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $productCollectionProductQueries->removeByProductId($product->id, $companyId);
        ProductCollectionUpdateByProductJob::dispatch($product->id, $companyId)->onQueue('medium');
    }

    public function searchByCompoundNameWithUpc(array $filterData): Closure
    {
        return fn ($query) => $query
            ->where(function ($query) use ($filterData): void {
                $query
                    ->whereAny(['upc', 'ean'], 'LIKE', '%' . $filterData['search_text'] . '%')
                    ->orWhere(function ($query) use ($filterData): void {
                        $compoundNames = array_filter(explode(' ', $filterData['search_text']));
                        foreach ($compoundNames as $compoundName) {
                            $query->where('compound_product_name', 'like', '%' . $compoundName . '%');
                        }
                    });
            });
    }

    public function searchByCompoundNameForReport(array $filterData): Closure
    {
        return fn ($query) => $query
            ->where(function ($query) use ($filterData): void {
                $query->where('products.upc', 'like', '%' . $filterData['search_text'] . '%')
                    ->orWhere(function ($query) use ($filterData): void {
                        $compoundNames = array_filter(explode(' ', $filterData['search_text']));
                        foreach ($compoundNames as $compoundName) {
                            $query->where('products.compound_product_name', 'like', '%' . $compoundName . '%');
                        }
                    });
            });
    }

    public function filterForTheReservedStock(array $filterData, int $companyId): Closure
    {
        return $this->commonFilterQueryForReport($filterData, $companyId);
    }

    public function filterForTheTransitStock(array $filterData, int $companyId): Closure
    {
        return $this->commonFilterQueryForReport($filterData, $companyId);
    }

    public function updateByUpc(array $productData, string $upc, int $companyId): Product
    {
        $categoryIds = $productData['category_ids'];
        $tagIds = $productData['tag_ids'];
        $saleChannelIds = $productData['sale_channel_ids'];

        unset($productData['category_ids'], $productData['tag_ids'], $productData['sale_channel_ids']);

        /** @var Product $product */
        $product = Product::select('id', 'company_id')
            ->whereCaseSensitive('upc', $upc)
            ->where('company_id', $companyId)
            ->first();

        $productData = $this->getCompoundProductName($productData, $product->company_id);

        $product->update($productData);

        $this->updateTags($product, $tagIds);
        $this->updateCategories($product, $categoryIds);
        $this->updateSaleChannelsByUpc($product, $saleChannelIds);

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $productCollectionProductQueries->removeByProductId($product->id, $companyId);
        ProductCollectionUpdateByProductJob::dispatch($product->id, $companyId)->onQueue('medium');

        return $product->refresh();
    }

    public function listQueryForWarehouseManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->productLists($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function searchByArticleNumber(string $articleNumber, int $companyId): Collection
    {
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        return Product::select('id', 'name', 'size_id', 'color_id', 'has_batch', 'upc', 'compound_product_name')
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->get();
    }

    public function searchActiveInventoryProductsByArticleNumber(string $articleNumber, int $companyId): Collection
    {
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return Product::select(
            'id',
            'name',
            'size_id',
            'color_id',
            'unit_of_measure_id',
            'has_batch',
            'upc',
            'compound_product_name'
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->where('is_non_inventory', false)
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->get();
    }

    public function searchByArticleNumberWithDerivatives(string $articleNumber, int $companyId): Collection
    {
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        return Product::select(
            'id',
            'name',
            'size_id',
            'color_id',
            'unit_of_measure_id',
            'has_batch',
            'upc',
            'compound_product_name'
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->where('is_non_inventory', false)
            ->get();
    }

    public function getIdByUpc(string $upc, int $companyId): ?int
    {
        return Product::select('id')->where('upc', $upc)->where('company_id', $companyId)->first()?->id;
    }

    public function getProductTypeAndPrice(string $upc): ?Product
    {
        return Product::select('id', 'type_id', 'retail_price')->where('upc', $upc)->first();
    }

    public function getProductTypeAndArticleNumber(int $productId, int $companyId): Product
    {
        if (config('app.product_variant')) {
            $masterProductQueries = resolve(MasterProductQueries::class);

            return Product::with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                ->select('id', 'type_id', 'article_number', 'master_product_id')
                ->where('company_id', $companyId)
                ->findOrFail($productId);
        }

        return Product::select('id', 'type_id', 'article_number')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function getProductsReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getProductReport($filterData, $companyId)->get();
    }

    public function getProfitsAndLossesReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getProfitAndLossReport($filterData, $companyId)->get();
    }

    public function getPaginatedProductsReport(array $filterData, int $companyId): PaginationLengthAwarePaginator
    {
        return $this->getProductReport($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getPaginatedConsignmentReport(array $filterData, int $companyId): PaginationLengthAwarePaginator
    {
        return $this->getConsignmentReport($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getConsignmentReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getConsignmentReport($filterData, $companyId)->get();
    }

    public function getPaginatedProfitsAndLossesReport(
        array $filterData,
        int $companyId,
    ): PaginationLengthAwarePaginator {
        return $this->getProfitAndLossReport($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getFilteredTotalsForProfitsAndLossesReport(array $filterData, int $companyId): Collection
    {
        return DB::table('products')
            ->select(
                'sale_return_totals.total_quantity_returned as total_quantity_returned',
                'sale_return_totals.total_returned_amount as total_returned_amount',
                'sale_totals.total_quantity_sold as total_quantity_sold',
                'sale_totals.total_amount_sold as total_amount_sold',
                DB::raw('SUM(total_quantity_sold * products.purchase_cost) as total_purchase_cost'),
            )
            ->where('products.is_non_selling_item', false)
            ->where('products.status', Statuses::ACTIVE->value)
            ->whereNull('products.deleted_at')
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        'locations.id as location_id',
                        DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                        DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                    })
                    ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->groupBy('counters.location_id', 'sale_return_items.product_id'),
                'sale_return_totals',
                'sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        'locations.id as location_id',
                        DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                        DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold'),
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                    })
                    ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sales.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->groupBy('counters.location_id', 'sale_items.product_id'),
                'sale_totals',
                'sale_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('category_product')
                    ->select('category_product.product_id', DB::raw('GROUP_CONCAT(categories.name) as category_names'))
                    ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
                    ->groupBy('category_product.product_id'),
                'product_category',
                'product_category.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('product_tag')
                    ->select('product_tag.product_id', DB::raw('GROUP_CONCAT(tags.name) as tag_names'))
                    ->leftJoin('tags', 'tags.id', '=', 'product_tag.tag_id')
                    ->groupBy('product_tag.product_id'),
                'products_tags',
                'products_tags.product_id',
                '=',
                'products.id'
            )
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
            ->leftJoin('departments', 'products.department_id', '=', 'departments.id')
            ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id')
            ->leftJoin('unit_of_measures', 'products.unit_of_measure_id', '=', 'unit_of_measures.id')
            ->leftJoin('seasons', 'products.season_id', '=', 'seasons.id')
            ->leftJoin('category_product', 'category_product.product_id', '=', 'products.id')
            ->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id')
            ->where('products.company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('products.article_number', $filterData['article_numbers']);
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
            })
            ->when($filterData['color_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when($filterData['size_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('category_product.category_id', $filterData['category_ids']);
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_collection_products where product_collection_id =' . $filterData['product_collection_id'] . ')'
                );
            })
            ->where(function ($query): void {
                $query->whereNotNull('sale_return_totals.total_quantity_returned')
                    ->orWhereNotNull('sale_return_totals.total_returned_amount')
                    ->orWhereNotNull('sale_totals.total_quantity_sold')
                    ->orWhereNotNull('sale_totals.total_amount_sold');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('units_sold' === $filterData['sort_by']) {
                    $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                }

                if ('units_returned' === $filterData['sort_by']) {
                    $query->orderBy('total_quantity_returned', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('products.id', 'desc');
            })
            ->groupBy('products.id', 'sale_return_totals.location_id', 'sale_totals.location_id')
            ->get();
    }

    public function getProductsForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $inventoryQueries = resolve(InventoryQueries::class);
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->getLowStockNotificationByCompanyIdAndType($companyId);
        $filteredData['location_ids'] = [$filteredData['location_id']];
        $mediaQueries = resolve(MediaQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Product::select(
            'id',
            'name',
            'article_number',
            'color_id',
            'size_id',
            'retail_price',
            'upc',
            'custom_sku',
            'master_product_id',
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'inventory' => $inventoryQueries->getInventoryByLocationAndTypeWithStockType(
                    $filteredData,
                    (int) $filteredData['location_id'],
                    $companyId,
                    $automatedNotification
                ),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->whereHas('inventory', function ($query) use (
                $filteredData,
                $inventoryQueries,
                $companyId,
                $automatedNotification
            ): void {
                $query->where('location_id', (int) $filteredData['location_id']);
                if ('no_stock' === $filteredData['stock_product']) {
                    $query->where('stock', '<=', 0);
                } elseif ('in_stock' === $filteredData['stock_product']) {
                    $query->where('stock', '>', 0);
                } elseif ('low_stock' === $filteredData['stock_product']) {
                    $query->whereIn(
                        'id',
                        $inventoryQueries->getLowStockInventoryIdQueryForProduct($filteredData, $companyId)
                    )
                        ->orWhereIn(
                            'id',
                            $inventoryQueries->getLowStockInventoryIdQueryForStore($filteredData, $companyId)
                        )
                        ->when(
                            null !== $automatedNotification,
                            function ($query) use (
                                $companyId,
                                $inventoryQueries,
                                $filteredData,
                                $automatedNotification
                            ): void {
                                $query->orWhereIn(
                                    'inventories.id',
                                    $inventoryQueries->getLowStockInventoryIdQueryForCompany(
                                        /* @phpstan-ignore-next-line */
                                        $automatedNotification,
                                        $filteredData,
                                        $companyId
                                    )
                                );
                            }
                        );
                }
            })
            ->onlyActive()
            ->where('company_id', $companyId)
            ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query
                        ->whereAny(
                            ['upc', 'compound_product_name', 'retail_price'],
                            'LIKE',
                            '%' . $filteredData['search_text'] . '%'
                        )
                        ->when(config('app.product_variant'), function ($query) use ($filteredData): void {
                            $query->orWhereHas('masterProduct', function ($query) use ($filteredData): void {
                                $query->orWhere('article_number', 'like', '%' . $filteredData['search_text'] . '%');
                            });
                        }, function ($query) use ($filteredData): void {
                            $query->orWhere('article_number', 'like', '%' . $filteredData['search_text'] . '%');
                        });
                });
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getProductDetailsForApplication(int $productId, int $companyId): Product
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return Product::select(
            'id',
            'name',
            'article_number',
            'retail_price',
            'upc',
            'custom_sku',
            'color_id',
            'size_id',
            'brand_id',
            'type_id',
            'master_product_id'
        )
            ->onlyActive()
            ->where('company_id', $companyId)
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($productId);
    }

    public function getAllActiveProductsCount(int $companyId): int
    {
        return Product::query()
            ->select('id')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->count();
    }

    public function getAllNoStocksProducts(array $filterData, int $companyId): int
    {
        return Product::query()
            ->select('id')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->whereHas('inventory', function ($query) use ($filterData): void {
                $query->where('stock', '<=', 0)
                    ->where('location_id', $filterData['location_id']);
            })
            ->count();
    }

    public function getCachedTopSellingProduct(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $fromDate,
        string $toDate,
        bool $refresh = false,
    ): Collection {
        $cacheKey = 'cache-Top-Selling-Product-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $fromDate . '-' . $toDate;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $mediaQueries = resolve(MediaQueries::class);

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Product::query()
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw(
                        '(COALESCE(product_sale_total.total_paid_amount, 0) - COALESCE(product_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(product_sale_total.units_sold, 0) - COALESCE(product_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->with('media:' . $mediaQueries->getBasicColumnNames())
                ->where('retail_price', '>', 0.0)
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('product_id'),
                    'product_sale_total',
                    'product_sale_total.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('product_id'),
                    'product_return_total',
                    'product_return_total.product_id',
                    '=',
                    'products.id'
                )
                ->whereNotNull('product_sale_total.total_paid_amount')
                ->orWhereNotNull('product_sale_total.units_sold')
                ->orWhereNotNull('product_sale_total.sales_count')
                ->orWhereNotNull('product_return_total.return_amount')
                ->orWhereNotNull('product_return_total.return_units')
                ->orderByDesc('total_units_sold')
                ->limit(10)
                ->get()
        );
    }

    public function getCachedWorstSellingProduct(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $fromDate,
        string $toDate,
        bool $refresh = false,
    ): Collection {
        $cacheKey = 'cache-Worst-Selling-Product-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $fromDate . '-' . $toDate;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $mediaQueries = resolve(MediaQueries::class);

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Product::query()
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw(
                        '(COALESCE(product_sale_total.total_paid_amount, 0) - COALESCE(product_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(product_sale_total.units_sold, 0) - COALESCE(product_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->with('media:' . $mediaQueries->getBasicColumnNames())
                ->where('retail_price', '>', 0.0)
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('product_id'),
                    'product_sale_total',
                    'product_sale_total.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('product_id'),
                    'product_return_total',
                    'product_return_total.product_id',
                    '=',
                    'products.id'
                )
                ->whereNotNull('product_sale_total.total_paid_amount')
                ->orWhereNotNull('product_sale_total.units_sold')
                ->orWhereNotNull('product_sale_total.sales_count')
                ->orWhereNotNull('product_return_total.return_amount')
                ->orWhereNotNull('product_return_total.return_units')
                ->orderBy('total_units_sold')
                ->limit(10)
                ->get()
        );
    }

    public function getCachedProductQuantitySoldReportWithArticleNumber(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('sale_return_totals.total_quantity_returned as total_quantity_returned'),
                    DB::raw('sale_return_totals.total_returned_amount as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_quantity_returned as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_returned_amount as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                         ->groupBy(
                             config('app.product_variant')
                                 ? 'master_products.article_number'
                                 : 'products.article_number'
                         ),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                 ->groupBy(
                     config('app.product_variant')
                         ? 'master_products.article_number'
                         : 'products.article_number'
                 )
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedSingleCompareProductQuantitySoldReportWithArticleNumber(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw(
                        'compare_sale_return_totals.compare_total_quantity_returned as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_returned_amount as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['compare_sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.name', $filterData['compare_sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['compare_sort_by']) {
                        $query->orderBy('colors.name', $filterData['compare_sort_direction']);
                    }

                    if ('upc' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.upc', $filterData['compare_sort_direction']);
                    }

                    if ('article_number' === $filterData['compare_sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['compare_sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['compare_sort_direction']);
                    }

                    if ('qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('amount_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['compare_sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy(
                    config('app.product_variant')
                        ? 'master_products.article_number'
                        : 'products.article_number'
                )
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedSingleProductQuantitySoldReportWithArticleNumber(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('sale_return_totals.total_quantity_returned as total_quantity_returned'),
                    DB::raw('sale_return_totals.total_returned_amount as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy(
                    config('app.product_variant')
                        ? 'master_products.article_number'
                        : 'products.article_number'
                )
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedProductQuantitySoldReportWithArticleNumberCollection(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('sale_return_totals.total_quantity_returned as total_quantity_returned'),
                    DB::raw('sale_return_totals.total_returned_amount as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_quantity_returned as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_returned_amount as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy(
                    config('app.product_variant')
                        ? 'master_products.article_number'
                        : 'products.article_number'
                )
                ->get();
        });
    }

    public function getCachedSingleComparedProductQuantitySoldReportWithArticleNumberCollection(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw(
                        'compare_sale_return_totals.compare_total_quantity_returned as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'compare_sale_return_totals.compare_total_returned_amount as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['compare_sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.name', $filterData['compare_sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['compare_sort_by']) {
                        $query->orderBy('colors.name', $filterData['compare_sort_direction']);
                    }

                    if ('upc' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.upc', $filterData['compare_sort_direction']);
                    }

                    if ('article_number' === $filterData['compare_sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['compare_sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['compare_sort_direction']);
                    }

                    if ('qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('amount_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['compare_sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy('products.article_number')
                ->get();
        });
    }

    public function getCachedSingleProductQuantitySoldReportWithArticleNumberCollection(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('sale_return_totals.total_quantity_returned as total_quantity_returned'),
                    DB::raw('sale_return_totals.total_returned_amount as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                }, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                            $query->leftJoin(
                                'category_master_product',
                                'products.master_product_id',
                                '=',
                                'category_master_product.master_product_id'
                            );
                            $query->leftJoin(
                                'master_product_tag',
                                'master_product_tag.master_product_id',
                                '=',
                                'products.master_product_id'
                            );
                        }, function ($query): void {
                            $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                            $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                        })
                        ->when(
                            array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                                } else {
                                    $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                                }
                            }
                        )
                        ->when(
                            array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                                } else {
                                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                                }
                            }
                        )
                        ->when(
                            $filterData['category_ids'] && null !== $filterData['category_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'category_master_product.category_id',
                                        $filterData['category_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw(
                                        'category_product.category_id',
                                        $filterData['category_ids']
                                    );
                                }
                            }
                        )
                        ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                            } else {
                                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                            }
                        })
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                            }
                        )
                        ->when(
                            $filterData['department_ids'] && null !== $filterData['department_ids'],
                            function ($query) use ($filterData): void {
                                if (config('app.product_variant')) {
                                    $query->whereIntegerInRaw(
                                        'master_products.department_id',
                                        $filterData['department_ids']
                                    );
                                } else {
                                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                                }
                            }
                        )
                        ->when(
                            config(
                                'app.product_variant'
                            ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                            }
                        )
                        ->when(
                            isset($filterData['attributes']) && $filterData['attributes'],
                            function ($q) use ($filterData): void {
                                $q->whereExists(function ($subQuery) use ($filterData): void {
                                    $subQuery->select(DB::raw(1))
                                        ->from('product_variant_values as pvv')
                                        ->whereRaw('pvv.product_id = products.id')
                                        ->whereIn('pvv.value', $filterData['attributes']);
                                });
                            }
                        )
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy(
                            config('app.product_variant')
                                ? 'master_products.article_number'
                                : 'products.article_number'
                        ),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(
                    config('app.product_variant') === false,
                    function ($query): void {
                        $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                    }
                )
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy(
                    config('app.product_variant')
                        ? 'master_products.article_number'
                        : 'products.article_number'
                )
                ->get();
        });
    }

    public function getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, fn () => Product::query()
            ->select(
                DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                DB::raw(
                    'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                ),
                DB::raw(
                    'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                ),
                DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                ...config('app.product_variant') ? [
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                                GROUP_CONCAT(
                                    CASE
                                        WHEN product_variant_values.value IS NOT NULL
                                        THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                        ELSE NULL
                                    END
                                    SEPARATOR ', '
                                ) as variant_values
                            "),
                    ],
                ] : [],
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
            })
            ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                        DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                        $query->leftJoin(
                            'category_master_product',
                            'products.master_product_id',
                            '=',
                            'category_master_product.master_product_id'
                        );
                        $query->leftJoin(
                            'master_product_tag',
                            'master_product_tag.master_product_id',
                            '=',
                            'products.master_product_id'
                        );
                    }, function ($query): void {
                        $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                        $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                    })
                    ->when(
                        array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                            } else {
                                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                            }
                        }
                    )
                    ->when(
                        array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                            } else {
                                $query->whereIn('products.article_number', $filterData['article_numbers']);
                            }
                        }
                    )
                    ->when(
                        $filterData['category_ids'] && null !== $filterData['category_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'category_master_product.category_id',
                                    $filterData['category_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('category_product.category_id', $filterData['category_ids']);
                            }
                        }
                    )
                    ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                        }
                    )
                    ->when(
                        $filterData['department_ids'] && null !== $filterData['department_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'master_products.department_id',
                                    $filterData['department_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                            }
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                        }
                    )
                    ->when(
                        isset($filterData['attributes']) && $filterData['attributes'],
                        function ($q) use ($filterData): void {
                            $q->whereExists(function ($subQuery) use ($filterData): void {
                                $subQuery->select(DB::raw(1))
                                    ->from('product_variant_values as pvv')
                                    ->whereRaw('pvv.product_id = products.id')
                                    ->whereIn('pvv.value', $filterData['attributes']);
                            });
                        }
                    )
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->when($filterData['location_id'], function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['location_id']);
                    })
                    ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                        $query->where('locations.region_id', $filterData['region_id']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['date_range'][0])
                        )
                            ->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date_range'][1])
                            );
                    })
                    ->groupBy(
                        config('app.product_variant')
                            ? 'master_products.article_number'
                            : 'products.article_number'
                    ),
                'sale_return_totals',
                'sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                        DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                        $query->leftJoin(
                            'category_master_product',
                            'products.master_product_id',
                            '=',
                            'category_master_product.master_product_id'
                        );
                        $query->leftJoin(
                            'master_product_tag',
                            'master_product_tag.master_product_id',
                            '=',
                            'products.master_product_id'
                        );
                    }, function ($query): void {
                        $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                        $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                    })
                    ->when(
                        array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                            } else {
                                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                            }
                        }
                    )
                    ->when(
                        array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                            } else {
                                $query->whereIn('products.article_number', $filterData['article_numbers']);
                            }
                        }
                    )
                    ->when(
                        $filterData['category_ids'] && null !== $filterData['category_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'category_master_product.category_id',
                                    $filterData['category_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('category_product.category_id', $filterData['category_ids']);
                            }
                        }
                    )
                    ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                        }
                    )
                    ->when(
                        $filterData['department_ids'] && null !== $filterData['department_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'master_products.department_id',
                                    $filterData['department_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                            }
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                        }
                    )
                    ->when(
                        isset($filterData['attributes']) && $filterData['attributes'],
                        function ($q) use ($filterData): void {
                            $q->whereExists(function ($subQuery) use ($filterData): void {
                                $subQuery->select(DB::raw(1))
                                    ->from('product_variant_values as pvv')
                                    ->whereRaw('pvv.product_id = products.id')
                                    ->whereIn('pvv.value', $filterData['attributes']);
                            });
                        }
                    )
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['location_id'], function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['location_id']);
                    })
                    ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                        $query->where('locations.region_id', $filterData['region_id']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['date_range'][0])
                        )
                            ->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date_range'][1])
                            );
                    })
                    ->groupBy(
                        config('app.product_variant')
                            ? 'master_products.article_number'
                            : 'products.article_number'
                    ),
                'sale_totals',
                'sale_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                        DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                        $query->leftJoin(
                            'category_master_product',
                            'products.master_product_id',
                            '=',
                            'category_master_product.master_product_id'
                        );
                        $query->leftJoin(
                            'master_product_tag',
                            'master_product_tag.master_product_id',
                            '=',
                            'products.master_product_id'
                        );
                    }, function ($query): void {
                        $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                        $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                    })
                    ->when(
                        array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                            } else {
                                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                            }
                        }
                    )
                    ->when(
                        array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                            } else {
                                $query->whereIn('products.article_number', $filterData['article_numbers']);
                            }
                        }
                    )
                    ->when(
                        $filterData['category_ids'] && null !== $filterData['category_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'category_master_product.category_id',
                                    $filterData['category_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('category_product.category_id', $filterData['category_ids']);
                            }
                        }
                    )
                    ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                        }
                    )
                    ->when(
                        $filterData['department_ids'] && null !== $filterData['department_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'master_products.department_id',
                                    $filterData['department_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                            }
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                        }
                    )
                    ->when(
                        isset($filterData['attributes']) && $filterData['attributes'],
                        function ($q) use ($filterData): void {
                            $q->whereExists(function ($subQuery) use ($filterData): void {
                                $subQuery->select(DB::raw(1))
                                    ->from('product_variant_values as pvv')
                                    ->whereRaw('pvv.product_id = products.id')
                                    ->whereIn('pvv.value', $filterData['attributes']);
                            });
                        }
                    )
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['compare_location_id']);
                    })
                    ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use ($filterData): void {
                        $query->where('locations.region_id', $filterData['compare_region_id']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['date_range'][0])
                        )
                            ->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date_range'][1])
                            );
                    })
                    ->groupBy(
                        config('app.product_variant')
                            ? 'master_products.article_number'
                            : 'products.article_number'
                    ),
                'compare_sale_return_totals',
                'compare_sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                        DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                        $query->leftJoin(
                            'category_master_product',
                            'products.master_product_id',
                            '=',
                            'category_master_product.master_product_id'
                        );
                        $query->leftJoin(
                            'master_product_tag',
                            'master_product_tag.master_product_id',
                            '=',
                            'products.master_product_id'
                        );
                    }, function ($query): void {
                        $query->leftJoin('category_product', 'products.id', '=', 'category_product.product_id');
                        $query->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id');
                    })
                    ->when(
                        array_key_exists('tag_ids', $filterData) && null !== $filterData['tag_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw('master_product_tag.tag_id', $filterData['tag_ids']);
                            } else {
                                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
                            }
                        }
                    )
                    ->when(
                        array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                            } else {
                                $query->whereIn('products.article_number', $filterData['article_numbers']);
                            }
                        }
                    )
                    ->when(
                        $filterData['category_ids'] && null !== $filterData['category_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'category_master_product.category_id',
                                    $filterData['category_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('category_product.category_id', $filterData['category_ids']);
                            }
                        }
                    )
                    ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                        }
                    )
                    ->when(
                        $filterData['department_ids'] && null !== $filterData['department_ids'],
                        function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->whereIntegerInRaw(
                                    'master_products.department_id',
                                    $filterData['department_ids']
                                );
                            } else {
                                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                            }
                        }
                    )
                    ->when(
                        config(
                            'app.product_variant'
                        ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('products.style_id', $filterData['style_ids']);
                        }
                    )
                    ->when(
                        isset($filterData['attributes']) && $filterData['attributes'],
                        function ($q) use ($filterData): void {
                            $q->whereExists(function ($subQuery) use ($filterData): void {
                                $subQuery->select(DB::raw(1))
                                    ->from('product_variant_values as pvv')
                                    ->whereRaw('pvv.product_id = products.id')
                                    ->whereIn('pvv.value', $filterData['attributes']);
                            });
                        }
                    )
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['compare_location_id']);
                    })
                    ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use ($filterData): void {
                        $query->where('locations.region_id', $filterData['compare_region_id']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['date_range'][0])
                        )
                            ->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date_range'][1])
                            );
                    })
                    ->groupBy(
                        config('app.product_variant')
                            ? 'master_products.article_number'
                            : 'products.article_number'
                    ),
                'compare_sale_totals',
                'compare_sale_totals.product_id',
                '=',
                'products.id'
            )
            ->where('products.company_id', $companyId)
            ->where(function ($query): void {
                $query->whereNotNull('sale_return_totals.total_quantity_returned')
                    ->orWhereNotNull('sale_return_totals.total_returned_amount')
                    ->orWhereNotNull('sale_totals.total_quantity_sold')
                    ->orWhereNotNull('sale_totals.total_amount_sold')
                    ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                    ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                    ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                    ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
            })
            ->get());
    }

    public function getCachedProductQuantitySoldReportWithUpc(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                    DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })->groupBy('products.id')
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedSingleProductQuantitySoldReportWithUpc(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                    DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })->groupBy('products.id')
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedSingleCompareProductQuantitySoldReportWithUpc(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                    ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['compare_sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.name', $filterData['compare_sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['compare_sort_by']) {
                        $query->orderBy('colors.name', $filterData['compare_sort_direction']);
                    }

                    if ('upc' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.upc', $filterData['compare_sort_direction']);
                    }

                    if (config('app.product_variant')) {
                        $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                    } else {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['compare_sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['compare_sort_direction']);
                    }

                    if ('qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('amount_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['compare_sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })->groupBy('products.id')
                ->paginate($filterData['per_page']);
        });
    }

    public function getCachedProductQuantitySoldReportWithUpcCollection(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                    DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy('products.id')
                ->get();
        });
    }

    public function getCachedSingleProductQuantitySoldReportWithUpcCollection(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                    DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when($filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold');
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('qty_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                    }

                    if ('amount_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy('products.id')
                ->get();
        });
    }

    public function getCachedSingleComparedProductQuantitySoldReportWithUpcCollection(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
    GROUP_CONCAT(
        CASE
            WHEN product_variant_values.value IS NOT NULL
            THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
            ELSE NULL
        END
        SEPARATOR ', '
    ) as variant_values
"),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when($filterData['compare_region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->when(config('app.product_variant') === false, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->when($filterData['compare_sort_by'], function ($query) use ($filterData): void {
                    if ('product' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.name', $filterData['compare_sort_direction']);
                    }

                    if (config('app.product_variant') === false && 'color' === $filterData['compare_sort_by']) {
                        $query->orderBy('colors.name', $filterData['compare_sort_direction']);
                    }

                    if ('upc' === $filterData['compare_sort_by']) {
                        $query->orderBy('products.upc', $filterData['compare_sort_direction']);
                    }

                    if ('article_number' === $filterData['compare_sort_by']) {
                        if (config('app.product_variant')) {
                            $query->orderBy('master_products.article_number', $filterData['sort_direction']);
                        } else {
                            $query->orderBy('products.article_number', $filterData['sort_direction']);
                        }
                    }

                    if (config('app.product_variant') === false && 'size' === $filterData['compare_sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['compare_sort_direction']);
                    }

                    if ('qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('amount_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('total_amount_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_qty_sold' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_quantity_sold', $filterData['compare_sort_direction']);
                    }

                    if ('compare_sold_amount' === $filterData['compare_sort_by']) {
                        $query->orderBy('compare_total_amount_sold', $filterData['compare_sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->groupBy('products.id')
                ->get();
        });
    }

    public function getCachedConsolidateProductQuantitySoldSumAndCountWithUpc(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $selectedColumns = ['products.id as id', 'products.name as name', 'products.upc as upc'];

            if (config('app.product_variant')) {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.master_product_id',
                        'master_products.article_number as article_number',
                        DB::raw("
                            GROUP_CONCAT(
                                CASE
                                    WHEN product_variant_values.value IS NOT NULL
                                    THEN CONCAT_WS(' : ', attributes.name, product_variant_values.value)
                                    ELSE NULL
                                END
                                SEPARATOR ', '
                            ) as variant_values
                        "),
                    ]
                );
            } else {
                $selectedColumns = array_merge(
                    $selectedColumns,
                    [
                        'products.article_number as article_number',
                        'products.size_id',
                        'products.color_id',
                        'colors.name as color_name',
                        'sizes.name as size_name',
                    ]
                );
            }

            return Product::query()
                ->select(
                    DB::raw('SUM(sale_return_totals.total_quantity_returned) as total_quantity_returned'),
                    DB::raw('SUM(sale_return_totals.total_returned_amount) as total_returned_amount'),
                    DB::raw('SUM(sale_totals.total_quantity_sold) as total_quantity_sold'),
                    DB::raw('SUM(sale_totals.total_amount_sold) as total_amount_sold'),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_quantity_returned) as compare_total_quantity_returned'
                    ),
                    DB::raw(
                        'SUM(compare_sale_return_totals.compare_total_returned_amount) as compare_total_returned_amount'
                    ),
                    DB::raw('SUM(compare_sale_totals.compare_total_quantity_sold) as compare_total_quantity_sold'),
                    DB::raw('SUM(compare_sale_totals.compare_total_amount_sold) as compare_total_amount_sold'),
                    ...$selectedColumns,
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                    $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id');
                    $query->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id');
                }, function ($query): void {
                    $query->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
                })
                ->tap(fn ($query): Builder => $this->applyCommonMainProductFilters($query, $filterData))
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'sale_return_totals',
                    'sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when(0 !== (int) $filterData['region_id'], function ($query) use ($filterData): void {
                            $query->where('locations.region_id', $filterData['region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'sale_totals',
                    'sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_return_items')
                        ->select(
                            'sale_return_items.product_id',
                            DB::raw('SUM(sale_return_items.quantity) as compare_total_quantity_returned'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as compare_total_returned_amount')
                        )
                        ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_return_items.product_id'),
                    'compare_sale_return_totals',
                    'compare_sale_return_totals.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_items')
                        ->select(
                            'sale_items.product_id',
                            DB::raw('SUM(sale_items.quantity) as compare_total_quantity_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as compare_total_amount_sold')
                        )
                        ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['compare_location_id'], function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['compare_location_id']);
                        })
                        ->when(0 !== (int) $filterData['compare_region_id'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('locations.region_id', $filterData['compare_region_id']);
                        })
                        ->when($filterData['date_range'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['date_range'][0])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['date_range'][1])
                                );
                        })
                        ->groupBy('sale_items.product_id'),
                    'compare_sale_totals',
                    'compare_sale_totals.product_id',
                    '=',
                    'products.id'
                )
                ->where('products.company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereNotNull('sale_return_totals.total_quantity_returned')
                        ->orWhereNotNull('sale_return_totals.total_returned_amount')
                        ->orWhereNotNull('sale_totals.total_quantity_sold')
                        ->orWhereNotNull('sale_totals.total_amount_sold')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_quantity_returned')
                        ->orWhereNotNull('compare_sale_return_totals.compare_total_returned_amount')
                        ->orWhereNotNull('compare_sale_totals.compare_total_quantity_sold')
                        ->orWhereNotNull('compare_sale_totals.compare_total_amount_sold');
                })
                ->get();
        });
    }

    public function getActiveInventoryProductByUpcForStockAdjustment(string $productsUpc, int $companyId): ?Product
    {
        $isProductVariant = config('app.product_variant');
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Product::select(
            'id',
            'name',
            'unit_of_measure_id',
            'upc',
            'has_batch',
            'retail_price',
            'is_non_inventory',
            'master_product_id'
        )
            ->with(['masterProduct:' . $masterProductQueries->getBasicColumnsForInventory()])
            ->onlyActive()
            ->where('upc', $productsUpc)
            ->where('company_id', $companyId)
            ->when(false === $isProductVariant, function ($query): void {
                $query->where('is_non_inventory', false);
            })
            ->when($isProductVariant, function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                });
            })
            ->first();
    }

    public function getProductsByUpcForInterCompany(array $productsUpc, int $companyId): Collection
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        $relation = [];
        $selectedColumns = [
            'id',
            'name',
            'compound_product_name',
            'unit_of_measure_id',
            'upc',
            'has_batch',
            'is_non_inventory',
            'status',
        ];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, ['master_product_id']);

            $relation = array_merge($relation, [
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relation = array_merge($relation, [
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ]);
        }

        return Product::select(...$selectedColumns)
            ->with($relation)
            ->where('company_id', $companyId)
            ->whereInCaseSensitive('upc', $productsUpc)
            ->get();
    }

    public function updateProductIdInTagsPivot(int $oldProductId, int $newProductId): void
    {
        DB::table('product_tag')
            ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function deleteProduct(int $companyId, int $productId): void
    {
        $product = Product::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->findOrFail($productId);

        $product->delete();
    }

    public function deleteDraftProducts(int $companyId, array $productIds): void
    {
        $products = Product::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('status', Statuses::DRAFT->value)
            ->whereIntegerInRaw('id', $productIds)
            ->get();

        foreach ($products as $product) {
            $product->delete();
        }
    }

    public function checkProductIsActive(int $companyId, int $productId): ?int
    {
        $product = Product::select('id', 'status')
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->find($productId);

        return $product?->status;
    }

    public function getProductSalesSummary(array $filterData, int $companyId): Collection
    {
        return Product::query()
            ->select(
                'products.id',
                'products.name',
                DB::raw(
                    '(COALESCE(product_sale_total.total_paid_amount, 0) - COALESCE(product_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(product_sale_total.units_sold, 0) - COALESCE(product_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['locationId']);
                    })
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        'counter_updates.opened_by_pos_at as created_at',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('product_id'),
                'product_sale_total',
                'product_sale_total.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('counters.location_id', $filterData['locationId']);
                    })
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->select(
                        'products.id as product_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('product_id'),
                'product_return_total',
                'product_return_total.product_id',
                '=',
                'products.id'
            )
            ->whereNotNull('product_sale_total.total_paid_amount')
            ->orWhereNotNull('product_sale_total.units_sold')
            ->orWhereNotNull('product_return_total.return_amount')
            ->orWhereNotNull('product_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForPaginate(
        array $filterData,
        int $companyId,
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughSalesAndReturnsDataByProductArticleNumber($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForConsolidateData(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughSalesAndReturnsDataByProductArticleNumber($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughSalesAndReturnsDataByProductArticleNumberForDashboard(
        array $filterData,
        int $companyId,
    ): Collection {
        $locationIds = $filterData['location_ids'] ?? null;
        $locationIds = is_array($locationIds) ? implode(',', $locationIds) : $locationIds;

        $cacheKey = 'cache-top-ranking-products-' . $companyId . '-' . $locationIds . '-' . $filterData['date'];

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => $this->commonQuerySellThroughSalesAndReturnsDataByProductArticleNumberForDashboard(
                $filterData,
                $companyId
            )->limit(10)
                ->having('sell_through', '<=', 100)
                ->get()
        );
    }

    public function commonQuerySellThroughSalesAndReturnsDataByProductArticleNumber(
        array $filterData,
        int $companyId,
    ): Builder {
        $mediaQueries = resolve(MediaQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $counterUpdateQueries = new CounterUpdateQueries();

        $soldLogic = 'CASE
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN (COALESCE(SUM(article_number_sale_total.units_sold), 0) + COALESCE(SUM(article_number_sale_total.foc_units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0))
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(SUM(article_number_sale_total.units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(SUM(article_number_sale_total.foc_units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0)
            ELSE 0
        END';

        return Product::query()
            ->with(
                'media:' . $mediaQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            )
            ->select(
                'id',
                'name',
                'color_id',
                'size_id',
                'retail_price as price',
                'article_number',
                DB::raw('SUM(product_inventory_update.received) as received'),
                DB::raw($soldLogic . ' AS sold'),
                DB::raw('sum(product_inventory_update_balance.total_closing_stock) as balance'),
                DB::raw("
                    CASE
                        WHEN (COALESCE(SUM(product_inventory_update.received), 0) = 0) THEN 0
                        ELSE (
                            {$soldLogic}
                            * 100 / COALESCE(SUM(product_inventory_update.received), 0)
                        )
                    END as sell_through
                ")
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['name', 'article_number'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->isSellingProduct()
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    $query->whereHas('tags', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                    });
                }
            )
            ->where('company_id', $companyId)
            ->where($this->productsFilterForSaleThrough($filterData))
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->where(function ($query) use ($inventoryUpdateQueries, $filterData): void {
                        $query->where(
                            $inventoryUpdateQueries->filterSellThroughDataBasedOnAffectedType($filterData)
                        );
                    })
                    ->whereNotIn('inventory_updates.affected_by_type', [
                        ModelMapping::SALE_ITEM->name,
                        ModelMapping::SALE_RETURN_ITEM->name,
                        ModelMapping::VOID_SALE->name,
                        ModelMapping::ORDER_ITEM->name,
                        ModelMapping::ORDER_RETURN_ITEM->name,
                        ModelMapping::ORDER->name,
                    ])
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                        $query->whereBetween('inventory_updates.happened_at', [
                            CommonFunctions::addStartTime($filterData['date_range'][0]),
                            CommonFunctions::addEndTime($filterData['date_range'][1]),
                        ]);
                    })
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.quantity) as received')
                    )
                    ->groupBy('product_id'),
                'product_inventory_update',
                'product_inventory_update.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->select(
                        DB::raw('SUM(inventory_updates.closing_stock) as total_closing_stock'),
                        'inventory_updates.product_id as product_id'
                    )
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->whereRaw('inventory_updates.happened_at = (
                        SELECT MAX(sub_inventory_updates.happened_at)
                        FROM inventory_updates as sub_inventory_updates
                        JOIN products as sub_products ON sub_products.id = sub_inventory_updates.product_id
                        WHERE sub_inventory_updates.product_id = inventory_updates.product_id
                        AND sub_inventory_updates.location_id = inventory_updates.location_id
                        ORDER BY sub_inventory_updates.happened_at DESC, sub_inventory_updates.id DESC, sub_inventory_updates.created_at DESC
                    )')
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                        $query->whereBetween('inventory_updates.happened_at', [
                            CommonFunctions::addStartTime($filterData['date_range'][0]),
                            CommonFunctions::addEndTime($filterData['date_range'][1]),
                        ]);
                    })
                    ->orderBy('inventory_updates.happened_at', 'desc')
                    ->orderBy('inventory_updates.id', 'desc')
                    ->orderBy('inventory_updates.created_at', 'desc')
                    ->groupBy('product_id'),
                'product_inventory_update_balance',
                'product_inventory_update_balance.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                        ),
                    )
                    ->groupBy('product_id'),
                'article_number_sale_total',
                'article_number_sale_total.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->select(
                        'products.id as product_id',
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('product_id'),
                'article_number_return_total',
                'article_number_return_total.product_id',
                '=',
                'products.id'
            )
            ->groupBy('products.article_number')
            ->where(function ($query): void {
                $query->orWhereNotNull('article_number_sale_total.units_sold')
                    ->orWhereNotNull('product_inventory_update.received')
                    ->orWhereNotNull('article_number_return_total.return_units')
                    ->orHaving('balance', '!=', 0);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function commonQuerySellThroughSalesAndReturnsDataByProductArticleNumberForDashboard(
        array $filterData,
        int $companyId,
    ): Builder {
        $mediaQueries = resolve(MediaQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $counterUpdateQueries = new CounterUpdateQueries();

        $soldLogic = 'CASE
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN (COALESCE(SUM(article_number_sale_total.units_sold), 0) + COALESCE(SUM(article_number_sale_total.foc_units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0))
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(SUM(article_number_sale_total.units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(SUM(article_number_sale_total.foc_units_sold), 0) - COALESCE(SUM(article_number_return_total.return_units), 0)
            ELSE 0
        END';

        return Product::query()
            ->with(
                'media:' . $mediaQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            )
            ->select(
                'id',
                'name',
                'color_id',
                'size_id',
                'retail_price as price',
                'article_number',
                DB::raw('SUM(product_inventory_update.received) as received'),
                DB::raw($soldLogic . ' AS sold'),
                DB::raw("
                    CASE
                        WHEN (COALESCE(SUM(product_inventory_update.received), 0) = 0) THEN 0
                        ELSE (
                            {$soldLogic}
                            * 100 / COALESCE(SUM(product_inventory_update.received), 0)
                        )
                    END as sell_through
                ")
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['name', 'article_number'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->isSellingProduct()
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    $query->whereHas('tags', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                    });
                }
            )
            ->where('company_id', $companyId)
            ->where($this->productsFilterForSaleThrough($filterData))
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->where(function ($query) use ($inventoryUpdateQueries, $filterData): void {
                        $query->where(
                            $inventoryUpdateQueries->filterSellThroughDataBasedOnAffectedType($filterData)
                        );
                    })
                    ->whereNotIn('inventory_updates.affected_by_type', [
                        ModelMapping::SALE_ITEM->name,
                        ModelMapping::SALE_RETURN_ITEM->name,
                        ModelMapping::VOID_SALE->name,
                        ModelMapping::ORDER_ITEM->name,
                        ModelMapping::ORDER_RETURN_ITEM->name,
                        ModelMapping::ORDER->name,
                    ])
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                        $query->whereBetween('inventory_updates.happened_at', [
                            CommonFunctions::addStartTime($filterData['date_range'][0]),
                            CommonFunctions::addEndTime($filterData['date_range'][1]),
                        ]);
                    })
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.quantity) as received')
                    )
                    ->groupBy('product_id'),
                'product_inventory_update',
                'product_inventory_update.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                        ),
                    )
                    ->groupBy('product_id'),
                'article_number_sale_total',
                'article_number_sale_total.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.article_number', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->select(
                        'products.id as product_id',
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('product_id'),
                'article_number_return_total',
                'article_number_return_total.product_id',
                '=',
                'products.id'
            )
            ->groupBy('products.article_number')
            ->where(function ($query): void {
                $query->whereNotNull('article_number_sale_total.units_sold')
                    ->orWhereNotNull('product_inventory_update.received')
                    ->orWhereNotNull('article_number_return_total.return_units');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function productsFilterForSaleThrough(array $filterData): Closure
    {
        return fn ($query) => $query
            ->when(
                array_key_exists('brand_id', $filterData) && null !== $filterData['brand_id'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                        ->where('master_products.brand_id', $filterData['brand_id']);
                    } else {
                        $query->where('brand_id', $filterData['brand_id']);
                    }
                }
            )
            ->when(
                array_key_exists('product_id', $filterData) && null !== $filterData['product_id'],
                function ($query) use ($filterData): void {
                    $query->where('products.id', $filterData['product_id']);
                }
            )
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'size_id',
                    $filterData
                ) && null !== $filterData['size_id'],
                function ($query) use ($filterData): void {
                    $query->where('size_id', $filterData['size_id']);
                }
            )
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'color_ids',
                    $filterData
                ) && null !== $filterData['color_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                }
            )
            ->when(
                array_key_exists('department_ids', $filterData) && [] !== $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                }
            )
            ->when(
                array_key_exists('article_numbers', $filterData) && [] !== $filterData['article_numbers'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->whereIn('article_number', $filterData['article_numbers']);
                    }
                }
            )
            ->when(
                array_key_exists('
                ', $filterData) && null !== $filterData['category_id'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->join(
                            'category_master_product',
                            'master_products.id',
                            '=',
                            'category_master_product.master_product_id'
                        )
                            ->whereRaw(
                                'master_products.id IN (select master_product_id from category_master_product where category_id = ' . $filterData['category_id'] . ')'
                            );
                    } else {
                        $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                            ->whereRaw(
                                'products.id IN (select product_id from category_product where category_id = ' . $filterData['category_id'] . ')'
                            );
                    }
                }
            )
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_collection_products where product_collection_id = ' . $filterData['product_collection_id'] . ')'
                    );
                }
            )
            ->when(array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'], function ($query) use (
                $filterData
            ): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'products.master_product_id IN (select master_product_id from master_product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                }
            })
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'style_ids',
                    $filterData
                ) && [] !== $filterData['style_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('style_id', $filterData['style_ids']);
                }
            )
            ->when(
                null !== $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->leftJoinSub(
                        DB::table('product_variant_values')
                            ->select('product_id')
                            ->whereIn('value', $filterData['attributes'])
                            ->groupBy('product_id'),
                        'product_variants',
                        'product_variants.product_id',
                        '=',
                        'products.id'
                    );
                }
            );
    }

    public function productsFilterForSellThroughAggregate(array $filterData, int $companyId): Closure
    {
        return fn ($query) => $query->where('products.company_id', $companyId)
            ->whereNull('products.deleted_at')
            ->when(config('app.product_variant'), function ($query): void {
                $query->where('master_products.is_non_selling_item', false);
            }, function ($query): void {
                $query->where('products.is_non_selling_item', false);
            })
            ->when(
                array_key_exists('brand_id', $filterData) && null !== $filterData['brand_id'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->where('master_products.brand_id', $filterData['brand_id']);
                    } else {
                        $query->where('brand_id', $filterData['brand_id']);
                    }
                }
            )
            ->when(
                array_key_exists('product_id', $filterData) && null !== $filterData['product_id'],
                function ($query) use ($filterData): void {
                    $query->where('products.id', $filterData['product_id']);
                }
            )
            ->when(
                array_key_exists('size_id', $filterData) && null !== $filterData['size_id'] && config(
                    'app.product_variant'
                ) === false,
                function ($query) use ($filterData): void {
                    $query->where('size_id', $filterData['size_id']);
                }
            )
            ->when(
                array_key_exists('color_ids', $filterData) && null !== $filterData['color_ids'] && config(
                    'app.product_variant'
                ) === false,
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                }
            )
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            )
            ->when(
                array_key_exists('department_ids', $filterData) && [] !== $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                }
            )
            ->when(
                array_key_exists('article_numbers', $filterData) && [] !== $filterData['article_numbers'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->whereIn('products.article_number', $filterData['article_numbers']);
                    }
                }
            )
            ->when(
                array_key_exists('category_id', $filterData) && null !== $filterData['category_id'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->join(
                            'category_master_product',
                            'master_products.id',
                            '=',
                            'category_master_product.master_product_id'
                        )
                            ->whereRaw(
                                'master_products.id IN (select master_product_id from category_master_product where category_id = ' . $filterData['category_id'] . ')'
                            );
                    } else {
                        $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                            ->whereRaw(
                                'products.id IN (select product_id from category_product where category_id = ' . $filterData['category_id'] . ')'
                            );
                    }
                }
            )
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_collection_products where product_collection_id = ' . $filterData['product_collection_id'] . ')'
                    );
                }
            )
            ->when(array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'], function ($query) use (
                $filterData
            ): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'master_products.id IN (select master_product_id from master_product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                }
            })
            ->when(
                array_key_exists('style_ids', $filterData) && [] !== $filterData['style_ids'] && config(
                    'app.product_variant'
                ) === false,
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('style_id', $filterData['style_ids']);
                }
            );
    }

    public function commonQuerySellThroughSalesAndReturnsDataByProductUpc(
        array $filterData,
        int $companyId,
    ): Builder {
        $mediaQueries = resolve(MediaQueries::class);
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $counterUpdateQueries = new CounterUpdateQueries();

        $soldLogic = 'CASE
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN (COALESCE(upc_sale_total.units_sold, 0) + COALESCE(upc_sale_total.foc_units_sold, 0) - COALESCE(upc_return_total.return_units, 0))
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(upc_sale_total.units_sold, 0) - COALESCE(upc_return_total.return_units, 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(upc_sale_total.foc_units_sold, 0) - COALESCE(upc_return_total.return_units, 0)
            ELSE 0
        END';

        return Product::query()
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->select(
                'id',
                'name',
                'retail_price as price',
                'upc',
                'color_id',
                'size_id',
                'product_inventory_update.received',
                'product_inventory_update_balance.total_closing_stock as balance',
                DB::raw($soldLogic . ' AS sold'),
                DB::raw("
                    CASE
                        WHEN (COALESCE(product_inventory_update.received, 0) = 0) THEN 0
                        ELSE (
                            ({$soldLogic})
                            * 100 / COALESCE(product_inventory_update.received, 0)
                        )
                    END as sell_through
                ")
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['name', 'upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->where('is_non_selling_item', false)
            ->whereNull('deleted_at')
            ->where('company_id', $companyId)
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    $query->whereHas('tags', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                    });
                }
            )
            ->where($this->productsFilterForSaleThrough($filterData))
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.upc', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->where(function ($query) use ($inventoryUpdateQueries, $filterData): void {
                        $query->where(
                            $inventoryUpdateQueries->filterSellThroughDataBasedOnAffectedType($filterData)
                        );
                    })
                    ->whereNotIn('inventory_updates.affected_by_type', [
                        ModelMapping::SALE_ITEM->name,
                        ModelMapping::SALE_RETURN_ITEM->name,
                        ModelMapping::VOID_SALE->name,
                        ModelMapping::ORDER_ITEM->name,
                        ModelMapping::ORDER_RETURN_ITEM->name,
                        ModelMapping::ORDER->name,
                    ])
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                        $query->whereBetween('inventory_updates.happened_at', [
                            CommonFunctions::addStartTime($filterData['date_range'][0]),
                            CommonFunctions::addEndTime($filterData['date_range'][1]),
                        ]);
                    })
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.quantity) as received')
                    )
                    ->groupBy('product_id'),
                'product_inventory_update',
                'product_inventory_update.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.upc', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->whereRaw('inventory_updates.happened_at = (
                        SELECT MAX(sub_inventory_update.happened_at)
                        FROM inventory_updates as sub_inventory_update
                        JOIN products as sub_products ON sub_products.id = sub_inventory_update.product_id
                        WHERE sub_inventory_update.product_id = inventory_updates.product_id
                        AND sub_inventory_update.location_id = inventory_updates.location_id
                        ORDER BY sub_inventory_update.happened_at DESC, sub_inventory_update.id DESC, sub_inventory_update.created_at DESC
                    )')
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                        $query->whereBetween('inventory_updates.happened_at', [
                            CommonFunctions::addStartTime($filterData['date_range'][0]),
                            CommonFunctions::addEndTime($filterData['date_range'][1]),
                        ]);
                    })
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.closing_stock) as total_closing_stock')
                    )
                    ->orderBy('inventory_updates.happened_at', 'desc')
                    ->orderBy('inventory_updates.id', 'desc')
                    ->orderBy('inventory_updates.created_at', 'desc')
                    ->groupBy('product_id'),
                'product_inventory_update_balance',
                'product_inventory_update_balance.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.upc', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                        ),
                    )
                    ->groupBy('product_id'),
                'upc_sale_total',
                'upc_sale_total.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->when($filterData['search_text'], function ($query) use ($filterData): void {
                        $query->where('products.name', 'like', '%' . $filterData['search_text'] . '%')
                            ->orWhere('products.upc', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                        $query->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        );
                    })
                    ->when(null !== $filterData['date_range'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where(
                            $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                        );
                    })
                    ->select('products.id as product_id', DB::raw('SUM(sale_return_items.quantity) as return_units'))
                    ->groupBy('product_id'),
                'upc_return_total',
                'upc_return_total.product_id',
                '=',
                'products.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('upc_sale_total.units_sold')
                    ->orWhereNotNull('product_inventory_update.received')
                    ->orWhereNotNull('upc_return_total.return_units')
                    ->orWhereNot('product_inventory_update_balance.total_closing_stock', 0);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getCachedSellThroughForSummarySaleThroughReportWithStore(
        array $filterData,
        int $companyId,
    ): Collection {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey . now(), $cacheExpireTime, function () use ($companyId, $filterData) {
            $inventoryUpdateQueries = new InventoryUpdateQueries();
            $counterUpdateQueries = new CounterUpdateQueries();

            return Product::query()
                ->select(
                    'id',
                    'name',
                    'article_number',
                    'product_inventory_update_location.color_id',
                    'product_inventory_update_location.location_name',
                    'product_inventory_update_location.location_id',
                    'product_inventory_update_location.color_name',
                    DB::raw('COALESCE(product_inventory_update_location.received, 0) as received'),
                    DB::raw('COALESCE(product_inventory_update_location_balance.total_closing_stock, 0) as balance'),
                    DB::raw('CASE
                    WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN (COALESCE(size_sale_total.units_sold, 0) + COALESCE(size_sale_total.foc_units_sold, 0) - COALESCE(size_return_total.return_units, 0))
                    WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(size_sale_total.units_sold, 0) - COALESCE(size_return_total.return_units, 0)
                    WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(size_sale_total.foc_units_sold, 0) - COALESCE(size_return_total.return_units, 0)
                    ELSE 0
                END AS sold'),
                )
                ->where('company_id', $companyId)
                ->where('is_non_selling_item', 0)
                ->join('category_product', 'products.id', '=', 'category_product.product_id')
                ->where($this->productsFilterForSaleThrough($filterData))
                ->leftJoinSub(
                    DB::table('inventory_updates')
                        ->whereNotIn('inventory_updates.affected_by_type', [
                            ModelMapping::SALE_ITEM->name,
                            ModelMapping::SALE_RETURN_ITEM->name,
                            ModelMapping::VOID_SALE->name,
                            ModelMapping::ORDER_ITEM->name,
                            ModelMapping::ORDER_RETURN_ITEM->name,
                            ModelMapping::ORDER->name,
                        ])
                        ->leftJoin('locations', function ($join) use ($filterData): void {
                            $join->on('inventory_updates.location_id', '=', 'locations.id')
                                ->when(
                                    array_key_exists(
                                        'location_ids',
                                        $filterData
                                    ) && null !== $filterData['location_ids'],
                                    function ($query) use ($filterData): void {
                                        $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                                    }
                                );
                        })
                        ->join('products', 'inventory_updates.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->where('products.is_non_selling_item', false)
                        ->whereNull('products.deleted_at')
                        ->where($this->productsFilterForSaleThrough($filterData))
                        ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.happened_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date'])
                            );
                        })
                        ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                            $query->whereBetween('inventory_updates.happened_at', [
                                CommonFunctions::addStartTime($filterData['date_range'][0]),
                                CommonFunctions::addEndTime($filterData['date_range'][1]),
                            ]);
                        })
                        ->select(
                            'inventory_updates.product_id as product_id',
                            'locations.code AS location_name',
                            'locations.id AS location_id',
                            'colors.id as color_id',
                            'colors.name as color_name',
                            DB::raw('SUM(inventory_updates.quantity) as received')
                        )
                        ->groupBy('color_id'),
                    'product_inventory_update_location',
                    'product_inventory_update_location.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('inventory_updates')
                        ->leftJoin('locations', function ($join) use ($filterData): void {
                            $join->on('inventory_updates.location_id', '=', 'locations.id')
                                ->when(
                                    array_key_exists(
                                        'location_ids',
                                        $filterData
                                    ) && null !== $filterData['location_ids'],
                                    function ($query) use ($filterData): void {
                                        $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                                    }
                                );
                        })
                        ->join('products', 'inventory_updates.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->where('products.is_non_selling_item', false)
                        ->whereNull('products.deleted_at')
                        ->whereRaw('inventory_updates.happened_at = (
                            SELECT MAX(sub_inventory_update.happened_at)
                            FROM inventory_updates as sub_inventory_update
                            JOIN products as sub_products ON sub_products.id = sub_inventory_update.product_id
                            WHERE sub_inventory_update.product_id = inventory_updates.product_id
                            AND sub_inventory_update.location_id = inventory_updates.location_id
                            ORDER BY sub_inventory_update.happened_at DESC, sub_inventory_update.id DESC, sub_inventory_update.created_at DESC
                        )')
                        ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.happened_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date'])
                            );
                        })
                        ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                            $query->whereBetween('inventory_updates.happened_at', [
                                CommonFunctions::addStartTime($filterData['date_range'][0]),
                                CommonFunctions::addEndTime($filterData['date_range'][1]),
                            ]);
                        })
                        ->select(
                            'inventory_updates.product_id as product_id',
                            DB::raw('SUM(inventory_updates.closing_stock) as total_closing_stock')
                        )
                        ->orderBy('inventory_updates.happened_at', 'desc')
                        ->orderBy('inventory_updates.id', 'desc')
                        ->orderBy('inventory_updates.created_at', 'desc')
                        ->groupBy('color_id'),
                    'product_inventory_update_location_balance',
                    'product_inventory_update_location_balance.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->where('locations.company_id', $companyId)
                        ->where('products.is_non_selling_item', false)
                        ->whereNull('products.deleted_at')
                        ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date'])
                            );
                        })
                        ->when(null !== $filterData['date_range'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where(
                                $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                            );
                        })
                        ->when(
                            array_key_exists('location_ids', $filterData) &&
                                null !== $filterData['location_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                            }
                        )
                        ->where($this->productsFilterForSaleThrough($filterData))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'locations.id as location_id',
                            'products.id as product_id',
                            'products.name as product_name',
                            'colors.id as color_id',
                            'colors.name as color_name',
                            'locations.name as location_name',
                            DB::raw(
                                'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                            ),
                            DB::raw(
                                'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                            ),
                        )
                        ->groupBy('color_id'),
                    'size_sale_total',
                    'size_sale_total.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->where('locations.company_id', $companyId)
                        ->where('products.is_non_selling_item', false)
                        ->whereNull('products.deleted_at')
                        ->when(
                            array_key_exists('location_ids', $filterData) &&
                                null !== $filterData['location_ids'],
                            function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                            }
                        )
                        ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '<=',
                                CommonFunctions::addEndTime($filterData['date'])
                            );
                        })
                        ->when(null !== $filterData['date_range'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where(
                                $counterUpdateQueries->sellThroughReportDateConditionCheck($filterData['date_range'])
                            );
                        })
                        ->where($this->productsFilterForSaleThrough($filterData))
                        ->select(
                            'products.id as product_id',
                            'locations.id as location_id',
                            'products.name as product_name',
                            'colors.id as color_id',
                            'colors.name as color_name',
                            'locations.name as location_name',
                            DB::raw('SUM(sale_return_items.quantity) as return_units')
                        )
                        ->groupBy('color_id'),
                    'size_return_total',
                    'size_return_total.product_id',
                    '=',
                    'products.id'
                )
                ->where(function ($query): void {
                    $query->orWhereNotNull('product_inventory_update_location.location_name');
                })
                ->get();
        });
    }

    public function productsFilterForSaleThroughAnalysis(array $filterData): Closure
    {
        return fn ($query) => $query->when(
            array_key_exists('brand_ids', $filterData) && [] !== $filterData['brand_ids'],
            function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                }
            }
        )
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'size_ids',
                    $filterData
                ) && [] !== $filterData['size_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('size_id', $filterData['size_ids']);
                }
            )
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'color_ids',
                    $filterData
                ) && [] !== $filterData['color_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('color_id', $filterData['color_ids']);
                }
            )->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                function ($query) use ($filterData): void {
                    $query->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            )
            ->when(
                array_key_exists('department_ids', $filterData) && [] !== $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                }
            )
            ->when(
                array_key_exists('article_numbers', $filterData) && [] !== $filterData['article_numbers'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->whereIn('article_number', $filterData['article_numbers']);
                    }
                }
            )
            ->when(array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'], function ($query) use (
                $filterData
            ): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'products.master_product_id IN (select master_product_id from master_product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_tag where tag_id in (' . implode(
                            ',',
                            $filterData['tag_ids']
                        ) . '))'
                    );
                }
            })
            ->when(
                array_key_exists('category_ids', $filterData) && [] !== $filterData['category_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereRaw(
                            'products.master_product_id IN (select master_product_id from category_master_product where category_id in (' . implode(
                                ',',
                                $filterData['category_ids']
                            ) . '))'
                        );
                    } else {
                        $query->whereRaw(
                            'products.id IN (select product_id from category_product where category_id in (' . implode(
                                ',',
                                $filterData['category_ids']
                            ) . '))'
                        );
                    }
                }
            )
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereRaw(
                        'products.id IN (select product_id from product_collection_products where product_collection_id =' . $filterData['product_collection_id'] . ')'
                    );
                }
            )
            ->when(
                array_key_exists('product_id', $filterData) && null !== $filterData['product_id'],
                function ($query) use ($filterData): void {
                    $query->where('products.id', $filterData['product_id']);
                }
            )
            ->when(
                config('app.product_variant') === false && array_key_exists(
                    'style_ids',
                    $filterData
                ) && [] !== $filterData['style_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('style_id', $filterData['style_ids']);
                }
            );
    }

    public function getCachedSaleThroughAnalysisData(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember($cacheKey, $cacheExpireTime, function () use ($filterData, $companyId) {
            $colorQueries = new ColorQueries();
            $sizeQueries = new SizeQueries();
            $attributeQueries = resolve(AttributeQueries::class);
            $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
            $masterProductQueries = resolve(MasterProductQueries::class);
            $categoryQueries = resolve(CategoryQueries::class);
            $tagQueries = resolve(TagQueries::class);

            if (config('app.product_variant')) {
                $relations = [
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ];
            } else {
                $relations = [
                    'color:' . $colorQueries->getBasicColumnNames(),
                    'size:' . $sizeQueries->getBasicColumnNames(),
                ];
            }

            return Product::query()
                ->with($relations)
                ->select(
                    'products.id',
                    'master_product_id',
                    'products.name',
                    'retail_price as price',
                    'upc',
                    'color_id',
                    'size_id',
                    'products.article_number',
                    'product_inventory_update.received',
                    'upc_sale_total.units_sold as sold',
                    'upc_return_total.return_units as returned',
                    DB::raw('
                        COALESCE(product_inventory_update.received, 0) - (COALESCE(upc_sale_total.units_sold, 0) - COALESCE(upc_return_total.return_units, 0)) as balance
                    '),
                    DB::raw('
                        CASE
                            WHEN (COALESCE(product_inventory_update.received, 0) = 0) THEN 0
                            ELSE (
                                (COALESCE(upc_sale_total.units_sold, 0) - COALESCE(upc_return_total.return_units, 0))
                                * 100 / COALESCE(product_inventory_update.received, 0)
                            )
                        END as sell_through
                    '),
                    DB::raw(
                        '(COALESCE(upc_sale_total.total_paid_amount, 0) - COALESCE(upc_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(upc_sale_total.units_sold, 0) - COALESCE(upc_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->when(config('app.product_variant'), function ($query): void {
                    $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
                })
                ->where('products.company_id', $companyId)
                ->when(
                    array_key_exists('category_ids', $filterData) && [] !== $filterData['category_ids'],
                    function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                                $query->whereHas('categories', function ($query) use ($filterData): void {
                                    $query->select('id')
                                        ->onlyActive()
                                        ->whereIntegerInRaw('id', $filterData['category_ids']);
                                });
                            });
                        } else {
                            $query->whereHas('categories', function ($query) use ($filterData): void {
                                $query->select('id')
                                    ->onlyActive()
                                    ->whereIntegerInRaw('id', $filterData['category_ids']);
                            });
                        }
                    }
                )
                ->when(
                    array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                    function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                                $query->whereHas('tags', function ($query) use ($filterData): void {
                                    $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                                });
                            });
                        } else {
                            $query->whereHas('tags', function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                            });
                        }
                    }
                )
                ->where($this->productsFilterForSaleThroughAnalysis($filterData))
                ->leftJoinSub(
                    DB::table('inventory_updates')
                        ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                        })
                        ->where($this->productsFilterForSaleThroughAnalysis($filterData))
                        ->whereNotIn('inventory_updates.affected_by_type', [
                            ModelMapping::SALE_ITEM->name,
                            ModelMapping::SALE_RETURN_ITEM->name,
                        ])
                        ->when(
                            array_key_exists('location_id', $filterData) &&
                                null !== $filterData['location_id'],
                            function ($query) use ($filterData): void {
                                $query
                                    ->where('inventory_updates.location_id', (int) $filterData['location_id']);
                            }
                        )
                        ->where('inventory_updates.happened_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                        ->when($filterData['search_text'], function ($query) use ($filterData, $companyId): void {
                            $query->where(
                                $this->searchByProductForSaleAnalysis($filterData['search_text'], $companyId)
                            );
                        })
                        ->select(
                            'inventory_updates.product_id as product_id',
                            DB::raw('SUM(inventory_updates.quantity) as received')
                        )
                        ->groupBy('product_id'),
                    'product_inventory_update',
                    'product_inventory_update.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                        })
                        ->where($this->productsFilterForSaleThroughAnalysis($filterData))
                        ->where('locations.company_id', $companyId)
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        )
                        ->when(
                            array_key_exists('location_id', $filterData) &&
                                null !== $filterData['location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('locations.id', (int) $filterData['location_id']);
                            }
                        )
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->when($filterData['search_text'], function ($query) use ($filterData, $companyId): void {
                            $query->where(
                                $this->searchByProductForSaleAnalysis($filterData['search_text'], $companyId)
                            );
                        })
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        )
                        ->groupBy('product_id'),
                    'upc_sale_total',
                    'upc_sale_total.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->when(config('app.product_variant'), function ($query): void {
                            $query->leftJoin(
                                'master_products',
                                'products.master_product_id',
                                '=',
                                'master_products.id'
                            );
                        })
                        ->where($this->productsFilterForSaleThroughAnalysis($filterData))
                        ->where('locations.company_id', $companyId)
                        ->when(
                            array_key_exists('location_id', $filterData) &&
                                null !== $filterData['location_id'],
                            function ($query) use ($filterData): void {
                                $query->where('locations.id', (int) $filterData['location_id']);
                            }
                        )
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['date'])
                        )
                        ->when($filterData['search_text'], function ($query) use ($filterData, $companyId): void {
                            $query->where(
                                $this->searchByProductForSaleAnalysis($filterData['search_text'], $companyId)
                            );
                        })
                        ->select(
                            'products.id as product_id',
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        )
                        ->groupBy('product_id'),
                    'upc_return_total',
                    'upc_return_total.product_id',
                    '=',
                    'products.id'
                )
                ->whereNotNull('upc_sale_total.units_sold')
                ->orWhereNotNull('product_inventory_update.received')
                ->orWhereNotNull('upc_return_total.return_units')
                ->when($filterData['search_text'], function ($query) use ($filterData, $companyId): void {
                    $query->where($this->searchByProductForSaleAnalysis($filterData['search_text'], $companyId));
                })
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    if ('name' === $filterData['sort_by']) {
                        $query->orderBy('products.name', $filterData['sort_direction']);
                    }

                    if ('upc' === $filterData['sort_by']) {
                        $query->orderBy('products.upc', $filterData['sort_direction']);
                    }

                    if ('article_number' === $filterData['sort_by']) {
                        $query->orderBy('products.article_number', $filterData['sort_direction']);
                    }

                    if ('color' === $filterData['sort_by']) {
                        $query->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('size' === $filterData['sort_by']) {
                        $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                    }

                    if ('total_sales' === $filterData['sort_by']) {
                        $query->orderBy('total_sales', $filterData['sort_direction']);
                    }

                    if ('total_units_sold' === $filterData['sort_by']) {
                        $query->orderBy('total_units_sold', $filterData['sort_direction']);
                    }
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->get();
        });
    }

    public function searchByProductForSaleAnalysis(string $searchText, int $companyId): Closure
    {
        return fn ($query) => $query->where(function ($query) use ($searchText): void {
            $query
                ->whereAny(
                    ['products.name', 'products.article_number', 'products.upc'],
                    'LIKE',
                    '%' . $searchText . '%'
                );
        })
            ->where('products.company_id', $companyId);
    }

    public function getPaginatedProductsAgeingReport(array $filterData, int $companyId): PaginationLengthAwarePaginator
    {
        return $this->getProductAgeingReport($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getProductsAgeingReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getProductAgeingReport($filterData, $companyId)->get();
    }

    public function getPaginatedProductsAgeingReportByMonthAndYear(
        array $filterData,
        int $companyId,
    ): PaginationLengthAwarePaginator {
        return $this->getProductAgeingReportByMonthAndYear($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getProductsAgeingReportByMonthAndYearForExport(array $filterData, int $companyId): Collection
    {
        return $this->getProductAgeingReportByMonthAndYear($filterData, $companyId)->get();
    }

    public function getByIdOnlyName(int $productId, int $companyId): Product
    {
        return Product::query()
            ->select('id', 'name', 'code', 'compound_product_name')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function getByIdOnlyNameAndUpc(int $productId, int $companyId): Product
    {
        return Product::query()
            ->select('id', 'name', 'code', 'compound_product_name', 'upc')
            ->where('company_id', $companyId)
            ->findOrFail($productId);
    }

    public function getByIdWithUpc(int $productId): ?Product
    {
        return Product::query()
            ->select('id', 'upc')
            ->where('id', $productId)
            ->first();
    }

    public function getByIdWithVerificationQrCode(int $productId): ?Product
    {
        return Product::query()
            ->select('id', 'name', 'upc', 'verification_qr_code')
            ->where('id', $productId)
            ->first();
    }

    public function getIdByUpcForLoyaltyPoint(string $upc, int $companyId): ?int
    {
        return Product::select('id')->where('upc', $upc)->where('company_id', $companyId)->first()?->id;
    }

    public function getActiveFilteredRegularProducts(array $filterData, int $companyId): Collection
    {
        return $this->getActiveFilteredProductsQuery($filterData, $companyId)
            ->when(false === config('app.product_variant'), function ($query): void {
                $query->where('is_non_inventory', false)
                    ->where('type_id', ProductTypes::REGULAR_PRODUCT->value);
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false)
                        ->where('type_id', ProductTypes::REGULAR_PRODUCT->value);
                });
            })
            ->get();
    }

    public function getActiveRegularProductsFilteredByNameBrandAndCategory(
        array $filterData,
        int $companyId,
    ): Collection {
        return $this->getActiveProductsFilteredByNameBrandAndCategoryQuery($filterData, $companyId)
            ->where('is_non_inventory', false)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->get();
    }

    public function existsByIdAndCompanyId(int $productId, int $companyId): bool
    {
        return Product::select('id')
            ->where('id', $productId)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getSellingProduct(): Closure
    {
        $columns = explode(',', $this->getBasicColumnNames());

        return fn ($query) => $query->select(...$columns)
            ->isSellingProduct();
    }

    public function getSellingProductWithRelation(): Closure
    {
        $columns = explode(',', $this->getCommonRelationColumns());

        return fn ($query) => $query->select(...$columns)
            ->isSellingProduct();
    }

    public function removeProductImage(int $productId, int $mediaId): void
    {
        $product = Product::query()
            ->select('id')
            ->findOrFail($productId);

        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $product->getMedia('images')->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function removeProductVideo(int $productId, int $mediaId): void
    {
        $product = Product::query()
            ->select('id')
            ->findOrFail($productId);
        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $product->getMedia('videos')->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function removeProductThumbnail(int $productId): void
    {
        $product = Product::query()
            ->select('id')
            ->findOrFail($productId);

        // We are using directly clearMediaCollection function because here we are not playing with the file we are just deleting a records. And, Spatie media library will taken care of it.
        $product->clearMediaCollection('thumbnail');
    }

    public function getFilteredProducts(array $filterData): array
    {
        $categoryQueries = new CategoryQueries();

        if (! isset($filterData['filter_by'])) {
            return [];
        }

        if (
            (int) $filterData['filter_by'] === StockMovementFilters::BY_PRODUCT->value ||
            (int) $filterData['filter_by'] === StockMovementFilters::BY_PRODUCTS->value
        ) {
            return [];
        }

        return Product::query()
            ->select('id', 'name')
            ->where('company_id', $filterData['company_id'])
            ->when(
                (int) $filterData['filter_by'] === StockMovementFilters::BY_MASTER_PRODUCT->value && isset($filterData['article_number']) && $filterData['article_number'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->where('article_number', $filterData['article_number']);
                        });
                    } else {
                        $query->where('article_number', $filterData['article_number']);
                    }
                }
            )
            ->when(
                (int) $filterData['filter_by'] === StockMovementFilters::BY_BRAND->value && isset($filterData['brand_ids']) && $filterData['brand_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                    }
                }
            )
            ->when(
                (int) $filterData['filter_by'] === StockMovementFilters::BY_DEPARTMENT->value && isset($filterData['department_ids']) && $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                }
            )
            ->when(
                (int) $filterData['filter_by'] === StockMovementFilters::BY_CATEGORIES->value && isset($filterData['category_ids']) && $filterData['category_ids'],
                function ($query) use ($filterData, $categoryQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                        });
                    } else {
                        $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                    }
                }
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_selling_item', false);
                });
            }, function ($query): void {
                $query->where('is_non_selling_item', false);
            })
            ->pluck('id')
            ->toArray();
    }

    public function loadCategoriesForProduct(Product $product): Product
    {
        $categoryQueries = resolve(CategoryQueries::class);

        return $product->load(['categories:' . $categoryQueries->getBasicColumnNamesForPosMemberApi()]);
    }

    public function addNewFromExternalProduct(array $productData, User $user): void
    {
        $productData['created_by_id'] = $user->id;
        $productData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $tagIds = $productData['tag_ids'];
        $categoryIds = $productData['category_ids'];

        unset(
            $productData['id'],
            $productData['tag_ids'],
            $productData['category_ids'],
            $productData['size'],
            $productData['tags'],
            $productData['brand'],
            $productData['color'],
            $productData['style'],
            $productData['categories'],
            $productData['department'],
            $productData['season'],
            $productData['created_at'],
            $productData['deleted_at'],
            $productData['updated_at'],
            $productData['unit_of_measure'],
            $productData['sender_company']
        );

        $product = Product::create($productData);

        $this->updateTags($product, $tagIds);
        $this->updateCategories($product, $categoryIds);

        ProductCollectionUpdateByProductJob::dispatch($product->id, $productData['company_id'])->onQueue('medium');
    }

    public function addNewFromExternalProductForVariant(array $productData, User $user): void
    {
        $masterProductQueries = resolve(MasterProductQueries::class);

        $productData['created_by_id'] = $user->id;
        $productData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $masterProductData = $productData;

        unset(
            $productData['id'],
            $productData['tag_ids'],
            $productData['category_ids'],
            $productData['size'],
            $productData['tags'],
            $productData['brand'],
            $productData['color'],
            $productData['style'],
            $productData['categories'],
            $productData['department'],
            $productData['season'],
            $productData['created_at'],
            $productData['deleted_at'],
            $productData['updated_at'],
            $productData['unit_of_measure'],
            $productData['sender_company'],
            $productData['template_id'],
            $productData['master_product'],
            $productData['product_variant_values'],
            $productData['master_product_id'],
        );

        $product = Product::create($productData);

        $masterProduct = $masterProductQueries->firstOrCreateWithRelations($masterProductData, $product->id);

        if ($masterProduct) {
            $product->update([
                'master_product_id' => $masterProduct->id,
            ]);
        }

        ProductCollectionUpdateByProductJob::dispatch($product->id, $productData['company_id'])->onQueue('medium');
    }

    public function getActiveProductByIdWithRelations(int $draftProductId): Product
    {
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return Product::query()
                ->select(
                    'id',
                    'company_id',
                    'name',
                    'description',
                    'compound_product_name',
                    'code',
                    'upc',
                    'ean',
                    'custom_sku',
                    'manufacturer_sku',
                    'type_id',
                    'retail_price',
                    'franchise_price_1',
                    'franchise_price_2',
                    'franchise_price_3',
                    'wholesale_price',
                    'company_or_tender_price',
                    'branch_price',
                    'minimum_price',
                    'original_capital_price',
                    'capital_price',
                    'staff_price',
                    'purchase_cost',
                    'online_price',
                    'is_temporarily_unavailable',
                    'status',
                    'is_available_in_pos',
                    'is_available_in_ecommerce',
                    'is_sold_as_single_item',
                    'created_at',
                    'updated_at',
                    'master_product_id',
                )
                ->with([
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                    'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ])
                ->where('status', Statuses::ACTIVE->value)
                ->findOrFail($draftProductId);
        }

        return Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'description',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'brand_id',
                'color_id',
                'size_id',
                'department_id',
                'sub_department_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'purchase_cost',
                'online_price',
                'created_by_id',
                'created_by_type',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'is_available_in_pos',
                'is_available_in_ecommerce',
                'is_sold_as_single_item',
                'created_at',
                'updated_at'
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'season:' . $seasonQueries->getBasicColumnNames(),
            ])
            ->where('status', Statuses::ACTIVE->value)
            ->findOrFail($draftProductId);
    }

    public function getActiveProductByIdWithAllRelations(int $draftProductId): Product
    {
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $templateQueries = resolve(TemplateQueries::class);
        $attachedTemplateQueries = resolve(AttachedTemplateQueries::class);

        if (config('app.product_variant')) {
            return Product::query()
                ->select(
                    'id',
                    'company_id',
                    'name',
                    'description',
                    'compound_product_name',
                    'code',
                    'upc',
                    'ean',
                    'custom_sku',
                    'manufacturer_sku',
                    'type_id',
                    'retail_price',
                    'franchise_price_1',
                    'franchise_price_2',
                    'franchise_price_3',
                    'wholesale_price',
                    'company_or_tender_price',
                    'branch_price',
                    'minimum_price',
                    'original_capital_price',
                    'capital_price',
                    'staff_price',
                    'purchase_cost',
                    'online_price',
                    'is_temporarily_unavailable',
                    'status',
                    'is_available_in_pos',
                    'is_available_in_ecommerce',
                    'is_sold_as_single_item',
                    'created_at',
                    'updated_at',
                    'master_product_id',
                )
                ->with([
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getAllColumns(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                    'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                    'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'masterProduct.variantTemplate:' . $templateQueries->getColumnNamesForRelation(),
                    'masterProduct.attachedTemplates:' . $attachedTemplateQueries->getBasicColumnNames(),
                    'masterProduct.attachedTemplates.template:' . $templateQueries->getColumnNamesForRelation(),
                    'masterProduct.attachedTemplates.template.attributes' => $attributeQueries->getBasicColumnsForProduct(),
                ])
                ->where('status', Statuses::ACTIVE->value)
                ->findOrFail($draftProductId);
        }

        return Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'description',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'brand_id',
                'color_id',
                'size_id',
                'department_id',
                'sub_department_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'purchase_cost',
                'online_price',
                'created_by_id',
                'created_by_type',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'is_available_in_pos',
                'is_available_in_ecommerce',
                'is_sold_as_single_item',
                'created_at',
                'updated_at'
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'season:' . $seasonQueries->getBasicColumnNames(),
            ])
            ->where('status', Statuses::ACTIVE->value)
            ->findOrFail($draftProductId);
    }

    public function getDraftProductByIdsAndCompanyId(
        array $draftProductIds,
        int $companyId,
        int $status,
    ): Collection {
        if (config('app.product_variant')) {
            return Product::query()
                ->select(
                    'id',
                    'company_id',
                    'name',
                    'description',
                    'compound_product_name',
                    'code',
                    'upc',
                    'ean',
                    'custom_sku',
                    'manufacturer_sku',
                    'type_id',
                    'retail_price',
                    'franchise_price_1',
                    'franchise_price_2',
                    'franchise_price_3',
                    'wholesale_price',
                    'company_or_tender_price',
                    'branch_price',
                    'minimum_price',
                    'original_capital_price',
                    'capital_price',
                    'staff_price',
                    'purchase_cost',
                    'online_price',
                    'is_temporarily_unavailable',
                    'status',
                    'is_available_in_pos',
                    'is_available_in_ecommerce',
                    'created_at',
                    'updated_at',
                    'master_product_id',
                )
                ->where('company_id', $companyId)
                ->where('status', $status)
                ->whereIntegerInRaw('id', $draftProductIds)
                ->get();
        }

        return Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'description',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'brand_id',
                'color_id',
                'size_id',
                'department_id',
                'sub_department_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'purchase_cost',
                'online_price',
                'created_by_id',
                'created_by_type',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'is_available_in_pos',
                'is_available_in_ecommerce',
                'created_at',
                'updated_at'
            )
            ->where('company_id', $companyId)
            ->where('status', $status)
            ->whereIntegerInRaw('id', $draftProductIds)
            ->get();
    }

    public function markAsApproved(array $draftProductIds, int $companyId, User $user): void
    {
        $collectDraftProductIds = collect($draftProductIds);
        foreach ($collectDraftProductIds->chunk(100) as $draftProductIds) {
            $draftProducts = $this->getDraftProductByIdsAndCompanyId(
                $draftProductIds->toArray(),
                $companyId,
                Statuses::DRAFT->value
            );

            foreach ($draftProducts as $draftProduct) {
                $this->activeProduct($draftProduct);

                if (config('app.product_variant')) {
                    $masterProductQueries = resolve(MasterProductQueries::class);
                    $masterProductQueries->updateStatus($draftProduct->master_product_id, $companyId);
                }

                CreateDraftProductTransactionsJob::dispatch(
                    $draftProduct->id,
                    $companyId,
                    $user->id,
                    $user::class,
                    Statuses::ACTIVE->value
                )->onQueue('medium');

                $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
                $productCollectionProductQueries->removeByProductId($draftProduct->id, $companyId);
                ProductCollectionUpdateByProductJob::dispatch($draftProduct->id, $companyId)->onQueue('medium');
            }
        }
    }

    public function getIdAndNameByIds(array $productIds, int $companyId): Collection
    {
        return Product::select('id', 'compound_product_name as name')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $productIds)
            ->get();
    }

    public function getIdByBrandIds(array $brandIds, int $companyId): Collection
    {
        return Product::select('id')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('brand_id', $brandIds)
            ->get();
    }

    public function getIdByCategoryIds(array $categoryIds, int $companyId): Collection
    {
        $categoryQueries = resolve(CategoryQueries::class);

        return Product::select('id')
            ->whereHas('categories', $categoryQueries->filterByIds($categoryIds))
            ->where('company_id', $companyId)
            ->get();
    }

    public function getIdByStyleIds(array $styleIds, int $companyId): Collection
    {
        return Product::select('id')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('style_id', $styleIds)
            ->get();
    }

    public function getIdByDepartmentIds(array $departmentIds, int $companyId): Collection
    {
        return Product::select('id')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('department_id', $departmentIds)
            ->get();
    }

    public function getIdByProductCollectionIds(array $productCollectionIds, int $companyId): Collection
    {
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        return Product::select('id')
            ->whereHas(
                'productCollectionProducts',
                $productCollectionProductQueries->filterByProductCollectionIds($productCollectionIds)
            )
            ->where('company_id', $companyId)
            ->get();
    }

    public function getByIdWithOriginalCreatedAt(int $productId): ?Product
    {
        return Product::query()
            ->select('id', 'original_created_at')
            ->where('id', $productId)
            ->first();
    }

    public function uploadVerifiedImage(Product $product, string $verifiedImage): void
    {
        $product->addMediaFromBase64(base64_encode($verifiedImage), 'image/png')
            ->usingFileName($product->name.'_'. $product->upc . '-verified-product.png')
            ->toMediaCollection('social_share');
    }

    private function getProductAgeingReport(array $filterData, int $companyId): Builder
    {
        $inventoryUpdateClosure = function ($query) use ($filterData): void {
            $query->select('product_id', 'affected_by_type', DB::raw('MIN(happened_at) AS happened_at'))
                ->where(function ($query): void {
                    $query->where('affected_by_type', ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name)
                        ->orWhere('affected_by_type', ModelMapping::STOCK_TRANSFER_ITEM->name);
                })
                ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                })
                ->groupBy('product_id')
                ->groupBy('affected_by_type')
                ->orderBy('happened_at', 'asc');
        };

        $inventoryClosure = function ($query) use ($filterData): void {
            $query->select('product_id', 'location_id', DB::raw('(stock + reserved_stock) as stock'))
                ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                });
        };

        $commonCondition = function ($query, array $filterData, $inventoryUpdateQueries, $ageCategory): void {
            [$startDays, $endDays] = AgeCategories::getDays((int) $ageCategory);
            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                if ($endDays > 0) {
                    $query->where(
                        'products.created_at',
                        '>=',
                        CommonFunctions::addStartTime(now()->subDays($endDays)->format('Y-m-d'))
                    );
                }

                if ($startDays > 0) {
                    $query->where(
                        'products.created_at',
                        '<=',
                        CommonFunctions::addEndTime(now()->subDays($startDays)->format('Y-m-d'))
                    );
                }
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                $query->whereIntegerInRaw(
                    'products.id',
                    $inventoryUpdateQueries->filterByFirstGrnForProductAgeingReport($filterData, $startDays, $endDays)
                );
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                $query->whereIntegerInRaw(
                    'products.id',
                    $inventoryUpdateQueries->filterByFirstTransferInForProductAgeingReport(
                        $filterData,
                        $startDays,
                        $endDays
                    )
                );
            }
        };

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();

        return Product::with([
            'inventoryUpdates' => $inventoryUpdateClosure,
            'inventory' => $inventoryClosure,
        ])
            ->select(
                'sale_return_totals.total_quantity_returned',
                'sale_totals.total_quantity_sold',
                'sale_totals.last_selling_date',
                'products.id as id',
                'products.name as name',
                'products.upc as upc',
                'products.article_number as article_number',
                'products.size_id',
                'products.color_id',
                'products.created_at',
                'colors.name as color_name',
                'sizes.name as size_name',
            )
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->leftJoin('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->groupBy('sale_return_items.product_id'),
                'sale_return_totals',
                'sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                        DB::raw('MAX(sales.happened_at) as last_selling_date')
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->groupBy('sale_items.product_id'),
                'sale_totals',
                'sale_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
            ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id')
            ->where('products.company_id', $companyId)
            ->where('products.is_non_selling_item', false)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('products.article_number', $filterData['article_numbers']);
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
            })
            ->when($filterData['color_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when($filterData['size_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
            })
            ->when(
                array_key_exists(
                    'last_selling_date_range',
                    $filterData
                ) && [] !== $filterData['last_selling_date_range'],
                function ($query) use ($filterData): void {
                    $query->where(
                        'sale_totals.last_selling_date',
                        '>=',
                        CommonFunctions::addStartTime($filterData['last_selling_date_range'][0])
                    )
                        ->where(
                            'sale_totals.last_selling_date',
                            '<=',
                            CommonFunctions::addEndTime($filterData['last_selling_date_range'][1])
                        );
                }
            )
            ->when((int) $filterData['age_category_id'] > 0, function ($query) use (
                $filterData,
                $inventoryUpdateQueries,
                $commonCondition
            ): void {
                $commonCondition($query, $filterData, $inventoryUpdateQueries, $filterData['age_category_id']);
            })
            ->when((int) $filterData['age_category_id'] === 0, function ($query) use (
                $filterData,
                $inventoryUpdateQueries
            ): void {
                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                    $query->whereIn(
                        'products.id',
                        $inventoryUpdateQueries->filterByFirstGrnForProductAgeingReport($filterData, 0, 0)
                    );
                }

                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                    $query->whereIn(
                        'products.id',
                        $inventoryUpdateQueries->filterByFirstTransferInForProductAgeingReport($filterData, 0, 0)
                    );
                }
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    $query->orderBy('products.created_at', $filterData['sort_direction']);
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('sale_totals.last_selling_date', $filterData['sort_direction']);
                }

                if ('quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('sale_totals.total_quantity_sold', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getProductAgeingReportByMonthAndYear(array $filterData, int $companyId): Builder
    {
        $inventoryUpdateClosure = function ($query) use ($filterData): void {
            $query->select('product_id', 'affected_by_type', DB::raw('MIN(happened_at) AS happened_at'))
                ->where(function ($query): void {
                    $query->where('affected_by_type', ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name)
                        ->orWhere('affected_by_type', ModelMapping::STOCK_TRANSFER_ITEM->name);
                })
                ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                })
                ->groupBy('product_id')
                ->groupBy('affected_by_type')
                ->orderBy('happened_at', 'asc');
        };

        $inventoryClosure = function ($query) use ($filterData): void {
            $query->select('product_id', 'location_id', DB::raw('(stock + reserved_stock) as stock'))
                ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                });
        };

        $commonCondition = function ($query, array $filterData, $inventoryUpdateQueries, $ageCategory): void {
            [$startDays, $endDays] = AgeCategories::getDays((int) $ageCategory);
            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                if ($endDays > 0) {
                    $query->where(
                        'products.created_at',
                        '>=',
                        CommonFunctions::addStartTime(now()->subDays($endDays)->format('Y-m-d'))
                    );
                }

                if ($startDays > 0) {
                    $query->where(
                        'products.created_at',
                        '<=',
                        CommonFunctions::addEndTime(now()->subDays($startDays)->format('Y-m-d'))
                    );
                }
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                $query->whereIntegerInRaw(
                    'products.id',
                    $inventoryUpdateQueries->filterByFirstGrnForProductAgeingReport($filterData, $startDays, $endDays)
                );
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                $query->whereIntegerInRaw(
                    'products.id',
                    $inventoryUpdateQueries->filterByFirstTransferInForProductAgeingReport(
                        $filterData,
                        $startDays,
                        $endDays
                    )
                );
            }
        };

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();

        return Product::with([
            'inventoryUpdates' => $inventoryUpdateClosure,
            'inventory' => $inventoryClosure,
        ])
            ->select(
                DB::raw(
                    '(COALESCE(sale_totals.first_month_quantity_sold, 0) - COALESCE(sale_return_totals.first_month_quantity_returned, 0)) as first_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.second_month_quantity_sold, 0) - COALESCE(sale_return_totals.second_month_quantity_returned, 0)) as second_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.third_month_quantity_sold, 0) - COALESCE(sale_return_totals.third_month_quantity_returned, 0)) as third_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.fourth_month_quantity_sold, 0) - COALESCE(sale_return_totals.fourth_month_quantity_returned, 0)) as fourth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.fifth_month_quantity_sold, 0) - COALESCE(sale_return_totals.fifth_month_quantity_returned, 0)) as fifth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.sixth_month_quantity_sold, 0) - COALESCE(sale_return_totals.sixth_month_quantity_returned, 0)) as sixth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.seventh_month_quantity_sold, 0) - COALESCE(sale_return_totals.seventh_month_quantity_returned, 0)) as seventh_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.eighth_month_quantity_sold, 0) - COALESCE(sale_return_totals.eighth_month_quantity_returned, 0)) as eighth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.ninth_month_quantity_sold, 0) - COALESCE(sale_return_totals.ninth_month_quantity_returned, 0)) as ninth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.tenth_month_quantity_sold, 0) - COALESCE(sale_return_totals.tenth_month_quantity_returned, 0)) as tenth_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.eleventh_month_quantity_sold, 0) - COALESCE(sale_return_totals.eleventh_month_quantity_returned, 0)) as eleventh_month_quantity_sold'
                ),
                DB::raw(
                    '(COALESCE(sale_totals.twelfth_month_quantity_sold, 0) - COALESCE(sale_return_totals.twelfth_month_quantity_returned, 0)) as twelfth_month_quantity_sold'
                ),
                'sale_totals.last_selling_date',
                'products.id as id',
                'products.name as name',
                'products.upc as upc',
                'products.article_number as article_number',
                'products.size_id',
                'products.color_id',
                'products.created_at',
                'colors.name as color_name',
                'sizes.name as size_name',
            )
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN products.created_at AND DATE_ADD(products.created_at, INTERVAL 1 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS first_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 1 MONTH) AND DATE_ADD(products.created_at, INTERVAL 2 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS second_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 2 MONTH) AND DATE_ADD(products.created_at, INTERVAL 3 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS third_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 3 MONTH) AND DATE_ADD(products.created_at, INTERVAL 4 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS fourth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 4 MONTH) AND DATE_ADD(products.created_at, INTERVAL 5 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS fifth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 5 MONTH) AND DATE_ADD(products.created_at, INTERVAL 6 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS sixth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 6 MONTH) AND DATE_ADD(products.created_at, INTERVAL 7 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS seventh_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 7 MONTH) AND DATE_ADD(products.created_at, INTERVAL 8 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS eighth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 8 MONTH) AND DATE_ADD(products.created_at, INTERVAL 9 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS ninth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 9 MONTH) AND DATE_ADD(products.created_at, INTERVAL 10 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS tenth_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 10 MONTH) AND DATE_ADD(products.created_at, INTERVAL 11 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS eleventh_month_quantity_returned'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_returns.happened_at >= DATE_ADD(products.created_at, INTERVAL 11 MONTH) THEN sale_return_items.quantity ELSE 0 END) AS twelfth_month_quantity_returned'
                        ),
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->groupBy('sale_return_items.product_id'),
                'sale_return_totals',
                'sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        DB::raw('MAX(sales.happened_at) as last_selling_date'),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN products.created_at AND DATE_ADD(products.created_at, INTERVAL 1 MONTH) THEN sale_items.quantity ELSE 0 END) AS first_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 1 MONTH) AND DATE_ADD(products.created_at, INTERVAL 2 MONTH) THEN sale_items.quantity ELSE 0 END) AS second_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 2 MONTH) AND DATE_ADD(products.created_at, INTERVAL 3 MONTH) THEN sale_items.quantity ELSE 0 END) AS third_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 3 MONTH) AND DATE_ADD(products.created_at, INTERVAL 4 MONTH) THEN sale_items.quantity ELSE 0 END) AS fourth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 4 MONTH) AND DATE_ADD(products.created_at, INTERVAL 5 MONTH) THEN sale_items.quantity ELSE 0 END) AS fifth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 5 MONTH) AND DATE_ADD(products.created_at, INTERVAL 6 MONTH) THEN sale_items.quantity ELSE 0 END) AS sixth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 6 MONTH) AND DATE_ADD(products.created_at, INTERVAL 7 MONTH) THEN sale_items.quantity ELSE 0 END) AS seventh_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 7 MONTH) AND DATE_ADD(products.created_at, INTERVAL 8 MONTH) THEN sale_items.quantity ELSE 0 END) AS eighth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 8 MONTH) AND DATE_ADD(products.created_at, INTERVAL 9 MONTH) THEN sale_items.quantity ELSE 0 END) AS ninth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 9 MONTH) AND DATE_ADD(products.created_at, INTERVAL 10 MONTH) THEN sale_items.quantity ELSE 0 END) AS tenth_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(products.created_at, INTERVAL 10 MONTH) AND DATE_ADD(products.created_at, INTERVAL 11 MONTH) THEN sale_items.quantity ELSE 0 END) AS eleventh_month_quantity_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sales.happened_at >= DATE_ADD(products.created_at, INTERVAL 11 MONTH) THEN sale_items.quantity ELSE 0 END) AS twelfth_month_quantity_sold'
                        ),
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->groupBy('sale_items.product_id'),
                'sale_totals',
                'sale_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
            ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id')
            ->where('products.company_id', $companyId)
            ->where('products.is_non_selling_item', false)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('products.article_number', $filterData['article_numbers']);
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
            })
            ->when($filterData['color_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when($filterData['size_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
            })
            ->when(
                array_key_exists(
                    'last_selling_date_range',
                    $filterData
                ) && [] !== $filterData['last_selling_date_range'],
                function ($query) use ($filterData): void {
                    $query->where(
                        'sale_totals.last_selling_date',
                        '>=',
                        CommonFunctions::addStartTime($filterData['last_selling_date_range'][0])
                    )
                        ->where(
                            'sale_totals.last_selling_date',
                            '<=',
                            CommonFunctions::addEndTime($filterData['last_selling_date_range'][1])
                        );
                }
            )
            ->when((int) $filterData['age_category_id'] > 0, function ($query) use (
                $filterData,
                $inventoryUpdateQueries,
                $commonCondition
            ): void {
                $commonCondition($query, $filterData, $inventoryUpdateQueries, $filterData['age_category_id']);
            })
            ->when((int) $filterData['age_category_id'] === 0, function ($query) use (
                $filterData,
                $inventoryUpdateQueries
            ): void {
                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                    $query->whereIn(
                        'products.id',
                        $inventoryUpdateQueries->filterByFirstGrnForProductAgeingReport($filterData, 0, 0)
                    );
                }

                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                    $query->whereIn(
                        'products.id',
                        $inventoryUpdateQueries->filterByFirstTransferInForProductAgeingReport($filterData, 0, 0)
                    );
                }
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    $query->orderBy('products.created_at', $filterData['sort_direction']);
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('sale_totals.last_selling_date', $filterData['sort_direction']);
                }

                if ('first_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('first_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('second_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('second_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('third_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('third_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('fourth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fourth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('fifth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fifth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('sixth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('sixth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('seventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('seventh_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('eighth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eighth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('ninth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('ninth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('tenth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('tenth_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('eleventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eleventh_month_quantity_sold', $filterData['sort_direction']);
                }

                if ('twelfth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('twelfth_month_quantity_sold', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function applyCommonMainProductFilters(Builder $query, array $filterData): Builder
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);

        return $query
            ->when(
                array_key_exists('article_numbers', $filterData) && null !== $filterData['article_numbers'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->whereIn('article_number', $filterData['article_numbers']);
                    }
                }
            )
            ->when($filterData['category_ids'] && null !== $filterData['category_ids'], function ($query) use (
                $filterData,
                $categoryQueries
            ): void {
                if (config('app.product_variant')) {
                    $query->whereHas(
                        'master_products.categories',
                        $categoryQueries->filterByIds($filterData['category_ids'])
                    );
                } else {
                    $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                }
            })
            ->when($filterData['tag_ids'] && null !== $filterData['tag_ids'], function ($query) use (
                $filterData,
                $tagQueries
            ): void {
                if (config('app.product_variant')) {
                    $query->whereHas('master_products.tags', $tagQueries->filterByIds($filterData['tag_ids']));
                } else {
                    $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                }
            })
            ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                $filterData
            ): void {
                if (config('app.product_variant')) {
                    $query->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                }
            })
            ->when(
                config(
                    'app.product_variant'
                ) === false && $filterData['color_ids'] && null !== $filterData['color_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('color_id', $filterData['color_ids']);
                }
            )
            ->when(
                config('app.product_variant') === false && $filterData['size_ids'] && null !== $filterData['size_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('size_id', $filterData['size_ids']);
                }
            )
            ->when(
                $filterData['department_ids'] && null !== $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                }
            )
            ->when(
                config(
                    'app.product_variant'
                ) === false && $filterData['style_ids'] && null !== $filterData['style_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('style_id', $filterData['style_ids']);
                }
            )
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                function ($q) use ($filterData): void {
                    $q->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            );
    }

    private function productLists(array $filterData, int $companyId): Builder
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $styleQueries = new StyleQueries();
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $seasonQueries = new SeasonQueries();
        $departmentQueries = new DepartmentQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $adminQueries = resolve(AdminQueries::class);
        $draftProductTransactionQueries = resolve(DraftProductTransactionQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $selectedColumns = [
            'id',
            'name',
            'description',
            'code',
            'unit_of_measure_id',
            'season_id',
            'department_id',
            'sub_department_id',
            'brand_id',
            'upc',
            'verification_qr_code',
            'article_number',
            'retail_price',
            'status',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'is_temporarily_unavailable',
            'has_batch',
            'type_id',
            'is_non_selling_item',
            'is_non_inventory',
            'status',
            'staff_price',
            'purchase_cost',
            'online_price',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'created_at',
            'updated_at',
            'original_created_at',
            'vendor_id',
            'created_by_id',
            'created_by_type',
            'last_editor_by_id',
            'last_editor_by_type',
        ];
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, ['master_product_id']);
        } else {
            $selectedColumns = array_merge($selectedColumns, ['color_id', 'size_id', 'style_id']);
        }

        $relations = [
            'season:' . $seasonQueries->getBasicColumnNames(),
            'productChannelReference:' . $productChannelReferenceQueries->getBasicColumnNames(),
            'draftProductTransaction:' . $draftProductTransactionQueries->getBasicColumnNames(),
            'draftProductTransaction.approvedBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'lastEditorBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
            'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            'vendor:' . $vendorQueries->getBasicColumnNames(),
            'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                $morphTo->constrain([
                    Admin::class => $adminQueries->getEmployeeWithRelation(),
                ]);
            },
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'masterProduct.vendor:' . $vendorQueries->getBasicColumnNames(),
                'masterProduct.createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
            ]);
        } else {
            $relations = array_merge($relations, [
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ]);
        }

        return Product::query()
            ->select(...$selectedColumns)
            ->with($relations)
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries,
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $this->searchForList($query, $filterData, $brandQueries, $categoryQueries);
                });
            })
            ->whereNot('status', Statuses::DRAFT->value)
            ->where('company_id', $companyId)
            ->tap(fn ($query): Builder => $this->commonFilterQuery($query, $filterData))
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }
            });
    }

    private function updateCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->detach();
        $categoryIds = collect($categoryIds)->unique();
        foreach ($categoryIds as $key => $categoryId) {
            if ($categoryId) {
                $product->categories()->attach([
                    $categoryId => [
                        'sort_order' => $key,
                    ],
                ]);
            }
        }
    }

    private function updateTags(Product $product, ?array $tagIds): void
    {
        if (null !== $tagIds) {
            $product->tags()->sync($tagIds);
        }
    }

    private function updateSaleChannelsByUpc(Product $product, ?array $saleChannelIds): void
    {
        if (null !== $saleChannelIds) {
            $product->saleChannels()->sync($saleChannelIds);
        }
    }

    private function updateSaleChannels(Product $product, ProductData $productData): void
    {
        if (! array_key_exists('sale_channel_ids', $productData->all())) {
            return;
        }

        if (null === $productData->sale_channel_ids) {
            return;
        }

        $product->saleChannels()->sync($productData->sale_channel_ids);
    }

    private function uploadPhoto(Product $product, ProductData $productData): void
    {
        if (! $productData->images) {
            return;
        }

        foreach ($productData->images as $image) {
            if ($image instanceof UploadedFile) {
                $product->addMedia($image)->toMediaCollection('images');
                $this->setUpdatedAt($product);
            }
        }
    }

    private function uploadVideo(Product $product, ProductData $productData): void
    {
        if (! $productData->videos) {
            return;
        }

        foreach ($productData->videos as $video) {
            if ($video instanceof UploadedFile) {
                $product->addMedia($video)->toMediaCollection('videos');
                $this->setUpdatedAt($product);
            }
        }
    }

    private function uploadOtherImages(Product $product, ProductData $productData): void
    {
        if ($productData->thumbnail instanceof UploadedFile) {
            $product->addMedia($productData->thumbnail)->toMediaCollection('thumbnail');
            $this->setUpdatedAt($product);
        }
    }

    private function updateLoyaltyPointMembership(Product $product, ProductData $productData): void
    {
        $product->tiers()->delete();
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        if ($productData->tiers) {
            foreach ($productData->tiers as $loyaltyPointTier) {
                if (! $loyaltyPointTier['membership_id']) {
                    continue;
                }

                if (! $loyaltyPointTier['points']) {
                    continue;
                }

                $productLoyaltyPointQueries->addNew(
                    $product->id,
                    (int) $loyaltyPointTier['membership_id'],
                    (int) $loyaltyPointTier['points'],
                );
            }
        }
    }

    private function updateAssemblyProducts(Product $product, ProductData $productData): void
    {
        if ((int) $productData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
            $assemblyChildProductQueries->deleteAssemblyChildProduct($product);

            foreach ((array) $productData->assembly_child_products as $assemblyProduct) {
                $assemblyChildProductQueries->addNew([
                    'product_id' => $product->id,
                    'child_product_id' => $assemblyProduct['child_product_id'],
                    'units' => $assemblyProduct['units'],
                ]);
            }
        }
    }

    private function updateProductBox(Product $product, ProductData $productData): void
    {
        $boxProductQueries = resolve(BoxProductQueries::class);
        $boxProductQueries->deleteProductBox($product);

        if ($productData->boxes && (int) $productData->type_id === ProductTypes::REGULAR_PRODUCT->value || (int) $productData->type_id === ProductTypes::SERIAL_PRODUCT->value) {
            $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
            foreach ((array) $productData->boxes as $productBox) {
                $boxProduct = $boxProductQueries->addNew([
                    'product_id' => $product->id,
                    'package_type_id' => $productBox['package_type_id'],
                    'units' => $productBox['units'],
                    'retail_price' => $productBox['retail_price'],
                    'staff_price' => $productBox['staff_price'],
                ]);
                if (! array_key_exists('box_product_loyalty_points', $productBox)) {
                    continue;
                }

                if ((array) $productBox['box_product_loyalty_points'] === []) {
                    continue;
                }

                foreach ($productBox['box_product_loyalty_points'] as $boxProductLoyaltyPoint) {
                    $boxProductLoyaltyPointQueries->addNew([
                        'box_product_id' => $boxProduct->id,
                        'membership_id' => $boxProductLoyaltyPoint['membership_id'],
                        'points' => (int) $boxProductLoyaltyPoint['points'],
                    ]);
                }
            }
        }
    }

    public function getBasicColumnNamesForVariants(): string
    {
        return 'id,master_product_id,name,compound_product_name,code,description,upc,ean,manufacturer_sku,custom_sku,retail_price,wholesale_price,staff_price,minimum_price,purchase_cost,online_price,is_temporarily_unavailable,is_available_in_pos,is_available_in_ecommerce,is_sold_as_single_item';
    }

    public function refresh(Product $product): Product
    {
        $product->refresh();

        return $product;
    }

    public function updateProductPrices(int $productId, int $companyId, array $priceData, User $user): void
    {
        $product = $this->getProductByIdAndCompanyId($productId, $companyId);
        $priceData['last_editor_by_id'] = $user->id;
        $priceData['last_editor_by_type'] = ModelMapping::getCaseName($user::class);

        $product->update($priceData);
    }

    /**
     * @return mixed[]
     */
    private function getCompoundProductName(array $productDetails, int $companyId): array
    {
        $colorName = null;
        $sizeName = null;

        if (array_key_exists('color_id', $productDetails) && $productDetails['color_id']) {
            $colorQueries = resolve(ColorQueries::class);
            $color = $colorQueries->getById($productDetails['color_id'], $companyId);
            $colorName = $color->getName();
        }

        if (array_key_exists('size_id', $productDetails) && $productDetails['size_id']) {
            $sizeQueries = resolve(SizeQueries::class);
            $size = $sizeQueries->getById($productDetails['size_id'], $companyId);
            $sizeName = $size->getName();
        }

        $productDetails['compound_product_name'] = $productDetails['name'] . ' ' . $colorName . ' ' . $sizeName;

        return $productDetails;
    }

    private function getProductReport(array $filterData, int $companyId): QueryBuilder
    {
        $saleBuilder = DB::table('sale_items', 'sale_items')
            ->selectRaw('product_id, NULL AS sale_return_quantity, NULL AS sale_return_amount')
            ->selectRaw('SUM(sale_items.total_price_paid) as sale_amount')
            ->selectRaw('SUM(sale_items.quantity) as sale_quantity')
            ->selectRaw('counters.location_id')
            ->selectRaw('locations.name as location_name')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
            })
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
            })
            ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
            })
            ->when(
                isset($filterData['purchase_type']) && (int) $filterData['purchase_type'] === PurchaseType::FOC->value,
                function ($query): void {
                    $query->where('sale_items.total_price_paid', '<=', 0);
                }
            )
            ->when(
                isset($filterData['purchase_type']) && (int) $filterData['purchase_type'] === PurchaseType::PAID->value,
                function ($query): void {
                    $query->where('sale_items.total_price_paid', '>', 0);
                }
            )
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('sales.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->groupBy('product_id', 'counters.location_id');

        $sale = DB::table(DB::raw('(' . $saleBuilder->toRawSql() . ') AS sale_item_with_locations'))->select(
            'sale_item_with_locations.location_id',
            'sale_item_with_locations.location_name',
            'sale_item_with_locations.product_id',
            'sale_item_with_locations.sale_amount',
            'sale_item_with_locations.sale_quantity',
            DB::raw('NULL AS sale_return_amount'),
            DB::raw('NULL AS sale_return_quantity')
        );

        $saleReturnBuilder = DB::table('sale_return_items', 'sale_return_items')
            ->selectRaw('product_id, NULL AS sale_quantity, NULL AS sale_amount')
            ->selectRaw('SUM(sale_return_items.total_price_paid) as sale_return_amount')
            ->selectRaw('SUM(sale_return_items.quantity) as sale_return_quantity')
            ->selectRaw('counters.location_id')
            ->selectRaw('locations.name as location_name')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
            })
            ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                    ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
            })
            ->groupBy('product_id', 'counters.location_id');

        $saleReturn = DB::table(
            DB::raw('(' . $saleReturnBuilder->toRawSql() . ') AS sale_return_item_with_locations')
        )->select(
            'sale_return_item_with_locations.location_id',
            'sale_return_item_with_locations.location_name',
            'sale_return_item_with_locations.product_id',
            DB::raw('NULL AS sale_amount'),
            DB::raw('NULL AS sale_quantity'),
            'sale_return_item_with_locations.sale_return_amount',
            'sale_return_item_with_locations.sale_return_quantity'
        );

        $saleAndSaleReturnUnion = $sale->unionAll($saleReturn);

        $result = DB::table(function ($query) use ($saleAndSaleReturnUnion): void {
            $query
                ->select(
                    'product_id',
                    'location_name',
                    'location_id',
                    DB::raw('SUM(sale_quantity) as sum_sale_quantity'),
                    DB::raw('SUM(sale_return_quantity) as sum_sale_return_quantity'),
                    DB::raw('SUM(sale_amount) as sum_sale_amount'),
                    DB::raw('SUM(sale_return_amount) as sum_sale_return_amount')
                )
                ->from($saleAndSaleReturnUnion)
                ->groupBy('product_id', 'location_id');
        }, 'sale_and_return_product');

        return DB::table('products')
            ->join(DB::raw('(' . $result->toRawSql() . ') AS sale_product'), function ($join): void {
                $join->on('products.id', '=', 'sale_product.product_id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoinSub(
                DB::table('genuine_product_verifications')
                    ->join('sales', 'genuine_product_verifications.sale_id', '=', 'sales.id')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->select(
                        'genuine_product_verifications.sale_id as verification_id',
                        'genuine_product_verifications.product_id',
                        'counters.location_id as counter_location_id',
                        DB::raw('COUNT(genuine_product_verifications.sale_id) as verification_count')
                    )
                    ->where('genuine_product_verifications.is_genuine', true)
                    ->groupBy('genuine_product_verifications.product_id', 'counter_location_id'),
                'product_verifications',
                function ($join): void {
                    $join->on('product_verifications.product_id', '=', 'products.id')
                        ->on('product_verifications.counter_location_id', '=', 'sale_product.location_id');
                }
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.master_product_id',
                    'master_products.article_number',
                    'products.upc',
                    'brands.name as brand_name',
                    'departments.name as department_name',
                    'products.sub_department_id',
                    'unit_of_measures.name as unit_of_measure_name',
                    'category_master_product.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    'location_name',
                    'location_id',
                    'sum_sale_quantity',
                    'sum_sale_return_quantity',
                    'sum_sale_amount',
                    'sum_sale_return_amount',
                    DB::raw('COALESCE(product_verifications.verification_count, 0) as verification_count'),
                    'product_verifications.verification_id',
                    DB::raw("
                            CONCAT('[', GROUP_CONCAT(
                                JSON_OBJECT('attribute_name', attributes.name, 'attribute_value', product_variant_values.value)
                                ORDER BY attributes.id SEPARATOR ','
                            ), ']') AS product_variants
                        ")
                )
                ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
                ->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id')
                ->groupBy('products.id');
            }, function ($query): void {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.article_number',
                    'products.upc',
                    'brands.name as brand_name',
                    'colors.name as color_name',
                    'sizes.name as size_name',
                    'departments.name as department_name',
                    'products.sub_department_id',
                    'seasons.name as season_name',
                    'unit_of_measures.name as unit_of_measure_name',
                    'product_category.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    'location_name',
                    'location_id',
                    'sum_sale_quantity',
                    'sum_sale_return_quantity',
                    'sum_sale_amount',
                    'sum_sale_return_amount',
                    DB::raw('COALESCE(product_verifications.verification_count, 0) as verification_count'),
                    'product_verifications.verification_id'
                );
            })
            ->where('products.company_id', $companyId)
            ->where('products.is_non_selling_item', false)
            ->whereNull('products.deleted_at')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_master_product')
                        ->select(
                            'category_master_product.master_product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_master_product.category_id')
                        ->groupBy('category_master_product.master_product_id'),
                    'category_master_product',
                    'category_master_product.master_product_id',
                    '=',
                    'master_products.id'
                );
            }, function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_product')
                        ->select(
                            'category_product.product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
                        ->groupBy('category_product.product_id'),
                    'product_category',
                    'product_category.product_id',
                    '=',
                    'products.id'
                );
            })
            ->leftJoinSub(
                DB::table('product_tag')
                    ->select('product_tag.product_id', DB::raw('GROUP_CONCAT(tags.name) as tag_names'))
                    ->leftJoin('tags', 'tags.id', '=', 'product_tag.tag_id')
                    ->groupBy('product_tag.product_id'),
                'products_tags',
                'products_tags.product_id',
                '=',
                'products.id'
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpb', 'products.master_product_id', '=', 'mpb.id')
                    ->leftJoin('brands', 'mpb.brand_id', '=', 'brands.id');
            }, function ($query): void {
                $query->leftJoin('brands', 'products.brand_id', '=', 'brands.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('colors', 'products.color_id', '=', 'colors.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('seasons', 'products.season_id', '=', 'seasons.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpd', 'products.master_product_id', '=', 'mpd.id')
                    ->leftJoin('departments', 'mpd.department_id', '=', 'departments.id');
            }, function ($query): void {
                $query->leftJoin('departments', 'products.department_id', '=', 'departments.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpu', 'products.master_product_id', '=', 'mpu.id')
                    ->leftJoin('unit_of_measures', 'mpu.unit_of_measure_id', '=', 'unit_of_measures.id');
            }, function ($query): void {
                $query->leftJoin('unit_of_measures', 'products.unit_of_measure_id', '=', 'unit_of_measures.id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when(! empty($filterData['article_numbers']), function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpa', 'products.master_product_id', '=', 'mpa.id')
                        ->whereIn('mpa.article_number', $filterData['article_numbers']);
                } else {
                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                }
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpbi', 'products.master_product_id', '=', 'mpbi.id')
                        ->whereIntegerInRaw('mpbi.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                }
            })
            ->when(config('app.product_variant') === false && $filterData['color_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when(config('app.product_variant') === false && $filterData['size_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                function ($q) use ($filterData): void {
                    $q->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpdi', 'products.master_product_id', '=', 'mpdi.id')
                        ->whereIntegerInRaw('products.mpdi.department_id', $filterData['department_ids']);
                } else {
                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                }
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'products.master_products.id IN (select master_product_id from category_master_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from category_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                }
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_tag where tag_id in (' . implode(
                        ',',
                        $filterData['tag_ids']
                    ) . '))'
                );
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_collection_products where product_collection_id =' . $filterData['product_collection_id'] . ')'
                );
            })
            ->where(function ($query): void {
                $query->whereNotNull('sum_sale_quantity')
                    ->orWhereNotNull('sum_sale_return_quantity')
                    ->orWhereNotNull('sum_sale_amount')
                    ->orWhereNotNull('sum_sale_return_amount');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('units_sold' === $filterData['sort_by']) {
                    $query->orderBy('sum_sale_quantity', $filterData['sort_direction']);
                }

                if ('units_returned' === $filterData['sort_by']) {
                    $query->orderBy('sum_sale_return_quantity', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getConsignmentReport(array $filterData, int $companyId): Builder
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = ['saleItems:' . $saleItemQueries->getColumnNamesForPromoterCommissionReport()];

        if (config('app.product_variant')) {
            $relations = array_merge(
                $relations,
                [
                    'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                    'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    'masterProduct.vendor:' . $vendorQueries->getBasicColumnNamesForConsignmentReport(),
                ]
            );
        } else {
            $relations = array_merge(
                $relations,
                [
                    'color:' . $colorQueries->getBasicColumnNames(),
                    'size:' . $sizeQueries->getBasicColumnNames(),
                    'brand:' . $brandQueries->getBasicColumnNames(),
                    'categories:' . $categoryQueries->getBasicColumnNames(),
                    'vendor:' . $vendorQueries->getBasicColumnNamesForConsignmentReport(),
                ]
            );
        }

        return Product::select(
            'id',
            'color_id',
            'size_id',
            'vendor_id',
            'brand_id',
            'upc',
            'article_number',
            'name',
            'retail_price',
            'master_product_id'
        )
            ->with($relations)
            ->where('company_id', $companyId)
            ->onlyActive()
            ->whereNotNull('vendor_id')
            ->whereHas('vendor', function ($query): void {
                $query->select('id')
                    ->where('is_consignment', true);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereHas('saleItems', function ($query) use ($filterData): void {
                    $query->select('id', 'sale_id')
                        ->whereHas('sale', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->where('happened_at', '>=', $filterData['date_range'][0])
                                ->where('happened_at', '<=', $filterData['date_range'][1]);
                        });
                });
            });
    }

    private function getProfitAndLossReport(array $filterData, int $companyId): QueryBuilder
    {
        return DB::table('products')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->select(
                    'sale_return_totals.total_quantity_returned as total_quantity_returned',
                    'sale_return_totals.total_returned_amount as total_returned_amount',
                    'sale_totals.total_quantity_sold as total_quantity_sold',
                    'sale_totals.total_amount_sold as total_amount_sold',
                    DB::raw('SUM(total_quantity_sold * products.purchase_cost) as total_purchase_cost'),
                    'products.id as id',
                    'products.name as name',
                    'products.upc as upc',
                    'products.master_product_id',
                    'master_products.article_number  as article_number',
                    'products.sub_department_id',
                    'brands.name as brand_name',
                    'departments.name as department_name',
                    'unit_of_measures.name as unit_of_measure_name',
                    'category_master_product.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    'sale_return_totals.location_name',
                    'sale_totals.location_name as location',
                    'sale_totals.location_id as location_id',
                    'sale_return_totals.location_id as return_location_id',
                    DB::raw("
                        CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT('attribute_name', attributes.name, 'attribute_value', product_variant_values.value)
                            ORDER BY attributes.id SEPARATOR ','
                        ), ']') AS product_variants
                    ")
                )
                    ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
                    ->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id')
                    ->groupBy('products.id');
            }, function ($query): void {
                $query->select(
                    'sale_return_totals.total_quantity_returned as total_quantity_returned',
                    'sale_return_totals.total_returned_amount as total_returned_amount',
                    'sale_totals.total_quantity_sold as total_quantity_sold',
                    'sale_totals.total_amount_sold as total_amount_sold',
                    DB::raw('SUM(total_quantity_sold * products.purchase_cost) as total_purchase_cost'),
                    'products.id as id',
                    'products.name as name',
                    'products.upc as upc',
                    'products.article_number as article_number',
                    'products.brand_id',
                    'products.season_id',
                    'products.department_id',
                    'products.size_id',
                    'products.color_id',
                    'products.sub_department_id',
                    'brands.name as brand_name',
                    'colors.name as color_name',
                    'sizes.name as size_name',
                    'departments.name as department_name',
                    'seasons.name as season_name',
                    'unit_of_measures.name as unit_of_measure_name',
                    'product_category.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    DB::raw('NULL AS location_name'),
                    'sale_totals.location_name as location',
                    'sale_totals.location_id as location_id',
                    'sale_return_totals.location_id as return_location_id',
                );
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->where('master_products.is_non_selling_item', false);
            }, function ($query): void {
                $query->where('products.is_non_selling_item', false);
            })
            ->where('products.status', Statuses::ACTIVE->value)
            ->whereNull('products.deleted_at')
            ->leftJoinSub(
                DB::table('sale_return_items')
                    ->select(
                        'sale_return_items.product_id',
                        'locations.name as location_name',
                        'locations.id as location_id',
                        DB::raw('SUM(sale_return_items.quantity) as total_quantity_returned'),
                        DB::raw('SUM(sale_return_items.total_price_paid) as total_returned_amount')
                    )
                    ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                    })
                    ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sale_returns.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sale_returns.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->groupBy('counters.location_id', 'sale_return_items.product_id'),
                'sale_return_totals',
                'sale_return_totals.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_items')
                    ->select(
                        'sale_items.product_id',
                        'locations.name as location_name',
                        'locations.id as location_id',
                        DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                        DB::raw('SUM(sale_items.total_price_paid) as total_amount_sold')
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                    })
                    ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sales.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->groupBy('counters.location_id', 'sale_items.product_id'),
                'sale_totals',
                'sale_totals.product_id',
                '=',
                'products.id'
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_master_product')
                        ->select(
                            'category_master_product.master_product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_master_product.category_id')
                        ->groupBy('category_master_product.master_product_id'),
                    'category_master_product',
                    'category_master_product.master_product_id',
                    '=',
                    'master_products.id'
                );
            }, function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_product')
                        ->select(
                            'category_product.product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
                        ->groupBy('category_product.product_id'),
                    'product_category',
                    'product_category.product_id',
                    '=',
                    'products.id'
                );
            })
            ->leftJoinSub(
                DB::table('product_tag')
                    ->select('product_tag.product_id', DB::raw('GROUP_CONCAT(tags.name) as tag_names'))
                    ->leftJoin('tags', 'tags.id', '=', 'product_tag.tag_id')
                    ->groupBy('product_tag.product_id'),
                'products_tags',
                'products_tags.product_id',
                '=',
                'products.id'
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpb', 'products.master_product_id', '=', 'mpb.id')
                    ->leftJoin('brands', 'mpb.brand_id', '=', 'brands.id');
            }, function ($query): void {
                $query->leftJoin('brands', 'products.brand_id', '=', 'brands.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('colors', 'products.color_id', '=', 'colors.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('seasons', 'products.season_id', '=', 'seasons.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpd', 'products.master_product_id', '=', 'mpd.id')
                    ->leftJoin('departments', 'mpd.department_id', '=', 'departments.id');
            }, function ($query): void {
                $query->leftJoin('departments', 'products.department_id', '=', 'departments.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpu', 'products.master_product_id', '=', 'mpu.id')
                    ->leftJoin('unit_of_measures', 'mpu.unit_of_measure_id', '=', 'unit_of_measures.id');
            }, function ($query): void {
                $query->leftJoin('unit_of_measures', 'products.unit_of_measure_id', '=', 'unit_of_measures.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpc', 'products.master_product_id', '=', 'mpc.id')
                    ->leftJoin('category_master_product as cmp', 'cmp.master_product_id', '=', 'mpc.id');
            }, function ($query): void {
                $query->leftJoin('category_product', 'category_product.product_id', '=', 'products.id');
            })
            ->leftJoin('product_tag', 'product_tag.product_id', '=', 'products.id')
            ->where('products.company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when(! empty($filterData['article_numbers']), function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpa', 'products.master_product_id', '=', 'mpa.id')
                        ->whereIn('mpa.article_number', $filterData['article_numbers']);
                } else {
                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                }
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpbi', 'products.master_product_id', '=', 'mpbi.id')
                        ->whereIntegerInRaw('mpbi.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                }
            })
            ->when(config('app.product_variant') === false && $filterData['color_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when(config('app.product_variant') === false && $filterData['size_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                function ($q) use ($filterData): void {
                    $q->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpdi', 'products.master_product_id', '=', 'mpdi.id')
                        ->whereIntegerInRaw('products.mpdi.department_id', $filterData['department_ids']);
                } else {
                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                }
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'products.master_products.id IN (select master_product_id from category_master_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from category_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                }
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('product_tag.tag_id', $filterData['tag_ids']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_collection_products where product_collection_id =' . $filterData['product_collection_id'] . ')'
                );
            })
            ->where(function ($query): void {
                $query->whereNotNull('sale_return_totals.total_quantity_returned')
                    ->orWhereNotNull('sale_return_totals.total_returned_amount')
                    ->orWhereNotNull('sale_totals.total_quantity_sold')
                    ->orWhereNotNull('sale_totals.total_amount_sold');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('units_sold' === $filterData['sort_by']) {
                    $query->orderBy('total_quantity_sold', $filterData['sort_direction']);
                }

                if ('units_returned' === $filterData['sort_by']) {
                    $query->orderBy('total_quantity_returned', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->groupBy('products.id', 'sale_return_totals.location_id', 'sale_totals.location_id');
    }

    private function generateFilteredCacheKeyWithExpiration(
        array $filterData,
        string $functionName,
        int $companyId,
    ): array {
        $kebabFunctionName = Str::kebab($functionName);

        $string = '';
        foreach ($filterData as $value) {
            if (is_array($value)) {
                $string .= implode('', $value);
            } elseif ('' !== $value) {
                $string .= $value;
            }
        }

        return [$kebabFunctionName . '-' . $string . '-' . $companyId, now()->addMinutes(20)];
    }

    private function setAvailableInPosAndAvailableInEcommerce(array $productDetails): array
    {
        if (true === $productDetails['is_non_selling_item']) {
            $productDetails['is_available_in_pos'] = false;
            $productDetails['is_available_in_ecommerce'] = false;
        }

        return $productDetails;
    }

    private function activeProduct(Product $product): void
    {
        $product->status = Statuses::ACTIVE->value;
        $product->save();
    }

    public function getBasicColumnsForProductAgeing(): string
    {
        return 'id,article_number,upc,color_id,size_id,name,created_at';
    }

    public function getYesterdayCreatedProductsIds(): array
    {
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');

        return Product::query()
            ->select('id', 'created_at', 'original_created_at')
            ->where('created_at', $yesterdayDate)
            ->get()
            ->toArray();
    }

    public function getAllActiveProductsIds(): array
    {
        return Product::query()
            ->select('id', 'created_at', 'original_created_at')
            ->get()
            ->toArray();
    }

    public function getUpcAndIsAvailableInEcommerceByUpc(string $productUpc): ?Product
    {
        return Product::select('id', 'upc', 'is_available_in_ecommerce')
            ->onlyActive()
            ->whereCaseSensitive('upc', $productUpc)
            ->where('is_non_inventory', false)
            ->first();
    }

    public function updateIsAvailableInEcommerce(Product $product): void
    {
        $product->is_available_in_ecommerce = true;
        $product->save();
    }

    private function commonQueryForEditProduct(int $companyId, int $productId, int $status): Product
    {
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $templateQueries = resolve(TemplateQueries::class);
        $attachedTemplateQueries = resolve(AttachedTemplateQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return Product::query()->select(...$this->getAllBasicColumns())
            ->with([
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
                'attachedTemplates:' . $attachedTemplateQueries->getBasicColumnNames(),
                'attachedTemplates.template:' . $templateQueries->getColumnNamesForRelation(),
                'attachedTemplates.template.attributes:' . $attributeQueries->getColumnNamesForRelation(),
                'attachedTemplates.template.attributes.customFieldValue' => function ($query) use (
                    $productId
                ): void {
                    $query->select('attribute_id', 'value')
                        ->where('model_id', $productId);
                },
                ...(config('app.product_variant')
                    ? ['masterProduct:' . $masterProductQueries->getBasicColumnNames()]
                    : []
                ),
            ])
            ->where('company_id', $companyId)
            ->where('status', $status)
            ->findOrFail($productId);
    }

    private function commonSelectedColumnsForDraftProducts(): Builder
    {
        $commonColumns = [
            'id',
            'name',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'type_id',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'purchase_cost',
            'is_temporarily_unavailable',
            'has_batch',
            'status',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'online_price',
            'created_at',
            'updated_at',
            'code',
            'upc',
        ];

        $additionalColumns = config('app.product_variant')
            ? ['master_product_id']
            : [
                'unit_of_measure_id',
                'season_id',
                'department_id',
                'sub_department_id',
                'color_id',
                'size_id',
                'brand_id',
                'style_id',
                'article_number',
                'is_non_inventory',
                'is_non_selling_item',
                'created_by_id',
                'created_by_type',
            ];

        return Product::select(array_merge($commonColumns, $additionalColumns));
    }

    private function applyConditionForMatchProduct(Builder $query, Product $draftProduct): Builder
    {
        if (config('app.product_variant')) {
            $productVariantValues = $draftProduct->productVariantValues;
            $values = $productVariantValues->pluck('value')->toArray();

            $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
            $matchingRecords = $productVariantValueQueries->getProductsWithMatchingVariants($draftProduct->id, $values);

            $productIds = $matchingRecords->pluck('product_id')->toArray();

            return $query->whereHas('productVariantValues', function ($query) use ($productIds): void {
                $query->whereIntegerInRaw('product_id', $productIds);
            })
                ->whereNot('id', $draftProduct->id)
                ->where('master_product_id', $draftProduct->master_product_id)
                ->where('ean', $draftProduct->ean)
                ->where('custom_sku', $draftProduct->custom_sku)
                ->where('manufacturer_sku', $draftProduct->manufacturer_sku)
                ->where('type_id', $draftProduct->type_id)
                ->where('retail_price', $draftProduct->retail_price)
                ->where('franchise_price_1', $draftProduct->franchise_price_1)
                ->where('franchise_price_2', $draftProduct->franchise_price_2)
                ->where('franchise_price_3', $draftProduct->franchise_price_3)
                ->where('wholesale_price', $draftProduct->wholesale_price)
                ->where('company_or_tender_price', $draftProduct->company_or_tender_price)
                ->where('branch_price', $draftProduct->branch_price)
                ->where('minimum_price', $draftProduct->minimum_price)
                ->where('original_capital_price', $draftProduct->original_capital_price)
                ->where('capital_price', $draftProduct->capital_price)
                ->where('staff_price', $draftProduct->staff_price)
                ->where('purchase_cost', $draftProduct->purchase_cost)
                ->where('is_temporarily_unavailable', $draftProduct->is_temporarily_unavailable)
                ->where('is_available_in_pos', $draftProduct->is_available_in_pos)
                ->where('is_available_in_ecommerce', $draftProduct->is_available_in_ecommerce)
                ->where('online_price', $draftProduct->online_price);
        }

        $categoryQueries = resolve(CategoryQueries::class);
        /** @var Collection $categories */
        $categories = $draftProduct->categories;

        return $query->whereHas('categories', $categoryQueries->filterByIds($categories->pluck('id')->toArray()))
            ->whereNot('id', $draftProduct->id)
            ->where('unit_of_measure_id', $draftProduct->unit_of_measure_id)
            ->where('season_id', $draftProduct->season_id)
            ->where('department_id', $draftProduct->department_id)
            ->where('sub_department_id', $draftProduct->sub_department_id)
            ->where('color_id', $draftProduct->color_id)
            ->where('size_id', $draftProduct->size_id)
            ->where('brand_id', $draftProduct->brand_id)
            ->where('style_id', $draftProduct->style_id)
            ->where('ean', $draftProduct->ean)
            ->where('custom_sku', $draftProduct->custom_sku)
            ->where('manufacturer_sku', $draftProduct->manufacturer_sku)
            ->where('article_number', $draftProduct->article_number)
            ->where('type_id', $draftProduct->type_id)
            ->where('retail_price', $draftProduct->retail_price)
            ->where('franchise_price_1', $draftProduct->franchise_price_1)
            ->where('franchise_price_2', $draftProduct->franchise_price_2)
            ->where('franchise_price_3', $draftProduct->franchise_price_3)
            ->where('wholesale_price', $draftProduct->wholesale_price)
            ->where('company_or_tender_price', $draftProduct->company_or_tender_price)
            ->where('branch_price', $draftProduct->branch_price)
            ->where('minimum_price', $draftProduct->minimum_price)
            ->where('original_capital_price', $draftProduct->original_capital_price)
            ->where('capital_price', $draftProduct->capital_price)
            ->where('staff_price', $draftProduct->staff_price)
            ->where('purchase_cost', $draftProduct->purchase_cost)
            ->where('is_temporarily_unavailable', $draftProduct->is_temporarily_unavailable)
            ->where('has_batch', $draftProduct->has_batch)
            ->where('is_non_inventory', $draftProduct->is_non_inventory)
            ->where('is_non_selling_item', $draftProduct->is_non_selling_item)
            ->where('is_available_in_pos', $draftProduct->is_available_in_pos)
            ->where('is_available_in_ecommerce', $draftProduct->is_available_in_ecommerce)
            ->where('online_price', $draftProduct->online_price);
    }

    public function accumulatedSaleThroughSalesAndReturnsDataByProductUpcForCustomReport(
        array $filterData,
        int $companyId,
    ): Collection {
        $mediaQueries = resolve(MediaQueries::class);
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $soldLogic = 'CASE
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN COALESCE(upc_sale_total.units_sold, 0) + COALESCE(upc_sale_total.foc_units_sold, 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(upc_sale_total.units_sold, 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(upc_sale_total.foc_units_sold, 0)
            ELSE 0
        END';

        $relations = ['media:' . $mediaQueries->getBasicColumnNames()];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        $selectedColumns = [];

        if (config('app.product_variant')) {
            $selectedColumns = ['products.id', 'products.master_product_id'];
        } else {
            $selectedColumns = [
                'products.article_number as article_number',
                'products.color_id',
                'products.size_id',
            ];
        }

        return Product::query()
            ->with($relations)
            ->select([
                'products.id',
                'products.name',
                'retail_price as price',
                'upc',
                ...$selectedColumns,
                'product_inventory_update.received',
                'upc_return_total.return_units as returned',
                DB::raw($soldLogic . ' AS sold'),
                DB::raw("
                        COALESCE(product_inventory_update.received, 0) - ({$soldLogic} - COALESCE(upc_return_total.return_units, 0)) as balance
                    "),
                DB::raw("
                        CASE
                            WHEN (COALESCE(product_inventory_update.received, 0) = 0) THEN 0
                            ELSE (
                                ({$soldLogic} - COALESCE(upc_return_total.return_units, 0))
                                * 100 / COALESCE(product_inventory_update.received, 0)
                            )
                        END as sell_through
                    "),
            ]
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->whereHas('masterProduct', function ($query): void {
                    $query->where('is_non_selling_item', false)
                    ->whereNull('deleted_at');
                });
            }, function ($query): void {
                $query->where('products.is_non_selling_item', false)
                    ->whereNull('deleted_at');
            })
            ->where('products.company_id', $companyId)
            ->when(config('app.product_variant'), function ($query): void {
                $query->join(
                    'category_master_product',
                    'products.master_product_id',
                    '=',
                    'category_master_product.master_product_id'
                );
            }, function ($query): void {
                $query->join('category_product', 'products.id', '=', 'category_product.product_id');
            })
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct.tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    } else {
                        $query->whereHas('tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    }
                }
            )
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('productVariantValues', function ($query) use ($filterData): void {
                        $query->select('id')->whereIn('value', $filterData['attributes']);
                    });
                })
            ->where($this->productsFilterForSaleThrough($filterData))
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->where(function ($query) use ($inventoryUpdateQueries, $filterData): void {
                        $query->where(
                            $inventoryUpdateQueries->filterSellThroughDataBasedOnAffectedType($filterData)
                        );
                    })
                    ->whereNotIn('inventory_updates.affected_by_type', [
                        ModelMapping::SALE_ITEM->name,
                        ModelMapping::SALE_RETURN_ITEM->name,
                        ModelMapping::VOID_SALE->name,
                        ModelMapping::ORDER_ITEM->name,
                        ModelMapping::ORDER_RETURN_ITEM->name,
                        ModelMapping::ORDER->name,
                    ])
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->where('inventory_updates.happened_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.quantity) as received')
                    )
                    ->groupBy('product_id'),
                'product_inventory_update',
                'product_inventory_update.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                        ),
                    )
                    ->groupBy('product_id'),
                'upc_sale_total',
                'upc_sale_total.product_id',
                '=',
                'products.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->select('products.id as product_id', DB::raw('SUM(sale_return_items.quantity) as return_units'))
                    ->groupBy('product_id'),
                'upc_return_total',
                'upc_return_total.product_id',
                '=',
                'products.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('upc_sale_total.units_sold')
                    ->orWhereNotNull('product_inventory_update.received')
                    ->orWhereNotNull('upc_return_total.return_units');
            })
            ->get();
    }

    public function accumulatedSaleThroughInventoryDataByProductUpcForCustomReportForStoreWise(
        array $filterData,
        int $companyId,
    ): Collection {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [];
        if (config('app.product_variant')) {
            $relations = [
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ];
        }

        $selectedColumns = [];
        if (config('app.product_variant')) {
            $selectedColumns = ['products.master_product_id', 'master_products.article_number as article_number'];
        } else {
            $selectedColumns = ['products.article_number', 'products.color_id', 'products.size_id'];
        }

        return Product::query()
            ->with($relations)
            ->select(array_merge([
                'products.id',
                'products.name',
                'products.retail_price as price',
                'products.upc',
                'product_inventory_update.received',
                'product_inventory_update.location_name as location_name',
                DB::raw('0 as sold'),
                DB::raw('0 as returned'),
            ], $selectedColumns))
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where(
                config('app.product_variant') ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item',
                false
            )
            ->whereNull(config('app.product_variant') ? 'master_products.deleted_at' : 'products.deleted_at')
            ->where('products.company_id', $companyId)
            ->when(config('app.product_variant'), function ($query): void {
                $query->join(
                    'category_master_product',
                    'products.master_product_id',
                    '=',
                    'category_master_product.master_product_id'
                );
            }, function ($query): void {
                $query->join('category_product', 'products.id', '=', 'category_product.product_id');
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('productVariantValues', function ($query) use ($filterData): void {
                        $query->select('id')->whereIn('value', $filterData['attributes']);
                    });
                }
            )
            ->where($this->productsFilterForSaleThrough($filterData))
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct.tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    } else {
                        $query->whereHas('tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    }
                }
            )
            ->leftJoinSub(
                DB::table('inventory_updates')
                    ->join('products', 'products.id', '=', 'inventory_updates.product_id')
                    ->leftJoin('locations', function ($join): void {
                        $join->on('locations.id', '=', 'inventory_updates.location_id');
                    })
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->where(function ($query) use ($inventoryUpdateQueries, $filterData): void {
                        $query->where(
                            $inventoryUpdateQueries->filterSellThroughDataBasedOnAffectedType($filterData)
                        );
                    })
                    ->whereNotIn('inventory_updates.affected_by_type', [
                        ModelMapping::SALE_ITEM->name,
                        ModelMapping::SALE_RETURN_ITEM->name,
                        ModelMapping::VOID_SALE->name,
                        ModelMapping::ORDER_ITEM->name,
                        ModelMapping::ORDER_RETURN_ITEM->name,
                        ModelMapping::ORDER->name,
                    ])
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query
                                ->whereIntegerInRaw('inventory_updates.location_id', $filterData['location_ids']);
                        }
                    )
                    ->where('inventory_updates.happened_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->select(
                        'inventory_updates.product_id as product_id',
                        DB::raw('SUM(inventory_updates.quantity) as received'),
                        DB::raw('COALESCE(locations.name) as location_name')
                    )
                    ->groupBy(['product_id', 'location_name']),
                'product_inventory_update',
                'product_inventory_update.product_id',
                '=',
                'products.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('product_inventory_update.received');
            })
            ->get();
    }

    private function createOrUpdateCustomFieldValues(Product $product, ProductData $productData): void
    {
        $attachedTemplateQueries = resolve(AttachedTemplateQueries::class);
        $customFieldValueQueries = resolve(CustomFieldValueQueries::class);
        $this->clearOldCustomFieldValueRecords($attachedTemplateQueries, $customFieldValueQueries, $product);

        $customFieldValuesData = $productData->custom_field_values ?? [];

        foreach ($customFieldValuesData as $template) {
            foreach ($template['attributes'] as $attribute) {
                $value = is_array($attribute['selected_value']) ? json_encode(
                    $attribute['selected_value']
                ) : $attribute['selected_value'];

                $customFieldValueQueries->addNew([
                    'model_type' => ModelMapping::PRODUCT->name,
                    'model_id' => $product->id,
                    'template_id' => $template['id'],
                    'attribute_id' => $attribute['id'],
                    'value' => $value,
                ]);
            }
        }

        $attachedTemplatesData = $productData->attached_templates ?? [];

        foreach ($attachedTemplatesData as $attachedTemplateData) {
            $attachedTemplateQueries->addNew([
                ...$attachedTemplateData,
                'model_type' => ModelMapping::PRODUCT->name,
                'model_id' => $product->id,
            ]);
        }
    }

    private function clearOldCustomFieldValueRecords(
        AttachedTemplateQueries $attachedTemplateQueries,
        CustomFieldValueQueries $customFieldValueQueries,
        Product $product,
    ): void {
        $attachedTemplateQueries->delete($product);
        $customFieldValueQueries->delete($product);
    }

    public function getIdANdNameColumns(): string
    {
        return 'id,name';
    }

    public function accumulatedSaleThroughSalesDataByProductUpcForCustomReportForStoreWise(
        array $filterData,
        int $companyId,
    ): Collection {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $soldLogic = 'CASE
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . ' THEN COALESCE(upc_sale_total.units_sold, 0) + COALESCE(upc_sale_total.foc_units_sold, 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . ' THEN COALESCE(upc_sale_total.units_sold, 0)
            WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . ' THEN COALESCE(upc_sale_total.foc_units_sold, 0)
            ELSE 0
        END';

        $relations = [];
        if (config('app.product_variant')) {
            $relations = [
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ];
        }

        $selectedColumns = [];
        if (config('app.product_variant')) {
            $selectedColumns = ['products.master_product_id', 'master_products.article_number as article_number'];
        } else {
            $selectedColumns = ['products.article_number', 'products.color_id', 'products.size_id'];
        }

        return Product::query()
            ->with($relations)
            ->select(array_merge([
                'products.id',
                'products.name',
                'products.retail_price as price',
                'products.upc',
                DB::raw($soldLogic . ' AS sold'),
                DB::raw('0 as received'),
                DB::raw('0 as returned'),
                'upc_sale_total.location_name as location_name',
            ], $selectedColumns))
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where(
                config('app.product_variant') ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item',
                false
            )
            ->whereNull(config('app.product_variant') ? 'master_products.deleted_at' : 'products.deleted_at')
            ->where('products.company_id', $companyId)
            ->when(config('app.product_variant'), function ($query): void {
                $query->join(
                    'category_master_product',
                    'products.master_product_id',
                    '=',
                    'category_master_product.master_product_id'
                );
            }, function ($query): void {
                $query->join('category_product', 'products.id', '=', 'category_product.product_id');
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('productVariantValues', function ($query) use ($filterData): void {
                        $query->select('id')->whereIn('value', $filterData['attributes']);
                    });
                }
            )
            ->where($this->productsFilterForSaleThrough($filterData))
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct.tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    } else {
                        $query->whereHas('tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    }
                }
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'products.id as product_id',
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit = 0 THEN sale_items.quantity ELSE 0 END) as foc_units_sold'
                        ),
                        DB::raw(
                            'SUM(CASE WHEN sale_items.price_paid_per_unit != 0 THEN sale_items.quantity ELSE 0 END) as units_sold'
                        ),
                        'locations.name as location_name'
                    )
                    ->groupBy(['product_id', 'location_name']),
                'upc_sale_total',
                'upc_sale_total.product_id',
                '=',
                'products.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('upc_sale_total.units_sold');
            })
            ->get();
    }

    public function accumulatedSaleThroughReturnsDataByProductUpcForCustomReportForStoreWise(
        array $filterData,
        int $companyId,
    ): Collection {
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(ProductVariantValueQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [];
        if (config('app.product_variant')) {
            $relations = [
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ];
        }

        $selectedColumns = [];
        if (config('app.product_variant')) {
            $selectedColumns = ['products.master_product_id', 'master_products.article_number as article_number'];
        } else {
            $selectedColumns = ['products.article_number', 'products.color_id', 'products.size_id'];
        }

        return Product::query()
            ->with($relations)
            ->select(array_merge([
                'products.id',
                'products.name',
                'products.retail_price as price',
                'products.upc',
                'upc_return_total.return_units as returned',
                DB::raw('0 as sold'),
                DB::raw('0 as received'),
                'upc_return_total.location_name as location_name',
            ], $selectedColumns))
             ->when(config('app.product_variant'), function ($query): void {
                 $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
             })
            ->where(
                config('app.product_variant') ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item',
                false
            )
            ->whereNull(config('app.product_variant') ? 'master_products.deleted_at' : 'products.deleted_at')
            ->where('products.company_id', $companyId)
            ->when(config('app.product_variant'), function ($query): void {
                $query->join(
                    'category_master_product',
                    'products.master_product_id',
                    '=',
                    'category_master_product.master_product_id'
                );
            }, function ($query): void {
                $query->join('category_product', 'products.id', '=', 'category_product.product_id');
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('productVariantValues', function ($query) use ($filterData): void {
                        $query->select('id')->whereIn('value', $filterData['attributes']);
                    });
                }
            )
            ->where($this->productsFilterForSaleThrough($filterData))
            ->when(
                array_key_exists('tag_ids', $filterData) && [] !== $filterData['tag_ids'],
                function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct.tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    } else {
                        $query->whereHas('tags', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('id', $filterData['tag_ids']);
                        });
                    }
                }
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->where($this->productsFilterForSaleThrough($filterData))
                    ->where('locations.company_id', $companyId)
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.is_non_selling_item', false)
                            ->whereNull('master_products.deleted_at');
                    }, function ($query): void {
                        $query->where('products.is_non_selling_item', false)
                            ->whereNull('products.deleted_at');
                    })
                    ->when(
                        array_key_exists('location_ids', $filterData) &&
                            null !== $filterData['location_ids'],
                        function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
                        }
                    )
                    ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date']))
                    ->select(
                        'products.id as product_id',
                        DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        'locations.name as location_name'
                    )
                    ->groupBy(['product_id', 'location_name']),
                'upc_return_total',
                'upc_return_total.product_id',
                '=',
                'products.id'
            )
            ->where(function ($query): void {
                $query
                    ->orWhereNotNull('upc_return_total.return_units');
            })
            ->get();
    }

    public function getByIdsWithBrandAndCategoriesForEcommerce(
        array $productUpcs,
        array $productIds,
        int $companyId,
    ): Collection {
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);

        return Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'department_id',
                'sub_department_id',
                'color_id',
                'size_id',
                'brand_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'online_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'purchase_cost',
                'created_by_id',
                'created_by_type',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'vendor_id',
            )->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'vendor' => $vendorQueries->filterByIsConsignmentTrue(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
                'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
                'assemblyChildProducts.product:' . $this->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->when($productUpcs, function ($query) use ($productUpcs): void {
                $query->whereInCaseSensitive('upc', $productUpcs);
            })
            ->when($productIds, function ($query) use ($productIds): void {
                $query->whereIn('id', $productIds);
            })
            ->onlyActive()
            ->where('company_id', $companyId)
            ->get();
    }

    public function getProductEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId,
    ): Collection {
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        return Product::select(
            'id',
            'master_product_id',
            'name',
            'article_number',
            'compound_product_name',
            'code',
            'height',
            'width',
            'weight',
            'color_id',
            'company_id',
            'upc',
            'retail_price',
            'online_price',
            'size_id',
            'brand_id',
            'description',
            'status',
            'is_available_in_ecommerce',
            'deleted_at',
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'inventory:' . $inventoryQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            ])
            ->whereDoesntHave('productChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Product
    {
        return Product::select('id')
            ->whereDoesntHave('productChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Product
    {
        return Product::select('id')
            ->whereDoesntHave('productChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getCountOfProductEcommerceChannelByStartAndEndId(int $companyId, int $startId, int $endId): int
    {
        return Product::select('id', 'upc')
            ->whereDoesntHave('productChannelReferences')
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->count();
    }

    public function getLastProductIdEcommerceChannel(int $companyId): Product
    {
        return Product::query()
            ->select(DB::raw('max(id) as max_id'))
            ->whereDoesntHave('productChannelReferences')
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    public function getProductsArticleNumberForEcommerce(int $companyId): Collection
    {
        return Product::select('id', 'article_number')
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getPosProductListForZip(): void
    {
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $seasonQueries = new SeasonQueries();
        $departmentQueries = new DepartmentQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $brandQueries = new BrandQueries();
        $styleQueries = new StyleQueries();
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();
        $inventoryQueries = new InventoryQueries();
        $inventoryUnitQueries = new InventoryUnitQueries();
        $batchQueries = new BatchQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $mergeProductTransactionQueries = resolve(MergeProductTransactionQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $assemblyChildProductQueries = resolve(AssemblyChildProductQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        Product::query()
            ->select(
                'id',
                'company_id',
                'name',
                'compound_product_name',
                'code',
                'unit_of_measure_id',
                'season_id',
                'department_id',
                'sub_department_id',
                'color_id',
                'size_id',
                'brand_id',
                'style_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'type_id',
                'retail_price',
                'franchise_price_1',
                'franchise_price_2',
                'franchise_price_3',
                'wholesale_price',
                'company_or_tender_price',
                'branch_price',
                'minimum_price',
                'original_capital_price',
                'capital_price',
                'staff_price',
                'is_temporarily_unavailable',
                'has_batch',
                'status',
                'is_non_inventory',
                'is_non_selling_item',
                'is_available_in_pos',
                'is_sold_as_single_item',
                'master_product_id',
            )
            ->with([
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'masterProduct.media:' . $mediaQueries->getBasicColumnNames(),
                'masterProduct.assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
                'masterProduct.assemblyChildMasterProducts.item:' . $this->getColumnNameAndId(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'season:' . $seasonQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNames(),
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'style:' . $styleQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'inventory:' . $inventoryQueries->getBasicColumnNames(),
                'inventory.inventoryUnits' => $inventoryUnitQueries->positiveQuantityRecordsOnly(),
                'inventory.inventoryUnits.batch:' . $batchQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'mergeProductTransactions:' . $mergeProductTransactionQueries->getBasicColumnsName(),
                'mergeProductTransactions.oldProduct:' . $this->getIdAndUpc(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
                'boxes.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'assemblyChildProducts:' . $assemblyChildProductQueries->getBasicColumnNames(),
                'assemblyChildProducts.product:' . $this->getColumnNameAndId(),
            ])
            ->onlyActive()
            ->isSellingProduct()
            ->orderBy('company_id')
            ->chunk(5000, function (Collection $productCollections, $key): void {
                $posProductExportZipService = resolve(PosProductExportZipService::class);
                $posProductExportZipService->productExportWithJson($productCollections, $key);
            });
    }

    public function getProductByIdWithRelations(int $productId): Product
    {
        $sizeQueries = resolve(SizeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        return Product::select(
            'id',
            'name',
            'article_number',
            'color_id',
            'company_id',
            'upc',
            'retail_price',
            'size_id',
            'brand_id',
            'description',
            'status',
            'is_available_in_ecommerce',
            'deleted_at',
        )
            ->with([
                'color:' . $colorQueries->getBasicColumnNames(),
                'size:' . $sizeQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'inventory:' . $inventoryQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            ])
            ->withTrashed()
            ->findOrFail($productId);
    }

    public function updateOrCreate(array $variantData): Product
    {
        return Product::updateOrCreate([
            'upc' => $variantData['upc'],
        ], $variantData);
    }

    public function removeMasterProductVariantById(int $productVariantId): void
    {
        $productVariant = $this->getProductVariantById($productVariantId);
        $boxProductQueries = resolve(BoxProductQueries::class);

        $productVariant->tiers()->delete();
        $boxProductQueries->deleteProductBox($productVariant);
        $productVariant->productVariantValues()->delete();
        $productVariant->delete();
    }

    public function getProductVariantById(int $productVariantId): Product
    {
        $mediaQueries = resolve(MediaQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);

        return Product::query()->select(...$this->getAllBasicColumnNamesForVariant())
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tiers:' .  $productLoyaltyPointQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
            ])
            ->findOrFail($productVariantId);
    }

    public function removeProductVariantByMasterProductId(int $masterProductId): void
    {
        $productVariants = $this->getPrductVariantsByMasterProductId($masterProductId);
        $boxProductQueries = resolve(BoxProductQueries::class);

        foreach ($productVariants as $productVariant) {
            $productVariant->tiers()->delete();
            $boxProductQueries->deleteProductBox($productVariant);
            $productVariant->productVariantValues()->delete();
            $productVariant->delete();
        }
    }

    public function getPrductVariantsByMasterProductId(int $masterProductId): Collection
    {
        $mediaQueries = resolve(MediaQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);

        return Product::query()->select(...$this->getAllBasicColumnNamesForVariant())
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'tiers:' .  $productLoyaltyPointQueries->getBasicColumnNames(),
                'boxes:' . $boxProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
            ])
            ->where('master_product_id', $masterProductId)
            ->get();
    }

    public function getAll(int $companyId): Collection
    {
        return Product::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getAllBasicColumnNamesForVariant(): array
    {
        return [
            'id',
            'master_product_id',
            'name',
            'compound_product_name',
            'code',
            'description',
            'upc',
            'ean',
            'manufacturer_sku',
            'custom_sku',
            'retail_price',
            'wholesale_price',
            'staff_price',
            'minimum_price',
            'purchase_cost',
            'online_price',
            'is_temporarily_unavailable',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
        ];
    }

    public function getProductDetailsByArticleNumberForUploadImages(string $articleNumber, int $companyId): ?Product
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $styleQueries = resolve(StyleQueries::class);

        return Product::select('id', 'name', 'brand_id', 'style_id', 'article_number', 'upc', 'type_id')
            ->with([
                'style:' . $styleQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function uploadImagesByArticleNumber(
        ProductImageUploadByArticleNumberData $productImageUploadByArticleNumberData,
        int $companyId,
    ): void {
        $products = Product::select('id')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->where('article_number', $productImageUploadByArticleNumberData->article_number)
            ->get();

        foreach ($products as $product) {
            if ($productImageUploadByArticleNumberData->thumbnail instanceof UploadedFile) {
                $tempPath = $productImageUploadByArticleNumberData->thumbnail->store('temp');

                if ($tempPath) {
                    $tempFile = Storage::path($tempPath);
                    $product->addMedia($tempFile)->toMediaCollection('thumbnail');

                    Storage::delete($tempPath);
                }
            }

            if (null !== $productImageUploadByArticleNumberData->images && [] !== $productImageUploadByArticleNumberData->images) {
                $this->uploadPhotoByArticleNumber(
                    $product,
                    $productImageUploadByArticleNumberData->images,
                    $productImageUploadByArticleNumberData->delete_old_images
                );
            }

            if (null !== $productImageUploadByArticleNumberData->videos && [] !== $productImageUploadByArticleNumberData->videos) {
                $this->uploadVideoByArticleNumber(
                    $product,
                    $productImageUploadByArticleNumberData->videos,
                    $productImageUploadByArticleNumberData->delete_old_videos
                );
            }

            $this->setUpdatedAt($product);
        }
    }

    public function getProductVariantRelationColumns(): Closure
    {
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return fn ($query) => $query->select($this->getBasicColumnNames())
            ->with([
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
    }

    private function uploadVideoByArticleNumber(Product $product, array $videos, bool $deleteOldVideos): void
    {
        if ($deleteOldVideos) {
            $product->clearMediaCollection('videos');
        }

        foreach ($videos as $video) {
            if ($video instanceof UploadedFile) {
                $this->addMediaAndDelete($product, $video, 'videos');
            }
        }
    }

    private function uploadPhotoByArticleNumber(Product $product, array $images, bool $deleteOldImages): void
    {
        if ($deleteOldImages) {
            $product->clearMediaCollection('images');
        }

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $this->addMediaAndDelete($product, $image, 'images');
            }
        }
    }

    private function addMediaAndDelete(Product $product, UploadedFile $uploadedFile, string $collectionName): void
    {
        $tempPath = $uploadedFile->store('temporary');

        if (! $tempPath) {
            return;
        }

        $tempFile = Storage::path($tempPath);
        $product->addMedia($tempFile)->toMediaCollection($collectionName);

        Storage::delete($tempPath);
    }

    private function commonFilterQueryForReport(array $filterData, int $companyId): Closure
    {
        return fn ($query) => $query->select('id', 'name', 'article_number', 'upc')
            ->where(function ($subQuery) use ($filterData): void {
                $subQuery
                    ->whereAny(['upc', 'article_number', 'name'], 'LIKE', '%' . $filterData['search_text'] . '%')
                    ->orWhereHas('color', function ($query) use ($filterData): void {
                        $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                    })
                    ->orWhereHas('size', function ($query) use ($filterData): void {
                        $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                    });
            })
            ->where('company_id', $companyId);
    }

    private function searchForList(
        Builder $query,
        array $filterData,
        BrandQueries $brandQueries,
        CategoryQueries $categoryQueries,
    ): Builder {
        if (config('app.product_variant')) {
            return $query
                ->whereAny(
                    [
                        'name',
                        'compound_product_name',
                        'code',
                        'upc',
                        'retail_price',
                        'purchase_cost',
                        'ean',
                        'custom_sku',
                    ],
                    'LIKE',
                    '%' . $filterData['search_text'] . '%'
                )
                ->orwhereHas('masterProduct', function ($q) use ($filterData): void {
                    $q->whereAny(['article_number'], 'LIKE', '%' . $filterData['search_text'] . '%');
                })
                ->orWhereHas('masterProduct.brand', $brandQueries->searchByName($filterData['search_text']))
                ->orWhereHas('masterProduct.categories', $categoryQueries->searchByName($filterData['search_text']));
        }

        return $query
            ->whereAny(
                [
                    'name',
                    'compound_product_name',
                    'code',
                    'upc',
                    'article_number',
                    'retail_price',
                    'purchase_cost',
                    'ean',
                    'custom_sku',
                ],
                'LIKE',
                '%' . $filterData['search_text'] . '%'
            )
            ->orWhereHas('brand', $brandQueries->searchByName($filterData['search_text']))
            ->orWhereHas('categories', $categoryQueries->searchByName($filterData['search_text']));
    }

    private function commonFilterQuery(Builder $query, array $filterData): Builder
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        return $query->when(config('app.product_variant'), fn ($q) => $q->whereNotNull('master_product_id'))
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['product_type_id'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData): void {
                        $q->where('type_id', (int) $filterData['product_type_id']);
                    });
                } else {
                    $query->where('type_id', (int) $filterData['product_type_id']);
                }
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData, $categoryQueries): void {
                        $q->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                    });
                } else {
                    $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                }
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData, $tagQueries): void {
                        $q->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                    });
                } else {
                    $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                }
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData): void {
                        $q->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
                    });
                } else {
                    $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
                }
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData): void {
                        $q->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
                    });
                } else {
                    $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
                }
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                fn ($q) => $q->whereHas('productVariantValues', fn ($q) => $q->select('id')->whereIn(
                    'value',
                    $filterData['attributes']
                ))
            )
            ->when(
                ! config('app.product_variant'),
                fn ($q) => $q->when($filterData['color_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('color_id', (array) $filterData['color_ids']);
                })
            )
            ->when(
                ! config('app.product_variant'),
                fn ($q) => $q->when($filterData['size_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('size_id', (array) $filterData['size_ids']);
                })
            )
            ->when($filterData['product_collection_ids'], function ($query) use (
                $filterData,
                $productCollectionProductQueries
            ): void {
                $query->whereHas(
                    'productCollectionProducts',
                    $productCollectionProductQueries->filterByProductCollectionIds(
                        $filterData['product_collection_ids']
                    )
                );
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($q) use ($filterData): void {
                        $q->whereIn('article_number', (array) $filterData['article_numbers']);
                    });
                } else {
                    $query->whereIn('article_number', (array) $filterData['article_numbers']);
                }
            });
    }

    public function existsByArticleNumber(string $generatedArticleNumber): bool
    {
        return Product::select('id')
            ->whereCaseSensitive('article_number', $generatedArticleNumber)
            ->exists();
    }

    public function getProductNameForFilter(array $productIds): string
    {
        $productData = [];
        $product = Product::select('name')
            ->whereIntegerInRaw('id', values: $productIds)
            ->get();

        if ($product->isNotEmpty()) {
            $productData = $product->pluck('name')->toArray();
        }

        return implode(', ', $productData);
    }

    public function getPaginatedOnlineProductsReport(array $filterData, int $companyId): PaginationLengthAwarePaginator
    {
        return $this->getOnlineProductReport($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getOnlineProductsReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->getOnlineProductReport($filterData, $companyId)->get();
    }

    public function getProductByIdWithRelationsForEcommerce(int $productId): Product
    {
        $mediaQueries = resolve(MediaQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        return Product::select(
            'id',
            'master_product_id',
            'name',
            'article_number',
            'upc',
            'article_number',
            'company_id',
            'compound_product_name',
            'code',
            'retail_price',
            'online_price',
            'brand_id',
            'description',
            'height',
            'width',
            'weight',
            'status',
            'is_available_in_ecommerce',
            'deleted_at',
        )
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            ])
            ->findOrFail($productId);
    }

    private function getOnlineProductReport(array $filterData, int $companyId): QueryBuilder
    {
        $orderBuilder = DB::table('order_items')
            ->selectRaw('product_id, NULL AS sale_return_quantity, NULL AS sale_return_amount')
            ->selectRaw('SUM(order_items.total_price_paid) as order_amount')
            ->selectRaw('SUM(order_items.quantity) as order_quantity')
            ->selectRaw('orders.location_id')
            ->selectRaw('locations.name as location_name')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('orders.location_id', $filterData['location_ids']);
            })
            ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('orders.created_at', '>=', $filterData['date_range'][0])
                    ->where('orders.created_at', '<=', $filterData['date_range'][1]);
            })
            ->groupBy('product_id', 'orders.location_id');

        $order = DB::table(DB::raw('(' . $orderBuilder->toRawSql() . ') AS order_item_with_locations'))->select(
            'order_item_with_locations.location_id',
            'order_item_with_locations.location_name',
            'order_item_with_locations.product_id',
            'order_item_with_locations.order_amount',
            'order_item_with_locations.order_quantity',
            DB::raw('NULL AS order_return_amount'),
            DB::raw('NULL AS order_return_quantity')
        );

        $orderReturnBuilder = DB::table('order_return_items', 'order_return_items')
            ->selectRaw('product_id, NULL AS order_quantity, NULL AS order_amount')
            ->selectRaw('SUM(order_return_items.total_price_paid) as order_return_amount')
            ->selectRaw('SUM(order_return_items.quantity) as order_return_quantity')
            ->selectRaw('order_returns.location_id')
            ->selectRaw('locations.name as location_name')
            ->join('order_returns', 'order_return_items.order_return_id', '=', 'order_returns.id')
            ->join('locations', 'order_returns.location_id', '=', 'locations.id')
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('order_returns.location_id', $filterData['location_ids']);
            })
            ->when($filterData['region_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('order_returns.created_at', '>=', $filterData['date_range'][0])
                    ->where('order_returns.created_at', '<=', $filterData['date_range'][1]);
            })
            ->groupBy('product_id', 'order_returns.location_id');

        $orderReturn = DB::table(
            DB::raw('(' . $orderReturnBuilder->toRawSql() . ') AS order_return_item_with_locations')
        )->select(
            'order_return_item_with_locations.location_id',
            'order_return_item_with_locations.location_name',
            'order_return_item_with_locations.product_id',
            DB::raw('NULL AS order_amount'),
            DB::raw('NULL AS order_quantity'),
            'order_return_item_with_locations.order_return_amount',
            'order_return_item_with_locations.order_return_quantity'
        );

        $orderAndOrderReturnUnion = $order->unionAll($orderReturn);

        $result = DB::table(function ($query) use ($orderAndOrderReturnUnion): void {
            $query
                ->select(
                    'product_id',
                    'location_name',
                    'location_id',
                    DB::raw('SUM(order_quantity) as sum_order_quantity'),
                    DB::raw('SUM(order_return_quantity) as sum_order_return_quantity'),
                    DB::raw('SUM(order_amount) as sum_order_amount'),
                    DB::raw('SUM(order_return_amount) as sum_order_return_amount')
                )
                ->from($orderAndOrderReturnUnion)
                ->groupBy('product_id', 'location_id');
        }, 'order_and_return_product');

        return DB::table('products')
            ->join(DB::raw('(' . $result->toRawSql() . ') AS order_product'), function ($join): void {
                $join->on('products.id', '=', 'order_product.product_id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.master_product_id',
                    'master_products.article_number',
                    'products.upc',
                    'brands.name as brand_name',
                    'departments.name as department_name',
                    'products.sub_department_id',
                    'unit_of_measures.name as unit_of_measure_name',
                    'category_master_product.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    DB::raw('order_product.location_name AS location_name'),
                    DB::raw('order_product.location_id AS location_id'),
                    DB::raw('order_product.sum_order_quantity AS sum_order_quantity'),
                    DB::raw('order_product.sum_order_return_quantity AS sum_order_return_quantity'),
                    DB::raw('order_product.sum_order_amount AS sum_order_amount'),
                    DB::raw('order_product.sum_order_return_amount AS sum_order_return_amount'),
                    DB::raw("
                            CONCAT('[', GROUP_CONCAT(
                                JSON_OBJECT('attribute_name', attributes.name, 'attribute_value', product_variant_values.value)
                                ORDER BY attributes.id SEPARATOR ','
                            ), ']') AS product_variants
                        ")
                )
                ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
                ->leftJoin('attributes', 'product_variant_values.attribute_id', '=', 'attributes.id')
                ->groupBy('products.id');
            }, function ($query): void {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.article_number',
                    'products.upc',
                    'brands.name as brand_name',
                    'colors.name as color_name',
                    'sizes.name as size_name',
                    'departments.name as department_name',
                    'products.sub_department_id',
                    'seasons.name as season_name',
                    'unit_of_measures.name as unit_of_measure_name',
                    'product_category.category_names as category_names',
                    'products_tags.tag_names as tag_names',
                    DB::raw('order_product.location_name AS location_name'),
                    DB::raw('order_product.location_id AS location_id'),
                    DB::raw('order_product.sum_order_quantity AS sum_order_quantity'),
                    DB::raw('order_product.sum_order_return_quantity AS sum_order_return_quantity'),
                    DB::raw('order_product.sum_order_amount AS sum_order_amount'),
                    DB::raw('order_product.sum_order_return_amount AS sum_order_return_amount'),
                );
            })
            ->where('products.company_id', $companyId)
            ->where('products.is_non_selling_item', false)
            ->whereNull('products.deleted_at')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_master_product')
                        ->select(
                            'category_master_product.master_product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_master_product.category_id')
                        ->groupBy('category_master_product.master_product_id'),
                    'category_master_product',
                    'category_master_product.master_product_id',
                    '=',
                    'master_products.id'
                );
            }, function ($query): void {
                $query->leftJoinSub(
                    DB::table('category_product')
                        ->select(
                            'category_product.product_id',
                            DB::raw('GROUP_CONCAT(categories.name) as category_names')
                        )
                        ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
                        ->groupBy('category_product.product_id'),
                    'product_category',
                    'product_category.product_id',
                    '=',
                    'products.id'
                );
            })
            ->leftJoinSub(
                DB::table('product_tag')
                    ->select('product_tag.product_id', DB::raw('GROUP_CONCAT(tags.name) as tag_names'))
                    ->leftJoin('tags', 'tags.id', '=', 'product_tag.tag_id')
                    ->groupBy('product_tag.product_id'),
                'products_tags',
                'products_tags.product_id',
                '=',
                'products.id'
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpb', 'products.master_product_id', '=', 'mpb.id')
                    ->leftJoin('brands', 'mpb.brand_id', '=', 'brands.id');
            }, function ($query): void {
                $query->leftJoin('brands', 'products.brand_id', '=', 'brands.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('colors', 'products.color_id', '=', 'colors.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('sizes', 'products.size_id', '=', 'sizes.id');
            })
            ->when(config('app.product_variant') === false, function ($query): void {
                $query->leftJoin('seasons', 'products.season_id', '=', 'seasons.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpd', 'products.master_product_id', '=', 'mpd.id')
                    ->leftJoin('departments', 'mpd.department_id', '=', 'departments.id');
            }, function ($query): void {
                $query->leftJoin('departments', 'products.department_id', '=', 'departments.id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products as mpu', 'products.master_product_id', '=', 'mpu.id')
                    ->leftJoin('unit_of_measures', 'mpu.unit_of_measure_id', '=', 'unit_of_measures.id');
            }, function ($query): void {
                $query->leftJoin('unit_of_measures', 'products.unit_of_measure_id', '=', 'unit_of_measures.id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where($this->searchByCompoundNameForReport($filterData));
            })
            ->when(! empty($filterData['article_numbers']), function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpa', 'products.master_product_id', '=', 'mpa.id')
                        ->whereIn('mpa.article_number', $filterData['article_numbers']);
                } else {
                    $query->whereIn('products.article_number', $filterData['article_numbers']);
                }
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('products.id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpbi', 'products.master_product_id', '=', 'mpbi.id')
                        ->whereIntegerInRaw('mpbi.brand_id', $filterData['brand_ids']);
                } else {
                    $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                }
            })
            ->when(config('app.product_variant') === false && $filterData['color_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
            })
            ->when(config('app.product_variant') === false && $filterData['size_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'],
                function ($q) use ($filterData): void {
                    $q->whereExists(function ($subQuery) use ($filterData): void {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variant_values as pvv')
                            ->whereRaw('pvv.product_id = products.id')
                            ->whereIn('pvv.value', $filterData['attributes']);
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->leftJoin('master_products as mpdi', 'products.master_product_id', '=', 'mpdi.id')
                        ->whereIntegerInRaw('products.mpdi.department_id', $filterData['department_ids']);
                } else {
                    $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                }
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->whereRaw(
                        'products.master_products.id IN (select master_product_id from category_master_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                } else {
                    $query->whereRaw(
                        'products.id IN (select product_id from category_product where category_id in (' . implode(
                            ',',
                            $filterData['category_ids']
                        ) . '))'
                    );
                }
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_tag where tag_id in (' . implode(
                        ',',
                        $filterData['tag_ids']
                    ) . '))'
                );
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereRaw(
                    'products.id IN (select product_id from product_collection_products where product_collection_id =' . $filterData['product_collection_id'] . ')'
                );
            })
            ->where(function ($query): void {
                $query->whereNotNull('sum_order_quantity')
                    ->orWhereNotNull('sum_order_return_quantity')
                    ->orWhereNotNull('sum_order_amount')
                    ->orWhereNotNull('sum_order_return_amount');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('units_sold' === $filterData['sort_by']) {
                    $query->orderBy('sum_order_quantity', $filterData['sort_direction']);
                }

                if ('units_returned' === $filterData['sort_by']) {
                    $query->orderBy('sum_sale_return_quantity', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function existsByQrCode(string $qrCode, int $companyId, string $upc): ?bool
    {
        return Product::select('id', 'status')
            ->where('verification_qr_code', $qrCode)
            ->where('company_id', $companyId)
            ->where('upc', '!=', $upc)
            ->exists();
    }

    public function validateProductSaleChannelMatch(Product $product, SaleChannel $saleChannel): bool
    {
        return $product->saleChannels()
            ->wherePivot('sale_channel_id', $saleChannel->id)
            ->exists();
    }

    public function addNewProductForIntegration(
        ProductDataForIntegration $productData,
        int $companyId,
        User $user,
    ): ?Product {
        $productDetails = $productData->all();
        $productDetails['company_id'] = $companyId;
        $productDetails['created_by_id'] = $user->id;
        $productDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $productDetails['status'] = Statuses::DRAFT->value;

        $productDetails['has_batch'] = false;
        $productDetails['is_temporarily_unavailable'] = false;
        $productDetails['is_non_inventory'] = false;
        $productDetails['is_non_selling_item'] = false;
        $productDetails['is_available_in_pos'] = true;
        $productDetails['is_available_in_ecommerce'] = false;
        $productDetails['sell_item_via_derivative'] = false;
        $productDetails['is_sold_as_single_item'] = false;

        unset($productDetails['category_ids']);

        $productDetails = $this->getCompoundProductName($productDetails, $companyId);
        $product = Product::create($productDetails);

        $this->updateCategories($product, $productData->category_ids);

        $masterProductService = resolve(MasterProductService::class);

        $newProductData = new ProductData(...[
            'name' => $product->name,
            'description' => $product->description,
            'code' => $product->code,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'season_id' => $product->season_id,
            'brand_id' => $product->brand_id,
            'color_id' => $product->color_id,
            'size_id' => $product->size_id,
            'vendor_id' => $product->vendor_id,
            'department_id' => $product->department_id,
            'sub_department_id' => $product->sub_department_id,
            'style_id' => $product->style_id,
            'upc' => $product->upc,
            'ean' => $product->ean,
            'custom_sku' => $product->custom_sku,
            'manufacturer_sku' => $product->manufacturer_sku,
            'article_number' => $product->article_number,
            'type_id' => ProductTypes::getCaseNameByValue($product->type_id),
            'retail_price' => $product->retail_price,
            'franchise_price_1' => $product->franchise_price_1,
            'franchise_price_2' => $product->franchise_price_2,
            'franchise_price_3' => $product->franchise_price_3,
            'wholesale_price' => $product->wholesale_price,
            'company_or_tender_price' => $product->company_or_tender_price,
            'branch_price' => $product->branch_price,
            'minimum_price' => $product->minimum_price,
            'original_capital_price' => $product->original_capital_price,
            'capital_price' => $product->capital_price,
            'staff_price' => $product->staff_price,
            'purchase_cost' => $product->purchase_cost,
            'online_price' => $product->online_price,
            'height' => (int) $product->height,
            'width' => (int) $product->width,
            'weight' => (int) $product->weight,
            'category_ids' => $productData->category_ids,
            'verification_qr_code' => $product->verification_qr_code,
            'thumbnail' => null,
            'images' => [],
            'videos' => [],
            'is_warranty' => $product->is_warranty ?? false,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
            'has_batch' => $product->has_batch,
            'is_non_inventory' => $product->is_non_inventory,
            'is_non_selling_item' => $product->is_non_selling_item,
            'is_available_in_pos' => $product->is_available_in_pos,
            'is_available_in_ecommerce' => $product->is_available_in_ecommerce,
            'is_sold_as_single_item' => $product->is_sold_as_single_item,
            'sell_item_via_derivative' => $product->sell_item_via_derivative,
        ]);

        $masterProductService->createOrUpdateFromProduct($product, $newProductData);

        return $product;
    }

    public function getUpcById(int $productId): string
    {
        return Product::select('upc')
            ->where('id', $productId)
            ->value('upc');
    }

    public function getAllByCompanyId(int $companyId, int $perPage = 1000): LengthAwarePaginator
    {
        if (config('app.product_variant')) {
            return Product::select(
                'id',
                'name',
                'retail_price',
                'master_product_id',
                'company_id',
                'brand_id',
                'vendor_id',
                'original_created_at',
                'article_number',
            )
            ->with([
                'productVariantValues' => static function ($query) use ($companyId): void {
                    $query->whereHas('attribute', function ($query) use ($companyId): void {
                        $query->where('company_id', $companyId);
                    });
                },
            ])
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->paginate($perPage);
        }

        return Product::select(
            'products.id',
            'products.name',
            'retail_price',
            'master_product_id',
            'products.company_id as company_id',
            'brand_id',
            'vendor_id',
            'original_created_at',
            'article_number',
            'color_id',
            'size_id',
            'colors.name as color_name',
            'sizes.name as size_name'
        )
        ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
        ->leftJoin('sizes', 'products.size_id', '=', 'sizes.id')
        ->where('products.company_id', $companyId)
        ->where('status', Statuses::ACTIVE->value)
        ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
        ->paginate($perPage);
    }

    public function getByIdWithProductVariantValues(int $productId, int $companyId): Product
    {
        return Product::select(
            'id',
            'name',
            'retail_price',
            'master_product_id',
            'company_id',
            'brand_id',
            'vendor_id',
            'original_created_at',
            'article_number',
            'type_id',
            'status'
        )
        ->with([
            'productVariantValues' => static function ($query) use ($companyId): void {
                $query->whereHas('attribute', function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                });
            },
        ])
        ->where('company_id', $companyId)
        ->where('status', Statuses::ACTIVE->value)
        ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
        ->findOrFail($productId);
    }

    public function getCompanyActiveRegularProductCount(int $companyId): int
    {
        return Product::where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->count();
    }

    public function createOrUpdateProductFromAzentioItem(array $productData): void
    {
        Product::updateOrCreate([
            'upc' => $productData['upc'],
            'company_id' => $productData['company_id'],
        ], [
            'name' => $productData['name'],
            'brand_id' => $productData['brand_id'],
            'color_id' => $productData['color_id'],
            'size_id' => $productData['size_id'],
            'status' => Statuses::ACTIVE->value,
        ]);
    }

    public function getProductIds(array $filterData, int $companyId): Collection
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        return Product::query()
            ->select('id')
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $this->searchForList($query, $filterData, $brandQueries, $categoryQueries);
                });
            })
            ->when($filterData['product_sync_type_id'], function ($query) use (
                $filterData,
                $saleChannelQueries
            ): void {
                $query->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::SYNC_WITH_ECOMMERCE->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->whereHas('productChannelReference', function ($query) use (
                            $saleChannelQueries
                        ): void {
                            $query->whereHas(
                                'saleChannel',
                                $saleChannelQueries->filterByTypeId(SaleChannelTypes::ECOMMERCE->value)
                            );
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::SYNC_WITH_WEBSPERT->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->whereHas('productChannelReference', function ($query) use (
                            $saleChannelQueries
                        ): void {
                            $query->whereHas(
                                'saleChannel',
                                $saleChannelQueries->filterByTypeId(SaleChannelTypes::WEBSPERT_ECOMMERCE->value)
                            );
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::NOT_SYNC_WITH_ECOMMERCE->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->where(function ($query) use ($saleChannelQueries): void {
                            $query->whereDoesntHave('productChannelReference')
                                ->orWhereHas('productChannelReference', function ($query) use (
                                    $saleChannelQueries
                                ): void {
                                    $query->whereHas(
                                        'saleChannel',
                                        $saleChannelQueries->filterByTypeId(SaleChannelTypes::ECOMMERCE->value, '!=')
                                    );
                                });
                        });
                    }
                )->when(
                    $filterData['product_sync_type_id'] === ProductSyncTypes::NOT_SYNC_WITH_WEBSPERT->value,
                    function ($query) use ($saleChannelQueries): void {
                        $query->where(function ($query) use ($saleChannelQueries): void {
                            $query->whereDoesntHave('productChannelReference')
                                ->orWhereHas('productChannelReference', function ($query) use (
                                    $saleChannelQueries
                                ): void {
                                    $query->whereHas(
                                        'saleChannel',
                                        $saleChannelQueries->filterByTypeId(
                                            SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
                                            '!='
                                        )
                                    );
                                });
                        });
                    }
                );
            })
            ->where('company_id', $companyId)
            ->whereNot('status', Statuses::DRAFT->value)
            ->tap(fn ($query): Builder => $this->commonFilterQuery($query, $filterData))
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', true);
                } else {
                    $query->where('has_batch', true);
                }
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }

                if (config('app.product_variant')) {
                    $query->where('masterProduct.has_batch', false);
                } else {
                    $query->where('has_batch', false);
                }
            })
            ->get();
    }
}
