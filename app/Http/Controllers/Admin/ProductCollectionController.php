<?php

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ProductCollection\DataObjects\ProductCollectionData;
use App\Domains\ProductCollection\DataObjects\ProductCollectionImagesData;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\Jobs\CreateUpdateProductCollectionJob;
use App\Domains\ProductCollection\Jobs\ProductCollectionsSyncJob;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\ProductCollectionResource;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilter\Enums\VariantFilterTypes;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\ProductCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductCollectionController extends Controller
{
    public function __construct(
        protected ProductCollectionQueries $productCollectionQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithAutoIncludeInCollectionsById($companyId);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->isAvailable($companyId);

        return Inertia::render('product_collection/Index', [
            'statuses' => Status::getStatuses(),
            'productCollectionModelMappingType' => ModelMapping::PRODUCT_COLLECTION->name,
            'autoIncludeFlag' => $company->auto_include_in_collections,
            'saleChannel' => $saleChannel,
        ]);
    }

    public function fetchProductCollections(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
        ];
        $companyId = session('admin_company_id');
        $lengthAwarePaginator = $this->productCollectionQueries->listQuery($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ProductCollectionResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('product_collection/Manage', $this->commonData());
    }

    public function store(ProductCollectionData $productCollectionData): RedirectResponse
    {
        /** @var Admin $user */
        $user = auth()->user();

        $importRecordQueries = resolve(ImportRecordQueries::class);

        $companyId = session('admin_company_id');
        DB::beginTransaction();

        try {
            $productCollection = $this->productCollectionQueries->addNew(
                $user,
                $productCollectionData,
                $companyId,
            );

            $importRecord = $importRecordQueries->addNewForProductCollection(
                ImportTypes::PRODUCT_COLLECTION->value,
                $user,
                $companyId,
                $productCollection,
            );

            DB::commit();

            CreateUpdateProductCollectionJob::dispatch($productCollection->id, $companyId, $importRecord->id)->onQueue(
                'medium'
            );

            return to_route('admin.product_collections.index')
                ->with('success', 'Product collection added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Store Product Collection', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function changeStatus(Request $request): void
    {
        $this->productCollectionQueries->changeStatus($request->productCollectionId);
    }

    public function delete(int $productCollectionId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $this->productCollectionQueries->delete($productCollectionId, $companyId);

        return to_route('admin.product_collections.index')->with('success', 'Product Collection deleted successfully.');
    }

    public function edit(int $productCollectionId): Response
    {
        $companyId = session('admin_company_id');
        $productCollection = $this->productCollectionQueries->edit($productCollectionId, $companyId);

        return Inertia::render('product_collection/Manage', [
            'productCollection' => $this->preparedProductCollectionData($productCollection),
            ...$this->commonData(),
        ]);
    }

    public function update(ProductCollectionData $productCollectionData, int $productCollectionId): RedirectResponse
    {
        /** @var Admin $user */
        $user = auth()->user();

        $companyId = session('admin_company_id');
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $productCollection = $this->productCollectionQueries->getByIdWithRelation($productCollectionId);

        /** @var ImportRecord $importRecord */
        $importRecord = $productCollection->importRecord;
        if ($importRecord instanceof ImportRecord && $importRecord->status !== Status::COMPLETED->value) {
            throw new RedirectBackWithErrorException('You cannot update while the process is in progress.');
        }

        DB::beginTransaction();

        try {
            $productCollection = $this->productCollectionQueries->update($productCollectionData, $productCollectionId);

            $productCollectionProductQueries->removeByProductCollectionId($productCollectionId, $companyId);

            $importRecord = $importRecordQueries->addNewForProductCollection(
                ImportTypes::PRODUCT_COLLECTION->value,
                $user,
                $companyId,
                $productCollection,
            );

            DB::commit();

            CreateUpdateProductCollectionJob::dispatch($productCollectionId, $companyId, $importRecord->id)->onQueue(
                'medium'
            );

            return to_route('admin.product_collections.index')
                ->with('success', 'Product collection updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Product Collection', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function syncProductCollections(): void
    {
        /** @var Admin $user */
        $user = auth()->user();

        $importRecordQueries = resolve(ImportRecordQueries::class);
        $companyId = session('admin_company_id');

        $productCollections = $this->productCollectionQueries->getProductCollections($companyId);
        foreach ($productCollections as $productCollection) {
            $importRecord = $importRecordQueries->addNewForProductCollection(
                ImportTypes::PRODUCT_COLLECTION->value,
                $user,
                $companyId,
                $productCollection,
            );

            ProductCollectionsSyncJob::dispatch(
                $productCollection->id,
                $importRecord->company_id,
                $importRecord->id
            )->onQueue('medium');
        }
    }

    public function manageMediaView(int $productCollectionId): Response
    {
        $productCollection = $this->productCollectionQueries->getById($productCollectionId);
        $productCollection = [
            'id' => $productCollection->id,
            'name' => $productCollection->name,
            'square_url' => $productCollection->getDiskBasedFirstMediaUrl('square_image'),
            'portrait_urls' => $productCollection->getDiskBasedMediaUrls('portrait_images'),
            'landscape_urls' => $productCollection->getDiskBasedMediaUrls('landscape_images'),
        ];

        return Inertia::render('product_collection/ManageMedia', [
            'productCollection' => $productCollection,
        ]);
    }

    public function uploadImages(
        ProductCollectionImagesData $productCollectionImageData,
        int $productCollectionId
    ): RedirectResponse {
        $productCollection = $this->productCollectionQueries->getById($productCollectionId);
        $this->productCollectionQueries->uploadImages($productCollection, $productCollectionImageData);

        return to_route('admin.product_collections.index')
                ->with('success', 'Images upload successfully.');
    }

    public function removePortraitImage(int $productCollectionId, int $mediaId): void
    {
        $this->productCollectionQueries->removeImage($productCollectionId, $mediaId, 'portrait_images');
    }

    public function removeLandscapeImage(int $productCollectionId, int $mediaId): void
    {
        $this->productCollectionQueries->removeImage($productCollectionId, $mediaId, 'landscape_images');
    }

    public function getFilteredProductCollections(Request $request): array
    {
        return [
            'productCollections' => $this->productCollectionQueries->getFilteredProductCollectionsByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function syncData(): void
    {
        // ToDo: Add Job For sync data
    }

    private function commonData(): array
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $companyId = session('admin_company_id');
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAll($companyId);

            return [
                'attributes' => $attributes,
                'filterTypes' => VariantFilterTypes::formattedForSelection(),
                'logicalConnectorTypes' => LogicalConnectorTypes::formattedForSelection(),
                'categories' => $categoryQueries->getParentByCompanyId($companyId),
                'departments' => $departmentQueries->getWithBasicColumns($companyId),
                'brands' => $brandQueries->getWithBasicColumns(),
                'tags' => $tagQueries->getWithBasicColumns($companyId),
                'types' => ProductTypes::formattedForSelection(),
                'saleChannels' => $saleChannelQueries->getAllByCompanyId($companyId),
                'conditionOperatorTypes' => [
                    'contains' => [
                        'id' => ConditionOperatorTypes::CONTAINS->value,
                        'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::CONTAINS->name),
                    ],
                    'lessThan' => [
                        'id' => ConditionOperatorTypes::LESS_THAN->value,
                        'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::LESS_THAN->name),
                    ],
                    'greaterThan' => [
                        'id' => ConditionOperatorTypes::GREATER_THAN->value,
                        'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::GREATER_THAN->name),
                    ],
                    'equal' => [
                        'id' => ConditionOperatorTypes::EQUAL->value,
                        'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::EQUAL->name),
                    ],
                ],
                'staticDetails' => [
                    'name' => VariantFilterTypes::NAME->value,
                    'category' => VariantFilterTypes::CATEGORY->value,
                    'department' => VariantFilterTypes::DEPARTMENT->value,
                    'brand' => VariantFilterTypes::BRAND->value,
                    'tags' => VariantFilterTypes::TAG->value,
                    'price' => VariantFilterTypes::PRICE->value,
                    'type' => VariantFilterTypes::TYPE->value,
                    'is_available_in_pos' => VariantFilterTypes::IS_AVAILABLE_IN_POS->value,
                    'is_available_in_ecommerce' => VariantFilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value,
                    'created_by' => VariantFilterTypes::CREATED_BY->value,
                    'sale_unit_sold' => VariantFilterTypes::SALE_UNIT_SOLD->value,
                    'sale_amount' => VariantFilterTypes::SALE_AMOUNT->value,
                    'order_unit_sold' => FilterTypes::ORDER_UNIT_SOLD->value,
                    'order_amount' => VariantFilterTypes::ORDER_AMOUNT->value,
                    'attribute' => VariantFilterTypes::ATTRIBUTES->value,
                ],
            ];
        }

        return [
            'filterTypes' => FilterTypes::formattedForSelection(),
            'logicalConnectorTypes' => LogicalConnectorTypes::formattedForSelection(),
            'categories' => $categoryQueries->getParentByCompanyId($companyId),
            'seasons' => $seasonQueries->getWithBasicColumns($companyId),
            'departments' => $departmentQueries->getWithBasicColumns($companyId),
            'colors' => $colorQueries->getWithBasicColumns($companyId),
            'sizes' => $sizeQueries->getWithBasicColumns($companyId),
            'brands' => $brandQueries->getWithBasicColumns(),
            'styles' => $styleQueries->getWithBasicColumns($companyId),
            'saleChannels' => $saleChannelQueries->getAllByCompanyId($companyId),
            'tags' => $tagQueries->getWithBasicColumns($companyId),
            'types' => ProductTypes::formattedForSelection(),
            'conditionOperatorTypes' => [
                'contains' => [
                    'id' => ConditionOperatorTypes::CONTAINS->value,
                    'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::CONTAINS->name),
                ],
                'lessThan' => [
                    'id' => ConditionOperatorTypes::LESS_THAN->value,
                    'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::LESS_THAN->name),
                ],
                'greaterThan' => [
                    'id' => ConditionOperatorTypes::GREATER_THAN->value,
                    'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::GREATER_THAN->name),
                ],
                'equal' => [
                    'id' => ConditionOperatorTypes::EQUAL->value,
                    'name' => CommonFunctions::stringTitleLowerCase(ConditionOperatorTypes::EQUAL->name),
                ],
            ],
            'staticDetails' => [
                'name' => FilterTypes::NAME->value,
                'category' => FilterTypes::CATEGORY->value,
                'season' => FilterTypes::SEASON->value,
                'department' => FilterTypes::DEPARTMENT->value,
                'color' => FilterTypes::COLOR->value,
                'size' => FilterTypes::SIZE->value,
                'brand' => FilterTypes::BRAND->value,
                'style' => FilterTypes::STYLE->value,
                'tags' => FilterTypes::TAG->value,
                'price' => FilterTypes::PRICE->value,
                'type' => FilterTypes::TYPE->value,
                'is_available_in_pos' => FilterTypes::IS_AVAILABLE_IN_POS->value,
                'is_available_in_ecommerce' => FilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value,
                'created_by' => FilterTypes::CREATED_BY->value,
                'sale_unit_sold' => FilterTypes::SALE_UNIT_SOLD->value,
                'sale_amount' => FilterTypes::SALE_AMOUNT->value,
                'order_unit_sold' => FilterTypes::ORDER_UNIT_SOLD->value,
                'order_amount' => FilterTypes::ORDER_AMOUNT->value,
            ],
        ];
    }

    private function preparedProductCollectionData(ProductCollection $productCollection): array
    {
        $collectionFilters = $productCollection->productCollectionFilter;
        $filterArray = [];
        foreach ($collectionFilters as $collectionFilter) {
            $filterTypeId = $collectionFilter->filter_type_id;
            $conditionOperatorId = $collectionFilter->condition_operator_type_id;
            $value = $collectionFilter->value ?? null;

            if (config('app.product_variant')) {
                switch ($filterTypeId) {
                    case VariantFilterTypes::CATEGORY->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'categories',
                            $collectionFilter->categories,
                            $conditionOperatorId
                        );
                        break;

                    case VariantFilterTypes::DEPARTMENT->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'departments',
                            $collectionFilter->departments,
                            $conditionOperatorId
                        );
                        break;

                    case VariantFilterTypes::BRAND->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'brands',
                            $collectionFilter->brands,
                            $conditionOperatorId
                        );
                        break;

                    case VariantFilterTypes::TAG->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'tags',
                            $collectionFilter->tags,
                            $conditionOperatorId
                        );
                        break;

                    case VariantFilterTypes::NAME->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'name',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case VariantFilterTypes::CREATED_BY->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'created_by',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case VariantFilterTypes::SALE_UNIT_SOLD->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'sale_unit_sold' => (float) $value,
                            ];
                        }

                        break;

                    case VariantFilterTypes::SALE_AMOUNT->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'sale_amount' => (float) $value,
                            ];
                        }

                        break;

                    case VariantFilterTypes::ORDER_UNIT_SOLD->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'order_unit_sold' => (float) $value,
                            ];
                        }

                        break;

                    case VariantFilterTypes::ORDER_AMOUNT->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'order_amount' => (float) $value,
                            ];
                        }

                        break;

                    case VariantFilterTypes::PRICE->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'price',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case VariantFilterTypes::IS_AVAILABLE_IN_POS->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'is_available_in_pos',
                                $value,
                                null
                            );
                        }

                        break;

                    case VariantFilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'is_available_in_ecommerce',
                                $value,
                                null
                            );
                        }

                        break;

                    case VariantFilterTypes::TYPE->value:
                        $filterArray[] = [
                            'filter_type_id' => $filterTypeId,
                            'condition_operator_id' => null,
                            'types' => $collectionFilter->types->map(fn ($item): array => [
                                'id' => $item->type_id,
                                'name' => $this->getProductTypeName($item->type_id),
                            ])->toArray(),
                        ];
                        break;

                    case VariantFilterTypes::ATTRIBUTES->value:
                        $filterArray[] = $this->mapFilterForAttributeWithItems(
                            $filterTypeId,
                            $collectionFilter->attributeValues,
                        );
                        break;
                }
            } else {
                switch ($filterTypeId) {
                    case FilterTypes::CATEGORY->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'categories',
                            $collectionFilter->categories,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::SEASON->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'seasons',
                            $collectionFilter->seasons,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::DEPARTMENT->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'departments',
                            $collectionFilter->departments,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::BRAND->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'brands',
                            $collectionFilter->brands,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::COLOR->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'colors',
                            $collectionFilter->colors,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::SIZE->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'sizes',
                            $collectionFilter->sizes,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::STYLE->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'styles',
                            $collectionFilter->styles,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::TAG->value:
                        $filterArray[] = $this->mapFilterWithItems(
                            $filterTypeId,
                            'tags',
                            $collectionFilter->tags,
                            $conditionOperatorId
                        );
                        break;

                    case FilterTypes::NAME->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'name',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case FilterTypes::CREATED_BY->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'created_by',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case FilterTypes::SALE_UNIT_SOLD->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'sale_unit_sold' => (float) $value,
                            ];
                        }

                        break;

                    case FilterTypes::SALE_AMOUNT->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'sale_amount' => (float) $value,
                            ];
                        }

                        break;

                    case FilterTypes::ORDER_UNIT_SOLD->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'order_unit_sold' => (float) $value,
                            ];
                        }

                        break;

                    case FilterTypes::ORDER_AMOUNT->value:
                        if (null !== $value) {
                            $filterArray[] = [
                                'filter_type_id' => $filterTypeId,
                                'condition_operator_id' => $conditionOperatorId,
                                'order_amount' => (float) $value,
                            ];
                        }

                        break;

                    case FilterTypes::PRICE->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'price',
                                $value,
                                $conditionOperatorId
                            );
                        }

                        break;

                    case FilterTypes::IS_AVAILABLE_IN_POS->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'is_available_in_pos',
                                $value,
                                null
                            );
                        }

                        break;

                    case FilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value:
                        if (null !== $value) {
                            $filterArray[] = $this->mapFilterWithValue(
                                $filterTypeId,
                                'is_available_in_ecommerce',
                                $value,
                                null
                            );
                        }

                        break;

                    case FilterTypes::TYPE->value:
                        $filterArray[] = [
                            'filter_type_id' => $filterTypeId,
                            'condition_operator_id' => null,
                            'types' => $collectionFilter->types->map(fn ($item): array => [
                                'id' => $item->type_id,
                                'name' => $this->getProductTypeName($item->type_id),
                            ])->toArray(),
                        ];
                        break;
                }
            }
        }

        return [
            'id' => $productCollection->id,
            'name' => $productCollection->name,
            'logical_connector_type_id' => $productCollection->logical_connector_type_id->value,
            'collection_filter_types' => $filterArray,
            'is_available_in_ecommerce' => $productCollection->is_available_in_ecommerce,
            'sale_channels' => $productCollection->saleChannels,
        ];
    }

    private function mapFilterWithItems(
        int $filterTypeId,
        string $key,
        Collection $items,
        ?int $conditionOperatorId = null
    ): array {
        return [
            'filter_type_id' => $filterTypeId,
            'condition_operator_id' => $conditionOperatorId,
            $key => $items->map(fn ($item) => $item->only(['id', 'name']))->toArray(),
        ];
    }

    private function mapFilterForAttributeWithItems(
        int $filterTypeId,
        Collection $items,
        ?int $conditionOperatorId = null
    ): array {
        return [
            'filter_type_id' => $filterTypeId,
            'condition_operator_id' => $conditionOperatorId,
            'attributes' => collect($items)
                ->groupBy('attribute_id')
                ->map(function ($items): array {
                    $attribute = $items->first()->attribute;

                    /** @var Collection options */
                    $options = $attribute->options;

                    return [
                        'attribute' => $attribute->id,
                        'attribute_selected_values' => $items->map(fn ($items): array => [
                            'id' => $items->value,
                            'name' => $items->value,
                        ])->values()->toArray(),
                        'attribute_values' => collect($options)->map(fn (string $name): array => [
                            'id' => $name,
                            'name' => $name,
                        ])->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray(),
        ];
    }

    private function mapFilterWithValue(
        int $filterTypeId,
        string $key,
        string $value,
        ?int $conditionOperatorId = null
    ): array {
        return [
            'filter_type_id' => $filterTypeId,
            'condition_operator_id' => $conditionOperatorId,
            $key => $value,
        ];
    }

    private function getProductTypeName(int $typeId): string
    {
        return match ($typeId) {
            ProductTypes::REGULAR_PRODUCT->value => 'Regular Product',
            ProductTypes::SPECIAL_ORDER->value => 'Special Order',
            ProductTypes::CUSTOM_ORDER->value => 'Custom Order',
            ProductTypes::POSTAGE_COST->value => 'Postage Cost',
            ProductTypes::ASSEMBLY_PRODUCT->value => 'Assembly Product',
            default => '',
        };
    }
}
