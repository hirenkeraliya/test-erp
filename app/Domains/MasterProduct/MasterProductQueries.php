<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct;

use App\CommonFunctions;
use App\Domains\AssemblyMasterProduct\AssemblyChildMasterProductQueries;
use App\Domains\AttachedTemplate\AttachedTemplateQueries;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\BoxProductLoyaltyPoint\BoxProductLoyaltyPointQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CustomFieldValue\CustomFieldValueQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\DataObjects\MasterProductImageUploadData;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\ProductVariantValue\Services\ProductVariantValueService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\MasterProduct;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Spatie\LaravelData\DataCollection;

class MasterProductQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $brandQueries = new BrandQueries();
        $categoryQueries = new CategoryQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        return MasterProduct::query()
            ->select(
                'id',
                'name',
                'code',
                'description',
                'brand_id',
                'vendor_id',
                'department_id',
                'unit_of_measure_id',
                'article_number',
                'type_id',
                'has_batch',
                'is_non_inventory',
                'is_non_selling_item',
                'original_created_at',
                'created_by_id',
                'created_by_type',
                'status',
                'created_at',
                'updated_at',
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $query
                        ->whereAny(
                            ['name', 'code', 'article_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('brand', $brandQueries->searchByName($filterData['search_text']))
                        ->orWhereHas('categories', $categoryQueries->searchByName($filterData['search_text']));
                });
            })
            ->where('company_id', $companyId)
            ->whereNot('status', Statuses::DRAFT->value)
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
                $query->where('type_id', (int) $filterData['product_type_id']);
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('article_number', (array) $filterData['article_numbers']);
            })
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                $query->where('has_batch', true);
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                $query->where('has_batch', false);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(MasterProductData $masterProductData, int $companyId, User $user): void
    {
        $masterProductDetails = $masterProductData->all();
        $masterProductDetails['company_id'] = $companyId;
        $masterProductDetails['created_by_id'] = $user->id;
        $masterProductDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $masterProductDetails['status'] = Statuses::DRAFT->value;

        if ((int) $masterProductData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $masterProductDetails['is_non_inventory'] = true;
        }

        unset($masterProductDetails['tag_ids'], $masterProductDetails['category_ids'], $masterProductDetails['thumbnail'], $masterProductDetails['images'], $masterProductDetails['videos'], $masterProductDetails['custom_field_values'], $masterProductDetails['attached_templates'], $masterProductDetails['variants'], $masterProductDetails['assembly_child_master_products']);

        $masterProduct = MasterProduct::create($masterProductDetails);
        $this->updateCategories($masterProduct, $masterProductData->category_ids);
        $this->updateTags($masterProduct, $masterProductData->tag_ids);
        $this->updateAssemblyItems($masterProduct, $masterProductData);
        $this->uploadPhoto($masterProduct, $masterProductData);
        $this->uploadVideo($masterProduct, $masterProductData);
        $this->uploadOtherImages($masterProduct, $masterProductData);
        $this->uploadVariants($masterProduct, $masterProductData);
        $this->createOrUpdateCustomFieldValues($masterProduct, $masterProductData);
    }

    public function getByIdWithMediaCategoriesAndTags(int $masterProductId, int $companyId): MasterProduct
    {
        $statuses = [Statuses::ACTIVE->value];

        return $this->commonQueryForEditItem($companyId, $masterProductId, $statuses);
    }

    public function getByIdWithMediaCategoriesAndTagsAndStatuses(int $masterProductId, int $companyId): MasterProduct
    {
        $statuses = [Statuses::DRAFT->value, Statuses::ACTIVE->value];

        return $this->commonQueryForEditItem($companyId, $masterProductId, $statuses);
    }

    public function checkDraftProduct(int $productId, int $companyId): bool
    {
        return MasterProduct::where('id', $productId)
            ->where('company_id', $companyId)
            ->where('status', Statuses::DRAFT->value)
            ->exists();
    }

    public function update(MasterProductData $masterProductData, int $masterProductId, int $companyId): void
    {
        $masterProduct = $this->getById($masterProductId, $companyId);
        $masterProductDetails = $masterProductData->all();
        unset($masterProductDetails['tag_ids'], $masterProductDetails['category_ids'], $masterProductDetails['images'], $masterProductDetails['videos'],  $masterProductDetails['thumbnail'], $masterProductDetails['custom_field_values'], $masterProductDetails['attached_templates'], $masterProductDetails['sale_channel_ids'], $masterProductDetails['assembly_child_master_products'], $masterProductDetails['variants']);

        if ($masterProduct->unit_of_measure_id && ! config('app.update_unit_of_measure')) {
            unset($masterProductDetails['unit_of_measure_id']);
        }

        if ((int) $masterProductData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $masterProductDetails['is_non_inventory'] = true;
        }

        $masterProduct->update($masterProductDetails);

        $this->updateCategories($masterProduct, $masterProductData->category_ids);
        $this->updateTags($masterProduct, $masterProductData->tag_ids);
        $this->uploadPhoto($masterProduct, $masterProductData);
        $this->uploadVideo($masterProduct, $masterProductData);
        $this->updateAssemblyItems($masterProduct, $masterProductData);
        $this->uploadOtherImages($masterProduct, $masterProductData);
        $this->uploadVariants($masterProduct, $masterProductData);
        $this->createOrUpdateCustomFieldValues($masterProduct, $masterProductData);
        $this->setUpdatedAt($masterProduct);

        ProductCollectionUpdateByProductJob::dispatch($masterProduct->id, $companyId)->onQueue(
            config('horizon.default_queue_name')
        );
    }

    public function updateStatus(int $masterProductId, int $companyId): void
    {
        $masterProduct = $this->getById($masterProductId, $companyId);
        $masterProduct->status = Statuses::ACTIVE->value;
        $masterProduct->save();
    }

    public function getMasterProductsWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        return $this->masterProductLists($filterData, $companyId)->get();
    }

    public function getById(int $masterProductId, int $companyId): MasterProduct
    {
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return MasterProduct::select(
            'id',
            'name',
            'description',
            'code',
            'unit_of_measure_id',
            'company_id',
            'department_id',
            'brand_id',
            'article_number',
            'vendor_id',
            'unit_of_measure_id',
            'type_id',
            'has_batch',
            'original_created_at',
            'is_non_inventory',
            'is_non_selling_item',
            'variant_template_id',
            'status'
        )
            ->with(['assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('status', [Statuses::ACTIVE->value, Statuses::DRAFT->value])
            ->findOrFail($masterProductId);
    }

    public function getByIdWithTrash(int $masterProductId, int $companyId): MasterProduct
    {
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);

        return MasterProduct::select(
            'id',
            'name',
            'description',
            'code',
            'unit_of_measure_id',
            'company_id',
            'department_id',
            'brand_id',
            'article_number',
            'vendor_id',
            'unit_of_measure_id',
            'type_id',
            'has_batch',
            'original_created_at',
            'is_non_inventory',
            'is_non_selling_item',
            'variant_template_id',
            'status'
        )
            ->withTrashed()
            ->with(['assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('status', [Statuses::ACTIVE->value, Statuses::DRAFT->value])
            ->findOrFail($masterProductId);
    }

    public function uploadImage(MasterProductImageUploadData $masterProductImageUploadData, int $companyId): void
    {
        $masterProduct = MasterProduct::select('id')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->findOrFail($masterProductImageUploadData->master_product_id);

        $masterProduct->addMedia($masterProductImageUploadData->image)->toMediaCollection('thumbnail');
    }

    public function removeMasterProductImage(int $masterProductId, int $mediaId): void
    {
        $masterProduct = MasterProduct::query()
            ->select('id')
            ->findOrFail($masterProductId);

        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $masterProduct->getMedia('images')->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function removeMasterProductVideo(int $masterProductId, int $mediaId): void
    {
        $masterProduct = MasterProduct::query()
            ->select('id')
            ->findOrFail($masterProductId);
        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $masterProduct->getMedia('videos')->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function removeMasterProductThumbnail(int $masterProductId): void
    {
        $masterProduct = MasterProduct::query()
            ->select('id')
            ->findOrFail($masterProductId);

        // We are using directly clearMediaCollection function because here we are not playing with the file we are just deleting a records. And, Spatie media library will taken care of it.
        $masterProduct->clearMediaCollection('thumbnail');
    }

    public function setUpdatedAt(MasterProduct $masterProduct): void
    {
        $masterProduct->touch();
    }

    public function getAllBasicColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'description',
            'brand_id',
            'vendor_id',
            'unit_of_measure_id',
            'department_id',
            'article_number',
            'type_id',
            'has_batch',
            'is_non_inventory',
            'is_non_selling_item',
            'original_created_at',
            'created_by_id',
            'created_by_type',
            'status',
            'variant_template_id',
            'created_at',
            'updated_at',
        ];
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function getActiveFilteredRegularMasterProducts(array $filterData, int $companyId): Collection
    {
        return $this->getActiveFilteredMasterProductsQuery($filterData, $companyId)
            ->where('is_non_inventory', false)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->get();
    }

    public function getActiveRegularMasterProductsFilteredByNameBrandAndCategory(
        array $filterData,
        int $companyId
    ): Collection {
        return $this->getActiveMasterProductsFilteredByNameBrandAndCategoryQuery($filterData, $companyId)
            ->where('is_non_inventory', false)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->get();
    }

    public function getActiveMasterProductsFilteredByNameBrandAndCategoryQuery(
        array $filterData,
        int $companyId
    ): Builder {
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return MasterProduct::query()
            ->select('id', 'name', 'brand_id', 'has_batch', 'article_number', 'unit_of_measure_id')
            ->with(
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchByNameWithArticleNumber($filterData))
            ->when($filterData['category_id'], function ($query) use ($categoryQueries, $filterData, $companyId): void {
                $query->whereHas(
                    'categories',
                    $categoryQueries->filterByIdAndCompany($companyId, (int) $filterData['category_id'])
                );
            })->when($filterData['brand_id'], function ($query) use ($filterData): void {
                $query->where('brand_id', $filterData['brand_id']);
            })
            ->onlyActive()
            ->orderBy('name');
    }

    public function getActiveFilteredMasterProductsQuery(array $filterData, int $companyId): Builder
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return MasterProduct::query()
            ->select('id', 'name', 'has_batch', 'unit_of_measure_id', 'article_number')
            ->with([
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], $this->searchByNameWithArticleNumber($filterData))
            ->when($filterData['number_of_records'], function ($query) use ($filterData): void {
                $query->limit($filterData['number_of_records']);
            })
            ->onlyActive()
            ->orderBy('name');
    }

    public function searchByNameWithArticleNumber(array $filterData): Closure
    {
        return fn ($query) => $query
            ->where(function ($query) use ($filterData): void {
                $query
                    ->whereAny(['article_number'], 'LIKE', '%' . $filterData['search_text'] . '%')
                    ->orWhere(function ($query) use ($filterData): void {
                        $names = array_filter(explode(' ', $filterData['search_text']));

                        foreach ($names as $name) {
                            $query->where('name', 'like', '%' . $name . '%');
                        }
                    });
            });
    }

    public function getActiveMasterProductWithBasicColumnsById(int $masterProductId, int $companyId): MasterProduct
    {
        return MasterProduct::select('id', DB::raw('master_products.name as name'))
            ->onlyActive()
            ->where('company_id', $companyId)
            ->findOrFail($masterProductId);
    }

    public function getBasicColumnsForInventory(): string
    {
        return 'id,name,code,type_id,is_non_inventory,unit_of_measure_id,has_batch,is_non_selling_item,article_number';
    }

    public function getBasicColumnsForSync(): string
    {
        return 'id,name,description,code,article_number,status,brand_id';
    }

    public function searchByArticleNumber(string $articleNumber, int $companyId): ?MasterProduct
    {
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return MasterProduct::select('id', 'name', 'variant_template_id', 'has_batch')
            ->onlyActive()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->with([
                'productVariants' => function ($query): void {
                    $query->select('id', 'name', 'master_product_id', 'compound_product_name')
                        ->where('status', Statuses::ACTIVE->value);
                },
                'productVariants.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariants.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->first();
    }

    public function searchByArticleNumberWithNonInventory(string $articleNumber, int $companyId): ?MasterProduct
    {
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return MasterProduct::select('id', 'name', 'variant_template_id', 'has_batch', 'unit_of_measure_id')
            ->with([
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->onlyActive()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->where('is_non_inventory', false)
            ->with([
                'productVariants' => function ($query): void {
                    $query->select('id', 'name', 'master_product_id', 'compound_product_name')
                        ->where('status', Statuses::ACTIVE->value);
                },
                'productVariants.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariants.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->first();
    }

    public function searchArticleNumber(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('article_number', 'like', '%' . $searchText . '%');
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,brand_id,article_number,department_id,variant_template_id,vendor_id,code,description,created_by_type,created_by_id,brand_id,unit_of_measure_id,type_id,has_batch,is_non_inventory,is_non_selling_item,status,created_at,updated_at';
    }

    public function getFilteredArticleNumberByCompanyId(string $searchText, int $companyId): LazyCollection
    {
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

    public function firstOrCreateWithRelations(array $productData, int $productId): ?MasterProduct
    {
        if (
            isset($productData['master_product'], $productData['product_variant_values']) &&
            ! empty($productData['master_product']) &&
            ! empty($productData['product_variant_values'])
        ) {
            $this->firstOrCreateProductVariantAndAttribute($productData, $productId);

            return $this->firstOrCreate($productData);
        }

        return null;
    }

    public function createOrUpdateFromProduct(Product $product, ProductData $productData): void
    {
        $productVariantValueService = resolve(ProductVariantValueService::class);
        $defaultTemplate = $productVariantValueService->createColorSizeStyleProductVariantValues($product);

        $thumbnailUrl = $product->getDiskBasedFirstMediaUrl('thumbnail');
        $images = $product->getDiskBasedMediaUrls('images');
        $videos = $product->getDiskBasedMediaUrls('videos');

        if ($product->master_product_id) {
            $masterProduct = $this->getByIdWithTrash($product->master_product_id, $product->company_id);
        } else {
            $masterProduct = $this->getByArticleNumberAndCompanyIdWithTrash(
                $product->article_number,
                $product->company_id
            );
        }

        $masterProductData = [
            'company_id' => $product->company_id,
            'brand_id' => $product->brand_id,
            'variant_template_id' => $defaultTemplate->id,
            'name' => $product->name,
            'code' => $product->code,
            'description' => $product->description,
            'department_id' => $product->department_id,
            'vendor_id' => $product->vendor_id,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'article_number' => $product->article_number,
            'type_id' => $product->type_id,
            'has_batch' => $product->has_batch,
            'is_non_inventory' => $product->is_non_inventory,
            'is_non_selling_item' => $product->is_non_selling_item,
            'created_by_id' => $product->created_by_id,
            'created_by_type' => $product->created_by_type,
            'status' => Statuses::ACTIVE->value,
        ];

        if ($masterProduct instanceof MasterProduct) {
            $masterProduct->update($masterProductData);
        } else {
            $masterProduct = MasterProduct::create($masterProductData);

            $this->updateCategories($masterProduct, $productData->category_ids);
            $this->updateTags($masterProduct, (array) $productData->tag_ids);
            $this->uploadPhoto($masterProduct, $productData, $images);
            $this->uploadVideo($masterProduct, $productData, $videos);
            $this->uploadOtherImages($masterProduct, $productData, $thumbnailUrl);
            $this->createOrUpdateCustomFieldValues($masterProduct, $productData);
        }

        $product->master_product_id = $masterProduct->id ?? null;
        $product->save();
    }

    public function filterByDepartmentIds(array $departmentIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('department_id', $departmentIds);
    }

    public function filterByBrandIds(array $brandIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('brand_id', $brandIds);
    }

    public function getMasterProductsArticleNumberForEcommerce(int $companyId): Collection
    {
        return MasterProduct::select('id', 'article_number')
            ->whereHas('productVariants', function ($query): void {
                $query->select('id', 'master_product_id')->where('is_available_in_ecommerce', true);
            })
            ->where('company_id', $companyId)
            ->get();
    }

    private function firstOrCreateProductVariantAndAttribute(array $productData, int $productId): void
    {
        $attributeQueries = resolve(AttributeQueries::class);

        foreach ($productData['product_variant_values'] as $productVariantValue) {
            $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
            $attributeQueries = resolve(AttributeQueries::class);

            $attributeId = $attributeQueries->firstOrCreate(
                $productVariantValue['attribute'],
                $productData['company_id'],
                $productData['template_id']
            );
            $productVariantValueQueries->firstOrCreate($productVariantValue, $attributeId, $productId);
        }
    }

    private function firstOrCreate(array $productData): MasterProduct
    {
        $masterProductFindByArticleNumber = MasterProduct::where('company_id', $productData['company_id'])
            ->where('article_number', $productData['master_product']['article_number'])
            ->first();

        if ($masterProductFindByArticleNumber) {
            return $masterProductFindByArticleNumber;
        }

        $masterProductFindByName = MasterProduct::where('company_id', $productData['company_id'])
            ->where('name', $productData['master_product']['name'])
            ->first();

        if ($masterProductFindByName) {
            return $masterProductFindByName;
        }

        $masterProduct = MasterProduct::create([
            'company_id' => $productData['company_id'],
            'brand_id' => $productData['brand_id'],
            'variant_template_id' => $productData['template_id'],
            'name' => $productData['master_product']['name'],
            'code' => $productData['master_product']['code'],
            'description' => $productData['master_product']['description'],
            'department_id' => $productData['department_id'],
            'unit_of_measure_id' => $productData['unit_of_measure_id'],
            'article_number' => $productData['master_product']['article_number'],
            'type_id' => $productData['master_product']['type_id'],
            'has_batch' => $productData['master_product']['has_batch'],
            'is_non_inventory' => $productData['master_product']['is_non_inventory'],
            'is_non_selling_item' => $productData['master_product']['is_non_selling_item'],
            'status' => Statuses::ACTIVE->value,
        ]);

        $this->updateCategories($masterProduct, $productData['category_ids']);
        $this->updateTags($masterProduct, $productData['tag_ids']);

        return $masterProduct;
    }

    private function masterProductLists(array $filterData, int $companyId): Builder
    {
        $categoryQueries = new CategoryQueries();
        $brandQueries = new BrandQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);

        return MasterProduct::query()
            ->select(
                'id',
                'name',
                'code',
                'description',
                'brand_id',
                'vendor_id',
                'unit_of_measure_id',
                'department_id',
                'article_number',
                'type_id',
                'has_batch',
                'is_non_inventory',
                'is_non_selling_item',
                'original_created_at',
                'created_by_id',
                'created_by_type',
                'status',
                'created_at',
                'updated_at',
            )
            ->with([
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                'unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries
            ): void {
                $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                    $query
                        ->whereAny(
                            ['name', 'code', 'article_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('brand', $brandQueries->searchByName($filterData['search_text']))
                        ->orWhereHas('categories', $categoryQueries->searchByName($filterData['search_text']));
                });
            })
            ->whereNot('status', Statuses::DRAFT->value)
            ->where('company_id', $companyId)
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
                $query->where('type_id', (int) $filterData['product_type_id']);
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
            })
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->onlyActive();
            })
            ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                $query->where('has_batch', true);
            })
            ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                $query->where('has_batch', false);
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('article_number', (array) $filterData['article_numbers']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyArchived();
            });
    }

    private function uploadPhoto(
        MasterProduct $masterProduct,
        MasterProductData|ProductData $masterProductData,
        array $urls = []
    ): void {
        if (! $masterProductData->images) {
            return;
        }

        if ([] !== $urls) {
            foreach ($urls as $url) {
                $masterProduct->addMediaFromUrl($url)->toMediaCollection('images');
            }

            return;
        }

        foreach ($masterProductData->images as $image) {
            if ($image instanceof UploadedFile) {
                $masterProduct->addMedia($image)->toMediaCollection('images');
            }
        }
    }

    private function uploadVideo(
        MasterProduct $masterProduct,
        MasterProductData|ProductData $masterProductData,
        array $urls = []
    ): void {
        if (! $masterProductData->videos) {
            return;
        }

        if ([] !== $urls) {
            foreach ($urls as $url) {
                $masterProduct->addMediaFromUrl($url)->toMediaCollection('videos');
            }

            return;
        }

        foreach ($masterProductData->videos as $video) {
            if ($video instanceof UploadedFile) {
                $masterProduct->addMedia($video)->toMediaCollection('videos');
            }
        }
    }

    private function uploadOtherImages(
        MasterProduct $masterProduct,
        MasterProductData|ProductData $masterProductData,
        string $url = ''
    ): void {
        if ($masterProductData->thumbnail instanceof UploadedFile) {
            if ('' !== $url) {
                $masterProduct->addMediaFromUrl($url)->toMediaCollection('thumbnail');

                return;
            }

            $masterProduct->addMedia($masterProductData->thumbnail)->toMediaCollection('thumbnail');
        }
    }

    private function updateAssemblyItems(MasterProduct $masterProduct, MasterProductData $masterProductData): void
    {
        if ((int) $masterProductData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);
            $assemblyChildMasterProductQueries->deleteAssemblyChildMasterProducts($masterProduct);
            foreach ((array) $masterProductData->assembly_child_master_products as $assemblyItem) {
                $assemblyChildMasterProductQueries->addNew([
                    'master_product_id' => $masterProduct->id,
                    'child_master_product_id' => $assemblyItem['child_master_product_id'],
                    'units' => $assemblyItem['units'],
                ]);
            }
        }
    }

    private function uploadVariants(MasterProduct $masterProduct, MasterProductData $masterProductData): void
    {
        $productQueries = resolve(ProductQueries::class);
        if ($masterProductData->variants instanceof DataCollection) {
            foreach ($masterProductData->variants->all() as $variant) {
                $variant = $variant->toArray();
                $tiers = $variant['tiers'];
                $boxes = $variant['boxes'];
                $productVariantValues = $variant['product_variant_values'];
                $thumbnail = $variant['thumbnail'];
                $images = $variant['images'];
                $videos = $variant['videos'];
                $saleChannelIds = $variant['sale_channel_ids'];

                unset($variant['tiers']);
                unset($variant['boxes']);
                unset($variant['product_variant_values']);
                unset($variant['thumbnail']);
                unset($variant['images']);
                unset($variant['videos']);
                unset($variant['sale_channel_ids']);

                $variant['master_product_id'] = $masterProduct->id;
                $variant['company_id'] = $masterProduct->company_id;
                $variant['brand_id'] = $masterProduct->brand_id;
                $variant['type_id'] = $masterProduct->type_id;
                $variant['compound_product_name'] = $this->generateCompoundItemName(
                    $variant['name'],
                    $productVariantValues
                );
                $productVariant = $productQueries->updateOrCreate($variant);
                $this->updateSaleChannels($productVariant, $saleChannelIds);
                $this->updateLoyaltyPointMembership($productVariant, $tiers);
                $this->updateProductVariantBox($productVariant, $boxes, (int) $masterProduct->type_id);
                $this->updateProductVariantValue($productVariant, $productVariantValues);
                $this->uploadVariantOtherImages($productVariant, $thumbnail);
                $this->uploadVariantPhoto($productVariant, $images);
                $this->uploadVariantVideo($productVariant, $videos);
            }
        }
    }

    private function generateCompoundItemName(string $variantName, array $productVariantValues): string
    {
        $productVariantValues = array_column($productVariantValues, 'selected_value');

        $productVariantValues = array_filter($productVariantValues, fn ($item): bool => null !== $item);

        $concatenatedValues = implode(' ', $productVariantValues);

        return $variantName.' '.$concatenatedValues;
    }

    private function uploadVariantOtherImages(Product $product, ?UploadedFile $thumbnail): void
    {
        if ($thumbnail instanceof UploadedFile) {
            $product->addMedia($thumbnail)->toMediaCollection('thumbnail');
        }
    }

    private function uploadVariantPhoto(Product $product, array $images): void
    {
        foreach ($images as $image) {
            if ($image) {
                $product->addMedia($image)->toMediaCollection('images');
            }
        }
    }

    private function uploadVariantVideo(Product $product, array $videos): void
    {
        foreach ($videos as $video) {
            if ($video) {
                $product->addMedia($video)->toMediaCollection('videos');
            }
        }
    }

    private function updateLoyaltyPointMembership(Product $product, array $tires): void
    {
        $product->tiers()->delete();

        if ([] === $tires) {
            return;
        }

        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);

        foreach ($tires as $loyaltyPointTier) {
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

    private function updateProductVariantBox(Product $product, array $boxes, int $typeId): void
    {
        $boxProductQueries = resolve(BoxProductQueries::class);
        $boxProductQueries->deleteProductBox($product);

        if ([] === $boxes) {
            return;
        }

        if ($typeId === ProductTypes::REGULAR_PRODUCT->value) {
            $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);

            foreach ((array) $boxes as $box) {
                $boxProduct = $boxProductQueries->addNew([
                    'product_id' => $product->id,
                    'package_type_id' => $box['package_type_id'],
                    'units' => $box['units'],
                    'retail_price' => $box['retail_price'],
                    'minimum_price' => $box['minimum_price'],
                    'staff_price' => $box['staff_price'],
                    'purchase_cost' => $box['purchase_cost'],
                    'wholesale_price' => $box['wholesale_price'],
                ]);
                if (! array_key_exists('box_product_loyalty_points', $box)) {
                    continue;
                }

                if ((array) $box['box_product_loyalty_points'] === []) {
                    continue;
                }

                foreach ($box['box_product_loyalty_points'] as $boxItemVariantLoyaltyPoint) {
                    $boxProductLoyaltyPointQueries->addNew([
                        'box_product_id' => $boxProduct->id,
                        'membership_id' => $boxItemVariantLoyaltyPoint['membership_id'],
                        'points' => (int) $boxItemVariantLoyaltyPoint['points'],
                    ]);
                }
            }
        }
    }

    private function updateProductVariantValue(Product $productVariant, array $productVariantValues): void
    {
        $productVariant->productVariantValues()->delete();

        if ([] === $productVariantValues) {
            return;
        }

        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        foreach ($productVariantValues as $productVariantValue) {
            $productVariantValueQueries->addNew(
                $productVariant->id,
                (int) $productVariantValue['id'],
                (string) $productVariantValue['selected_value'],
            );
        }
    }

    private function updateCategories(MasterProduct $masterProduct, array $categoryIds): void
    {
        $masterProduct->categories()->detach();
        $categoryIds = collect($categoryIds)->unique();
        foreach ($categoryIds as $key => $categoryId) {
            if ($categoryId) {
                $masterProduct->categories()->attach([
                    $categoryId => [
                        'sort_order' => $key,
                    ],
                ]);
            }
        }
    }

    private function createOrUpdateCustomFieldValues(
        MasterProduct $masterProduct,
        MasterProductData|ProductData $masterProductData
    ): void {
        $attachedTemplateQueries = resolve(AttachedTemplateQueries::class);
        $customFieldValueQueries = resolve(CustomFieldValueQueries::class);
        $this->clearOldCustomFieldValueRecords($attachedTemplateQueries, $customFieldValueQueries, $masterProduct);

        $customFieldValuesData = $masterProductData->custom_field_values ?? [];

        $modelType = $masterProductData instanceof MasterProductData ? ModelMapping::MASTER_PRODUCT->name : ModelMapping::PRODUCT->name;

        foreach ($customFieldValuesData as $template) {
            foreach ($template['attributes'] as $attribute) {
                $value = is_array($attribute['selected_value']) ? json_encode(
                    $attribute['selected_value']
                ) : $attribute['selected_value'];

                $customFieldValueQueries->addNew([
                    'model_type' => $modelType,
                    'model_id' => $masterProduct->id,
                    'template_id' => $template['id'],
                    'attribute_id' => $attribute['id'],
                    'value' => $value,
                ]);
            }
        }

        $attachedTemplatesData = $masterProductData->attached_templates ?? [];

        foreach ($attachedTemplatesData as $attachedTemplateData) {
            $attachedTemplateQueries->addNew([
                ...$attachedTemplateData,
                'model_type' => $modelType,
                'model_id' => $masterProduct->id,
            ]);
        }
    }

    private function clearOldCustomFieldValueRecords(
        AttachedTemplateQueries $attachedTemplateQueries,
        CustomFieldValueQueries $customFieldValueQueries,
        MasterProduct $masterProduct
    ): void {
        $attachedTemplateQueries->deleteMasterProduct($masterProduct);
        $customFieldValueQueries->deleteMasterProduct($masterProduct);
    }

    private function commonQueryForEditItem(int $companyId, int $masterProductId, array $statuses): MasterProduct
    {
        $categoryQueries = new CategoryQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $templateQueries = resolve(TemplateQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $attachedTemplateQueries = resolve(AttachedTemplateQueries::class);
        $assemblyChildMasterProductQueries = resolve(AssemblyChildMasterProductQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        return MasterProduct::query()->select(...$this->getAllBasicColumns())
            ->with([
                'media:' . $mediaQueries->getBasicColumnNames(),
                'assemblyChildMasterProducts:' . $assemblyChildMasterProductQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
                'department:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'productVariants:' . $productQueries->getBasicColumnNamesForVariants(),
                'productVariants.media:' . $mediaQueries->getBasicColumnNames(),
                'productVariants.tiers:' . $productLoyaltyPointQueries->getBasicColumnNames(),
                'productVariants.boxes:' . $boxProductQueries->getBasicColumnNames(),
                'productVariants.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'productVariants.productVariantValues.attribute' => function ($query): void {
                    $query->select('id', 'is_required');
                },
                'productVariants.boxes.boxProductLoyaltyPoints:' . $boxProductLoyaltyPointQueries->getBasicColumnNames(),
                'productVariants.saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'variantTemplate:'. $templateQueries->getColumnNamesForRelation(),
                'attachedTemplates:' . $attachedTemplateQueries->getBasicColumnNames(),
                'attachedTemplates.template:'. $templateQueries->getColumnNamesForRelation(),
                'attachedTemplates.template.attributes' => function ($query): void {
                    $query->select(
                        'id',
                        'template_id',
                        'name',
                        'field_type',
                        'default_value',
                        'from',
                        'to',
                        'options',
                        'is_required',
                    );
                },
                'attachedTemplates.template.attributes.customFieldValue' => function ($query) use (
                    $masterProductId
                ): void {
                    $query->select('attribute_id', 'value')
                        ->where('model_id', $masterProductId);
                },
            ])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('status', $statuses)
            ->findOrFail($masterProductId);
    }

    private function updateTags(MasterProduct $masterProduct, ?array $tagIds): void
    {
        if (null !== $tagIds) {
            $masterProduct->tags()->sync($tagIds);
        }
    }

    private function updateSaleChannels(Product $product, array $saleChannelIds = []): void
    {
        if ([] === $saleChannelIds) {
            return;
        }

        $product->saleChannels()->sync($saleChannelIds);
    }

    public function getAllByCompanyId(int $companyId, int $perPage = 1000): LengthAwarePaginator
    {
        return MasterProduct::select(
            'id',
            'name',
            'company_id',
            'brand_id',
            'vendor_id',
            'original_created_at',
            'variant_template_id',
            'article_number'
        )
        ->where('company_id', $companyId)
        ->where('status', Statuses::ACTIVE->value)
        ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
        ->paginate($perPage);
    }

    public function getByArticleNumberAndCompanyId(?string $articleNumber, int $companyId): ?MasterProduct
    {
        return MasterProduct::select('id', 'status', 'variant_template_id')
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getByArticleNumberAndCompanyIdWithTrash(?string $articleNumber, int $companyId): ?MasterProduct
    {
        return MasterProduct::select('id', 'status', 'variant_template_id')
            ->withTrashed()
            ->where('article_number', $articleNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getCompanyActiveRegularMasterProductCount(int $companyId): int
    {
        return MasterProduct::where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->count();
    }
}
