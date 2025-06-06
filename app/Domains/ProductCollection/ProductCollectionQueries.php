<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection;

use App\Domains\Admin\AdminQueries;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ProductCollection\DataObjects\ProductCollectionData;
use App\Domains\ProductCollection\DataObjects\ProductCollectionImagesData;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilter\Enums\VariantFilterTypes;
use App\Domains\ProductCollectionFilter\ProductCollectionFilterQueries;
use App\Domains\ProductCollectionFilterAttributeValue\ProductCollectionFilterAttributeValueQueries;
use App\Domains\ProductCollectionFilterType\ProductCollectionFilterTypeQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductCollectionFilter;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductCollectionQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getProductCollectionQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getProductCollections(int $companyId): Collection
    {
        return ProductCollection::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function getPaginatedProductCollectionsForEcommerce(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        $mediaQueries = new MediaQueries();

        return ProductCollection::query()
            ->select('id', 'name', 'created_at', 'updated_at', 'status')
            ->with(['media:' . $mediaQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getPaginatedProductCollectionsForPos(array $filterData, int $companyId): LengthAwarePaginator
    {
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        return ProductCollection::query()
            ->select('id', 'name', 'created_at', 'updated_at', 'status')
            ->with([
                'productCollectionProducts:' . $productCollectionProductQueries->getProductCollectionAndProductIdColumns(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getProductByProductCollectionAndCompany(
        int $productId,
        ProductCollection $productCollection,
        int $companyId
    ): ?Product {
        $masterProductQueries = resolve(MasterProductQueries::class);

        $collectionFilters = $productCollection->productCollectionFilter;
        $whereCondition = 'where';
        $whereHasCondition = 'whereHas';
        $whereInCondition = 'whereIn';
        $whereAnyCondition = 'whereAny';

        if ($productCollection->logical_connector_type_id->value === LogicalConnectorTypes::OR->value) {
            $whereCondition = 'orWhere';
            $whereHasCondition = 'orWhereHas';
            $whereInCondition = 'orWhereIn';
            $whereAnyCondition = 'orWhereAny';
        }

        $query = Product::select('id', 'company_id', 'master_product_id')
            ->with(['masterProduct:' . $masterProductQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('id', $productId)
            ->where('status', Statuses::ACTIVE->value);
        $query = $this->applyFilters(
            $query,
            $collectionFilters,
            $whereCondition,
            $whereHasCondition,
            $whereInCondition,
            $whereAnyCondition
        );

        /** @var Product */
        return $query->first();
    }

    public function addNew(User $user, ProductCollectionData $collectionData, int $companyId): ProductCollection
    {
        $productCollection = ProductCollection::create([
            'name' => $collectionData->name,
            'logical_connector_type_id' => $collectionData->logical_connector_type_id,
            'company_id' => $companyId,
            'status' => true,
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->id,
            'is_available_in_ecommerce' => $collectionData->is_available_in_ecommerce,
        ]);

        $productCollectionFilterQueries = resolve(ProductCollectionFilterQueries::class);

        $productCollectionFilterQueries->separateByFilter(
            $collectionData->collection_filter_types,
            $productCollection->id
        );

        $this->updateSaleChannels($productCollection, $collectionData);

        return $productCollection;
    }

    public function changeStatus(int $productCollectionId): void
    {
        $productCollection = ProductCollection::select('id', 'status')->findOrFail($productCollectionId);
        $productCollection->status = ! $productCollection->status;
        $productCollection->save();
    }

    public function delete(int $productCollectionId, int $companyId): void
    {
        $productCollection = ProductCollection::select('id')
            ->where('company_id', $companyId)
            ->findOrFail($productCollectionId);
        $productCollection->delete();
    }

    public function edit(int $productCollectionId, int $companyId): ProductCollection
    {
        $collectionFilterQueries = resolve(ProductCollectionFilterQueries::class);
        $productCollectionFilterTypeQueries = resolve(ProductCollectionFilterTypeQueries::class);
        $categoriesQueries = resolve(CategoryQueries::class);
        $seasonsQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $colorsQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $tagsQueries = resolve(TagQueries::class);
        $productCollectionFilterAttributeValueQueries = resolve(ProductCollectionFilterAttributeValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        if (config('app.product_variant')) {
            return ProductCollection::select('id', 'name', 'logical_connector_type_id', 'is_available_in_ecommerce')
                ->with([
                    'productCollectionFilter:' . $collectionFilterQueries->getBasicColumnNames(),
                    'productCollectionFilter.types:' . $productCollectionFilterTypeQueries->getBasicColumnNames(),
                    'productCollectionFilter.categories:' . $categoriesQueries->getBasicColumnNamesForProductCollection(),
                    'productCollectionFilter.departments:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                    'productCollectionFilter.brands:' . $brandQueries->getIdAndNameColumnNames(),
                    'productCollectionFilter.tags:' . $tagsQueries->getBasicColumnNames(),
                    'productCollectionFilter.attributeValues:' . $productCollectionFilterAttributeValueQueries->getBasicColumnNames(),
                    'productCollectionFilter.attributeValues.attribute:' . $attributeQueries->getColumnsForProductCollection(),
                    'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($productCollectionId);
        }

        return ProductCollection::select('id', 'name', 'logical_connector_type_id', 'is_available_in_ecommerce')
            ->with([
                'productCollectionFilter:' . $collectionFilterQueries->getBasicColumnNames(),
                'productCollectionFilter.types:' . $productCollectionFilterTypeQueries->getBasicColumnNames(),
                'productCollectionFilter.categories:' . $categoriesQueries->getBasicColumnNamesForProductCollection(),
                'productCollectionFilter.seasons:' . $seasonsQueries->getBasicColumnNamesForProductCollection(),
                'productCollectionFilter.departments:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                'productCollectionFilter.colors:' . $colorsQueries->getBasicColumnNamesForRegularSalesApi(),
                'productCollectionFilter.sizes:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'productCollectionFilter.brands:' . $brandQueries->getIdAndNameColumnNames(),
                'productCollectionFilter.styles:' . $styleQueries->getBasicColumnNamesProductCollection(),
                'productCollectionFilter.tags:' . $tagsQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($productCollectionId);
    }

    public function update(ProductCollectionData $productCollectionData, int $productCollectionId): ProductCollection
    {
        /** @var LogicalConnectorTypes $logicConnectorTypeId */
        $logicConnectorTypeId = $productCollectionData->logical_connector_type_id;

        $productCollection = $this->getById($productCollectionId);
        $productCollection->name = $productCollectionData->name;
        $productCollection->logical_connector_type_id = $logicConnectorTypeId;
        $productCollection->is_available_in_ecommerce = $productCollectionData->is_available_in_ecommerce;
        $productCollection->save();

        $productCollectionFilterQueries = resolve(ProductCollectionFilterQueries::class);

        $productCollectionFilterQueries->updateFilter(
            $productCollectionData->collection_filter_types,
            $productCollectionId
        );

        $this->setUpdatedAt($productCollection);

        $this->updateSaleChannels($productCollection, $productCollectionData);

        return $productCollection;
    }

    private function updateSaleChannels(
        ProductCollection $productCollection,
        ProductCollectionData $productCollectionData
    ): void {
        if (! array_key_exists('sale_channel_ids', $productCollectionData->all())) {
            return;
        }

        if (null === $productCollectionData->sale_channel_ids) {
            return;
        }

        $productCollection->saleChannels()->sync($productCollectionData->sale_channel_ids);
    }

    public function getMatchProducts(ProductCollection $productCollection, int $companyId): Collection
    {
        $masterProductQueries = resolve(MasterProductQueries::class);

        $collectionFilters = $productCollection->productCollectionFilter;
        $whereCondition = 'where';
        $whereHasCondition = 'whereHas';
        $whereInCondition = 'whereIn';
        $whereAnyCondition = 'whereAny';

        if ($productCollection->logical_connector_type_id->value === LogicalConnectorTypes::OR->value) {
            $whereCondition = 'orWhere';
            $whereHasCondition = 'orWhereHas';
            $whereInCondition = 'orWhereIn';
            $whereAnyCondition = 'orWhereAny';
        }

        $query = Product::select('id', 'company_id', 'master_product_id')
            ->with(['masterProduct:' . $masterProductQueries->getBasicColumnNames()])
            ->whereDoesntHave('productCollectionProducts', function ($query) use ($productCollection): void {
                $query->select('id')
                    ->where('product_collection_id', $productCollection->id);
            })
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value);
        $query = $this->applyFilters(
            $query,
            $collectionFilters,
            $whereCondition,
            $whereHasCondition,
            $whereInCondition,
            $whereAnyCondition
        );

        return $query->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function getProductCollectionById(int $productCollectionId, int $companyId): ProductCollection
    {
        return ProductCollection::select('id')
            ->where('company_id', $companyId)
            ->findOrFail($productCollectionId);
    }

    public function getByIdWithRelation(int $productCollectionId): ProductCollection
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $productCollection = $this->getById($productCollectionId);

        return $productCollection->load(['importRecord:' . $importRecordQueries->getModuleWithStatusColumns()]);
    }

    public function getColumnNameAndId(): string
    {
        return 'id,name';
    }

    public function getById(int $productCollectionId): ProductCollection
    {
        return ProductCollection::select('id', 'name')->findOrFail($productCollectionId);
    }

    public function getByIdWithMedia(int $productCollectionId): ProductCollection
    {
        $mediaQueries = resolve(MediaQueries::class);

        return ProductCollection::select('id', 'name', 'status', 'company_id', 'created_at', 'updated_at')
            ->with(['media:' . $mediaQueries->getBasicColumnNames()])
            ->findOrFail($productCollectionId);
    }

    public function updateLastSyncById(int $productCollectionId): void
    {
        $productCollection = ProductCollection::select('id', 'last_sync_at')->findOrFail($productCollectionId);

        $productCollection->last_sync_at = now()->format('Y-m-d h:i:s');
        $productCollection->save();
    }

    public function removeImage(int $productCollectionId, int $mediaId, string $mediaName): void
    {
        $productCollection = ProductCollection::select('id')->findOrFail($productCollectionId);

        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $productCollection->getMedia($mediaName)->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function uploadImages(
        ProductCollection $productCollection,
        ProductCollectionImagesData $productCollectionImageData
    ): void {
        $this->uploadSquareImage($productCollection, $productCollectionImageData);
        $this->uploadPortraitImages($productCollection, $productCollectionImageData);
        $this->uploadLandscapeImages($productCollection, $productCollectionImageData);
    }

    public function getFilteredProductCollectionsByCompanyId(string $searchText, int $companyId): Collection
    {
        return ProductCollection::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function setUpdatedAt(ProductCollection $productCollection): void
    {
        $productCollection->touch();
    }

    private function uploadSquareImage(
        ProductCollection $productCollection,
        ProductCollectionImagesData $collectionData
    ): void {
        if ($collectionData->square_image instanceof UploadedFile) {
            $productCollection->addMedia($collectionData->square_image)->toMediaCollection('square_image');
            $this->setUpdatedAt($productCollection);
        }
    }

    private function uploadPortraitImages(
        ProductCollection $productCollection,
        ProductCollectionImagesData $collectionData
    ): void {
        if (! $collectionData->portrait_images) {
            return;
        }

        foreach ($collectionData->portrait_images as $image) {
            if ($image instanceof UploadedFile) {
                $productCollection->addMedia($image)->toMediaCollection('portrait_images');
                $this->setUpdatedAt($productCollection);
            }
        }
    }

    private function uploadLandscapeImages(
        ProductCollection $productCollection,
        ProductCollectionImagesData $collectionData
    ): void {
        if (! $collectionData->landscape_images) {
            return;
        }

        foreach ($collectionData->landscape_images as $image) {
            if ($image instanceof UploadedFile) {
                $productCollection->addMedia($image)->toMediaCollection('landscape_images');
                $this->setUpdatedAt($productCollection);
            }
        }
    }

    private function getProductCollectionQuery(array $filterData, int $companyId): Builder
    {
        $adminQueries = resolve(AdminQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        return ProductCollection::query()
            ->select(
                'id',
                'name',
                'number_of_products',
                'pending_products',
                'logical_connector_type_id',
                'last_sync_at',
                'status',
                'created_by_id',
                'created_by_type',
            )
            ->with([
                'createdBy' => function (MorphTo $morphTo) use ($adminQueries): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
                'importRecord:'. $importRecordQueries->getBasicColumns(),
                'productCollectionProducts:'. $productCollectionProductQueries->getBasicColumns(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->orderBy('id', 'desc');
    }

    private function applyFilters(
        Builder $query,
        Collection $collectionFilters,
        string $whereCondition,
        string $whereHasCondition,
        string $whereInCondition,
        string $whereAnyCondition
    ): Builder {
        return $query->where(function ($query) use (
            $collectionFilters,
            $whereCondition,
            $whereHasCondition,
            $whereInCondition,
            $whereAnyCondition
        ): void {
            foreach ($collectionFilters as $collectionFilter) {
                $query = $this->addWhereConditionFilter($query, $whereCondition, $collectionFilter);
                $query = $this->addWhereHasConditionFilter($query, $whereHasCondition, $collectionFilter);
                $query = $this->addWhereInConditionFilter($query, $whereInCondition, $collectionFilter);
                $query = $this->addWhereAndWhereAnyConditionFilter(
                    $query,
                    $whereCondition,
                    $whereAnyCondition,
                    $collectionFilter
                );
            }
        });
    }

    private function addCategoryFilter(Builder $query, string $whereHasCondition, Collection $categories): Builder
    {
        $categoryQueries = new CategoryQueries();
        $categoryArray = $categories->pluck('id')->toArray();

        if (config('app.product_variant')) {
            $query->whereHas('masterProduct', function ($q) use (
                $categoryArray,
                $categoryQueries,
                $whereHasCondition
            ): void {
                $q->{$whereHasCondition}('categories', $categoryQueries->filterByIds($categoryArray));
            });
        } else {
            $query->{$whereHasCondition}('categories', $categoryQueries->filterByIds($categoryArray));
        }

        return $query;
    }

    private function addSeasonFilter(Builder $query, string $whereInCondition, Collection $seasons): Builder
    {
        $seasonArray = $seasons->pluck('id')->toArray();

        return $query->{$whereInCondition}('season_id', $seasonArray);
    }

    private function addDepartmentFilter(Builder $query, string $whereInCondition, Collection $departments): Builder
    {
        $departmentArray = $departments->pluck('id')->toArray();

        if (config('app.product_variant')) {
            $query->whereHas('masterProduct', function ($q) use ($departmentArray, $whereInCondition): void {
                $q->{$whereInCondition}('department_id', $departmentArray);
            });
        } else {
            $query->{$whereInCondition}('department_id', $departmentArray);
        }

        return $query;
    }

    private function addColorFilter(Builder $query, string $whereInCondition, Collection $colors): Builder
    {
        $colorArray = $colors->pluck('id')->toArray();

        return $query->{$whereInCondition}('color_id', $colorArray);
    }

    private function addSizeFilter(Builder $query, string $whereInCondition, Collection $sizes): Builder
    {
        $sizeArray = $sizes->pluck('id')->toArray();

        return $query->{$whereInCondition}('size_id', $sizeArray);
    }

    private function addBrandFilter(Builder $query, string $whereInCondition, Collection $brands): Builder
    {
        $brandArray = $brands->pluck('id')->toArray();

        if (config('app.product_variant')) {
            $query->whereHas('masterProduct', function ($q) use ($brandArray, $whereInCondition): void {
                $q->{$whereInCondition}('brand_id', $brandArray);
            });
        } else {
            $query->{$whereInCondition}('brand_id', $brandArray);
        }

        return $query;
    }

    private function addStyleFilter(Builder $query, string $whereInCondition, Collection $styles): Builder
    {
        $styleArray = $styles->pluck('id')->toArray();

        return $query->{$whereInCondition}('style_id', $styleArray);
    }

    private function addTagFilter(Builder $query, string $whereHasCondition, Collection $tags): Builder
    {
        $tagQueries = new TagQueries();
        $tagArray = $tags->pluck('id')->toArray();

        if (config('app.product_variant')) {
            $query->whereHas('masterProduct', function ($q) use ($tagQueries, $tagArray, $whereHasCondition): void {
                $q->{$whereHasCondition}('tags', $tagQueries->filterByIds($tagArray));
            });
        } else {
            $query->{$whereHasCondition}('tags', $tagQueries->filterByIds($tagArray));
        }

        return $query;
    }

    private function addTypeFilter(Builder $query, string $whereInCondition, Collection $types): Builder
    {
        $typeArray = $types->pluck('type_id')->toArray();

        if (config('app.product_variant')) {
            $query->whereHas('masterProduct', function ($q) use ($typeArray, $whereInCondition): void {
                $q->{$whereInCondition}('type_id', $typeArray);
            });
        } else {
            $query->{$whereInCondition}('type_id', $typeArray);
        }

        return $query;
    }

    private function addIsAvailableInPosQuery(Builder $query, string $whereCondition, string $value): Builder
    {
        return $query->{$whereCondition}('is_available_in_pos', (int) $value);
    }

    private function addIsAvailableInEcomFilter(Builder $query, string $whereCondition, string $value): Builder
    {
        return $query->{$whereCondition}('is_available_in_ecommerce', (int) $value);
    }

    private function addNameConditionFilter(
        Builder $query,
        ?int $conditionId,
        string $whereCondition,
        string $whereAnyCondition,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}('name', $value);
        }

        if ($conditionId === ConditionOperatorTypes::CONTAINS->value) {
            return $query->{$whereAnyCondition}(['name'], 'LIKE', '%'. $value . '%');
        }

        return $query;
    }

    private function addPriceConditionFilter(
        Builder $query,
        ?int $conditionId,
        string $whereCondition,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}('retail_price', (float) $value);
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}('retail_price', '<', (float) $value);
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}('retail_price', '>', (float) $value);
        }

        return $query;
    }

    private function addCreatedByConditionFilter(
        Builder $query,
        string $whereCondition,
        ?int $conditionId,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereDate('created_at', '=', $value);
            });
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereDate('created_at', '<', $value);
            });
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereDate('created_at', '>', $value);
            });
        }

        return $query;
    }

    private function addSaleUnitSoldConditionFilter(
        Builder $query,
        string $whereCondition,
        ?int $conditionId,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereHas('saleItems', function ($query) use ($value): void {
                    $query->select('id')
                    ->havingRaw('SUM(quantity - returned_quantity) = ' . (float) $value);
                });
            });
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereHas('saleItems', function ($query) use ($value): void {
                    $query->select('id')
                    ->havingRaw('SUM(quantity - returned_quantity) < ' . (float) $value);
                });
            });
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereHas('saleItems', function ($query) use ($value): void {
                    $query->select('id')
                    ->havingRaw('SUM(quantity - returned_quantity) > ' . (float) $value);
                });
            });
        }

        return $query;
    }

    private function addSaleAmountConditionFilter(
        Builder $query,
        string $whereCondition,
        ?int $conditionId,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM sale_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM sale_return_items WHERE product_id = products.id) = '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM sale_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM sale_return_items WHERE product_id = products.id) < '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM sale_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM sale_return_items WHERE product_id = products.id) > '. $value
                );
            });
        }

        return $query;
    }

    private function addOrderUnitSoldConditionFilter(
        Builder $query,
        string $whereCondition,
        ?int $conditionId,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(quantity) FROM order_items WHERE product_id = products.id) - (SELECT SUM(quantity) FROM order_return_items WHERE product_id = products.id) = '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(quantity) FROM order_items WHERE product_id = products.id) - (SELECT SUM(quantity) FROM order_return_items WHERE product_id = products.id) < '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(quantity) FROM order_items WHERE product_id = products.id) - (SELECT SUM(quantity) FROM order_return_items WHERE product_id = products.id) > '. $value
                );
            });
        }

        return $query;
    }

    private function addAttributeFilter(Builder $query, string $whereCondition, Collection $attributeValues): Builder
    {
        $attributeValuePairs = $attributeValues->map(fn ($item): array => [
            'attribute_id' => $item->attribute_id,
            'value' => $item->value,
        ])->toArray();

        if ('where' === $whereCondition) {
            foreach ($attributeValuePairs as $pair) {
                $query->whereHas('productVariantValues', function ($q) use ($pair, $whereCondition): void {
                    $q->{$whereCondition}('attribute_id', $pair['attribute_id'])
                    ->{$whereCondition}('value', $pair['value']);
                });
            }
        } else {
            $query->whereHas('productVariantValues', function ($q) use ($attributeValuePairs, $whereCondition): void {
                $q->where(function ($subQuery) use ($attributeValuePairs, $whereCondition): void {
                    foreach ($attributeValuePairs as $pair) {
                        $subQuery->{$whereCondition}(function ($q) use ($pair): void {
                            $q->where('attribute_id', $pair['attribute_id'])
                                ->where('value', $pair['value']);
                        });
                    }
                });
            });
        }

        return $query;
    }

    private function addOrderAmountConditionFilter(
        Builder $query,
        string $whereCondition,
        ?int $conditionId,
        string $value
    ): Builder {
        if ($conditionId === ConditionOperatorTypes::EQUAL->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM order_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM order_return_items WHERE product_id = products.id) = '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::LESS_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM order_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM order_return_items WHERE product_id = products.id) < '. $value
                );
            });
        }

        if ($conditionId === ConditionOperatorTypes::GREATER_THAN->value) {
            return $query->{$whereCondition}(function ($query) use ($value): void {
                $query->whereRaw(
                    '(SELECT SUM(total_price_paid) FROM order_items WHERE product_id = products.id) - (SELECT SUM(total_price_paid) FROM order_return_items WHERE product_id = products.id) > '. $value
                );
            });
        }

        return $query;
    }

    private function addWhereConditionFilter(
        Builder $query,
        string $whereCondition,
        ProductCollectionFilter $collectionFilter
    ): Builder {
        if (config('app.product_variant')) {
            if ($collectionFilter->filter_type_id === VariantFilterTypes::IS_AVAILABLE_IN_POS->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addIsAvailableInPosQuery($query, $whereCondition, $collectionFilter->value);
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addIsAvailableInEcomFilter($query, $whereCondition, $collectionFilter->value);
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::CREATED_BY->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addCreatedByConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::SALE_UNIT_SOLD->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addSaleUnitSoldConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::SALE_AMOUNT->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addSaleAmountConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::ORDER_UNIT_SOLD->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addOrderUnitSoldConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::ORDER_AMOUNT->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addOrderAmountConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::ATTRIBUTES->value &&
                null !== $collectionFilter->attributeValues
            ) {
                $query = $this->addAttributeFilter($query, $whereCondition, $collectionFilter->attributeValues);
            }

            if ($collectionFilter->filter_type_id !== VariantFilterTypes::PRICE->value) {
                return $query;
            }

            if (null === $collectionFilter->value) {
                return $query;
            }
        } else {
            if ($collectionFilter->filter_type_id === FilterTypes::IS_AVAILABLE_IN_POS->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addIsAvailableInPosQuery($query, $whereCondition, $collectionFilter->value);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addIsAvailableInEcomFilter($query, $whereCondition, $collectionFilter->value);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::CREATED_BY->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addCreatedByConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === FilterTypes::SALE_UNIT_SOLD->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addSaleUnitSoldConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === FilterTypes::SALE_AMOUNT->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addSaleAmountConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === FilterTypes::ORDER_UNIT_SOLD->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addOrderUnitSoldConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id === FilterTypes::ORDER_AMOUNT->value &&
                null !== $collectionFilter->value
            ) {
                $query = $this->addOrderAmountConditionFilter(
                    $query,
                    $whereCondition,
                    $collectionFilter->condition_operator_type_id,
                    $collectionFilter->value
                );
            }

            if ($collectionFilter->filter_type_id !== FilterTypes::PRICE->value) {
                return $query;
            }

            if (null === $collectionFilter->value) {
                return $query;
            }
        }

        return $this->addPriceConditionFilter(
            $query,
            $collectionFilter->condition_operator_type_id,
            $whereCondition,
            $collectionFilter->value
        );
    }

    private function addWhereHasConditionFilter(
        Builder $query,
        string $whereHasCondition,
        ProductCollectionFilter $collectionFilter
    ): Builder {
        if (config('app.product_variant')) {
            if ($collectionFilter->filter_type_id === VariantFilterTypes::CATEGORY->value) {
                $query = $this->addCategoryFilter($query, $whereHasCondition, $collectionFilter->categories);
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::TAG->value) {
                return $this->addTagFilter($query, $whereHasCondition, $collectionFilter->tags);
            }
        } else {
            if ($collectionFilter->filter_type_id === FilterTypes::CATEGORY->value) {
                $query = $this->addCategoryFilter($query, $whereHasCondition, $collectionFilter->categories);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::TAG->value) {
                return $this->addTagFilter($query, $whereHasCondition, $collectionFilter->tags);
            }
        }

        return $query;
    }

    private function addWhereInConditionFilter(
        Builder $query,
        string $whereInCondition,
        ProductCollectionFilter $collectionFilter
    ): Builder {
        if (config('app.product_variant')) {
            if ($collectionFilter->filter_type_id === VariantFilterTypes::DEPARTMENT->value) {
                $query = $this->addDepartmentFilter($query, $whereInCondition, $collectionFilter->departments);
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::BRAND->value) {
                $query = $this->addBrandFilter($query, $whereInCondition, $collectionFilter->brands);
            }

            if ($collectionFilter->filter_type_id === VariantFilterTypes::TYPE->value) {
                return $this->addTypeFilter($query, $whereInCondition, $collectionFilter->types);
            }
        } else {
            if ($collectionFilter->filter_type_id === FilterTypes::DEPARTMENT->value) {
                $query = $this->addDepartmentFilter($query, $whereInCondition, $collectionFilter->departments);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::SEASON->value) {
                $query = $this->addSeasonFilter($query, $whereInCondition, $collectionFilter->seasons);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::COLOR->value) {
                $query = $this->addColorFilter($query, $whereInCondition, $collectionFilter->colors);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::SIZE->value) {
                $query = $this->addSizeFilter($query, $whereInCondition, $collectionFilter->sizes);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::BRAND->value) {
                $query = $this->addBrandFilter($query, $whereInCondition, $collectionFilter->brands);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::STYLE->value) {
                $query = $this->addStyleFilter($query, $whereInCondition, $collectionFilter->styles);
            }

            if ($collectionFilter->filter_type_id === FilterTypes::TYPE->value) {
                return $this->addTypeFilter($query, $whereInCondition, $collectionFilter->types);
            }
        }

        return $query;
    }

    private function addWhereAndWhereAnyConditionFilter(
        Builder $query,
        string $whereCondition,
        string $whereAnyCondition,
        ProductCollectionFilter $collectionFilter
    ): Builder {
        if (config('app.product_variant')) {
            if ($collectionFilter->filter_type_id === VariantFilterTypes::NAME->value &&
                null !== $collectionFilter->value &&
                null !== $collectionFilter->condition_operator_type_id
            ) {
                return $this->addNameConditionFilter(
                    $query,
                    $collectionFilter->condition_operator_type_id,
                    $whereCondition,
                    $whereAnyCondition,
                    $collectionFilter->value
                );
            }
        } elseif ($collectionFilter->filter_type_id === FilterTypes::NAME->value &&
            null !== $collectionFilter->value &&
            null !== $collectionFilter->condition_operator_type_id) {
            return $this->addNameConditionFilter(
                $query,
                $collectionFilter->condition_operator_type_id,
                $whereCondition,
                $whereAnyCondition,
                $collectionFilter->value
            );
        }

        return $query;
    }

    public function getProductCollectionByIdAndCompanyId(int $productCollectionId, int $companyId): ProductCollection
    {
        return ProductCollection::select('name')
            ->where('company_id', $companyId)
            ->findOrFail($productCollectionId);
    }

    public function refresh(ProductCollection $productCollection): ProductCollection
    {
        return $productCollection->refresh();
    }

    public function getProductCollectionNameForFilter(array $productCollectionIds): string
    {
        $productCollectionData = [];
        $productCollection = ProductCollection::select('name')
            ->whereIntegerInRaw('id', values: $productCollectionIds)
            ->get();

        if ($productCollection->isNotEmpty()) {
            $productCollectionData = $productCollection->pluck('name')->toArray();
        }

        return implode(', ', $productCollectionData);
    }

    public function getProductCollectionByIdForEcommerce(int $productCollectionId): ProductCollection
    {
        return ProductCollection::select('id', 'company_id')->findOrFail($productCollectionId);
    }

    public function validateProductCollectionSaleChannelMatch(
        ProductCollection $productCollection,
        SaleChannel $saleChannel
    ): bool {
        return $productCollection->saleChannels()
            ->wherePivot('sale_channel_id', $saleChannel->id)
            ->exists();
    }

    public function getProductCollectionsForECommerce(int $companyId): array
    {
        return ProductCollection::query()
            ->select('id', 'name')
            ->where('is_available_in_ecommerce', true)
            ->where('status', true)
            ->where('company_id', $companyId)
            ->get()
            ->toArray();
    }
}
