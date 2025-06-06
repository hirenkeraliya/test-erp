<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductImageUploadByArticleNumberData;
use App\Domains\Product\DataObjects\ProductImageUploadData;
use App\Domains\Product\DataObjects\ProductStockPurchasePlanData;
use App\Domains\Product\DataObjects\ProductWithLocationStockData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Exports\BoxProductExport;
use App\Domains\Product\Exports\BulkUpdateProductExport;
use App\Domains\Product\Exports\LoyaltyPointProductExport;
use App\Domains\Product\Exports\ProductExport;
use App\Domains\Product\Jobs\ProductMergeJob;
use App\Domains\Product\Jobs\ProductSyncMainJob;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\AdminProductListResource;
use App\Domains\Product\Resources\ProductListForMergeResource;
use App\Domains\Product\Resources\ProductMatchingUpcInventoryResource;
use App\Domains\Product\Resources\ProductMatchingUpcResource;
use App\Domains\Product\Resources\ProductUploadImagesResource;
use App\Domains\Product\Services\ProductService;
use App\Domains\ProductChannelReference\Jobs\RemoveProductChannelReferenceDataJob;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::PRODUCT->value,
            session('admin_company_id')
        );

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::PRODUCT->value,
            session('admin_company_id')
        );

        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $exportRecordCount = $exportRecordQueries->exportRecordCountForProductHistory(session('admin_company_id'));

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        return Inertia::render('products/Index', [
            'productStatuses' => ProductStatuses::getList(),
            'productBatches' => ProductBatches::getList(),
            'productSyncTypes' => ProductSyncTypes::getList(),
            'allStatus' => ProductStatuses::ACTIVE,
            'allBatch' => ProductBatches::ALL,
            'productTypes' => ProductTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('product'),
            'uploadImagePermission' => 'product_' . PermissionList::PRODUCT_UPLOAD_IMAGE->value,
            'allProductSyncType' => ProductSyncTypes::ALL_PRODUCT->value,
            'activeProduct' => Statuses::ACTIVE->value,
            'archivedProduct' => Statuses::ARCHIVED->value,
            'exportType' => ExportRecordTypes::PRODUCTS->value,
            'exportRecordCount' => $exportRecordCount,
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'product_sync_type_id' => (int) $request->get('product_sync_type_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $lengthAwarePaginator = $this->productQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminProductListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function productDetails(int $productId, Request $request): array
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        $product = $this->productQueries->getByIdWithRelationsForPrint($productId, session('admin_company_id'));

        return [
            'product' => new ProductListForMergeResource($product, $allPermissionLists),
        ];
    }

    public function mergeAndDeleteProduct(int $oldProductId, int $newProductId, Request $request): array
    {
        $companyId = session('admin_company_id');

        /** @var User $user */
        $user = $request->user();

        if ($this->productQueries->checkProductIsActive($companyId, $newProductId) !== Statuses::ACTIVE->value) {
            abort(412, 'The new selected product is not active.');
        }

        if ($oldProductId === $newProductId) {
            abort(
                412,
                'Make sure the merged product is not the same as its opposite. You can do this by using the archive product feature.'
            );
        }

        $oldProduct = $this->productQueries->getProductTypeAndArticleNumber($oldProductId, $companyId);
        $newProduct = $this->productQueries->getProductTypeAndArticleNumber($newProductId, $companyId);

        if (config('app.product_variant')) {
            if ($oldProduct->masterProduct?->id !== $newProduct->masterProduct?->id) {
                abort(412, 'Same Master Product only can be merge.');
            }

            if ($oldProduct->masterProduct?->type_id !== $newProduct->masterProduct?->type_id) {
                abort(412, 'Same Product type only can be merge. Like Regular v/s Regular.');
            }

            if (null !== $newProduct->masterProduct?->article_number && null !== $oldProduct->masterProduct?->article_number && $newProduct->masterProduct->article_number !== $oldProduct->masterProduct->article_number) {
                abort(412, "Same Article Number's product only can be merge.");
            }

            if (! (null !== $newProduct->masterProduct?->article_number && null !== $oldProduct->masterProduct?->article_number)) {
                abort(
                    412,
                    'One of the product have no article number. Hence, Both products must have no Article Number to be merged.'
                );
            }
        } else {
            if ($oldProduct->type_id !== $newProduct->type_id) {
                abort(412, 'Same Product type only can be merge. Like Regular v/s Regular.');
            }

            if (null !== $newProduct->article_number && null !== $oldProduct->article_number && $newProduct->article_number !== $oldProduct->article_number) {
                abort(412, "Same Article Number's product only can be merge.");
            }

            if (! (null !== $newProduct->article_number && null !== $oldProduct->article_number)) {
                abort(
                    412,
                    'One of the product have no article number. Hence, Both products must have no Article Number to be merged.'
                );
            }
        }

        $this->productQueries->markAsArchived($oldProductId, session('admin_company_id'));
        $this->productQueries->markAsArchived($newProductId, session('admin_company_id'));

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $productCollectionProductQueries->removeByProductId($oldProductId, session('admin_company_id'));

        ProductMergeJob::dispatch($user, $oldProductId, $newProductId, $companyId)->onQueue('high');
        ProductCollectionUpdateByProductJob::dispatch($newProductId, $companyId)->onQueue('medium');

        return [
            'message' => 'Merged Product Is In Progress, It Will Take Some Time To Process The Inventories.',
        ];
    }

    public function create(): Response
    {
        $productService = resolve(ProductService::class);

        return Inertia::render('products/Manage', $productService->getCommonRecords(session('admin_company_id')));
    }

    public function store(ProductData $productData, Request $request): RedirectResponse
    {
        $productService = resolve(ProductService::class);
        $masterProductService = resolve(MasterProductService::class);
        $companyId = session('admin_company_id');
        $productService->checkRequestDetails($productData->brand_id, $companyId, $productData);
        $productService->validateBoxProductLoyaltyPointMembership($productData);

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $product = $this->productQueries->addNew($productData, session('admin_company_id'), $user);
            $masterProductService->createOrUpdateFromProduct($product, $productData);

            DB::commit();

            return to_route('admin.products.index')
                ->with('success', 'Product added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Product', [
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

    public function edit(int $productId): Response
    {
        $product = $this->productQueries->getByIdWithMediaCategoriesAndTags(
            $productId,
            session('admin_company_id')
        );
        /** @var int ?$typeId */
        $typeId = config('app.product_variant') ? $product->masterProduct?->type_id : $product->type_id;

        $productService = resolve(ProductService::class);
        $product['uploaded_images'] = $product->getDiskBasedMediaUrls('images');
        $product['uploaded_videos'] = $product->getDiskBasedMediaUrls('videos');
        $product['type_name'] = $typeId ? ProductTypes::getFormattedCaseName($typeId) : 'N/A';
        $product['thumbnail_url'] = $product->getDiskBasedFirstMediaUrl('thumbnail');
        $product['custom_field_values'] = $productService->prepareCustomFieldValues($product->attachedTemplates);

        return Inertia::render('products/Manage', [
            'product' => $product,
            'updateUnitOfMeasure' => config('app.update_unit_of_measure'),
            ...$productService->getCommonRecords(session('admin_company_id')),
        ]);
    }

    public function archive(int $productId): RedirectResponse
    {
        $this->productQueries->markAsArchived($productId, session('admin_company_id'));

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $productCollectionProductQueries->removeByProductId($productId, session('admin_company_id'));

        return to_route('admin.products.index')->with('success', 'Product archived successfully.');
    }

    public function restore(int $productId): RedirectResponse
    {
        $this->productQueries->restore($productId, session('admin_company_id'));
        ProductCollectionUpdateByProductJob::dispatch($productId, session('admin_company_id'))->onQueue('medium');

        return to_route('admin.products.index')->with('success', 'Product restored successfully.');
    }

    public function uploadImage(ProductImageUploadData $productImageUploadData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->productQueries->uploadImage($productImageUploadData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.products.index')
                ->with('success', 'Product image uploaded successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Product', [
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

    public function update(ProductData $productData, int $productId, Request $request): RedirectResponse
    {
        $productService = resolve(ProductService::class);
        $masterProductService = resolve(MasterProductService::class);
        $companyId = session('admin_company_id');
        $productService->checkRequestDetails($productData->brand_id, $companyId, $productData);
        $productService->validateRetailPriceForPromotion($productData, $productId, $companyId);
        $productService->validateBoxProductLoyaltyPointMembership($productData);

        DB::beginTransaction();

        try {
            $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
            $productCollectionProductQueries->removeByProductId($productId, $companyId);

            /** @var User $user */
            $user = $request->user();

            $product = $this->productQueries->update($productData, $productId, $companyId, $user);

            if (null !== $product->master_product_id) {
                $masterProductService->createOrUpdateFromProduct($product, $productData);
            }

            DB::commit();

            return to_route('admin.products.index')
                ->with('success', 'Product updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Product', [
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

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getMatchingUpcProducts(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveProductsByUpc(
            $validatedData['import_products'],
            session('admin_company_id')
        );

        return [
            'products' => ProductMatchingUpcResource::collection($products),
            'products_count' => $products->count(),
        ];
    }

    public function getMatchingUpcAndIsSellingProducts(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveAndIsSellingProductsByUpc(
            $validatedData['import_products'],
            session('admin_company_id')
        );

        return [
            'products' => ProductMatchingUpcInventoryResource::collection($products),
            'products_count' => $products->count(),
        ];
    }

    public function getActiveInventoryProductsByUpcs(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveInventoryProductsByUpcs(
            $validatedData['import_products'],
            session('admin_company_id')
        );

        return [
            'products' => ProductMatchingUpcInventoryResource::collection($products),
            'products_count' => $products->count(),
        ];
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function getActiveInventoryProductsByUpcsWithDerivatives(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveInventoryProductsByUpcsWithDerivatives(
            $validatedData['import_products'],
            session('admin_company_id')
        );

        return [
            'products' => $products,
            'products_count' => $products->count(),
        ];
    }

    public function exportProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        $products = $this->productQueries->getProductsWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new ProductExport($products, $filteredColumns, $allPermissionLists), $filename);
    }

    /**
     * @return array<string, LazyCollection>
     */
    public function getFilteredArticleNumber(Request $request): array
    {
        return [
            'articleNumbers' => $this->productQueries->getFilteredArticleNumberByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function existsProductUpc(string $upc): array
    {
        return [
            'status' => $this->productQueries->existsByUpc($upc, session('admin_company_id')),
        ];
    }

    /**
     * @return array<string, Collection<int|string, array{id: mixed, has_batch: mixed, color: mixed, size: mixed, stock: null, combination: string}>>|array<string, mixed[]>
     */
    public function searchByArticleNumber(ProductArticleData $productArticleData): array
    {
        $productService = resolve(ProductService::class);

        return $productService->getActiveInventoryProductDetailsForArticleNumber(
            $productArticleData,
            session('admin_company_id')
        );
    }

    public function searchByArticleNumberWithStock(ProductWithLocationStockData $productWithLocationStockData): array
    {
        $productService = resolve(ProductService::class);

        return $productService->getProductArticleNumberWithLocationStock(
            $productWithLocationStockData,
            session('admin_company_id')
        );
    }

    public function searchByArticleNumberForPurchasePlan(
        ProductStockPurchasePlanData $productStockPurchasePlanData
    ): array {
        $productService = resolve(ProductService::class);

        return $productService->getProductArticleNumberWithLocationStockForPurchasePlan(
            $productStockPurchasePlanData,
            session('admin_company_id')
        );
    }

    public function printProducts(Request $request): string
    {
        $productsData = [];
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $companyId = session('admin_company_id');
        $products = $this->productQueries->getProductsWithRelationsForPrint($filterData, $companyId);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productService = resolve(ProductService::class);
        $filterColumns = $productService->filterColumnsForPdf($filteredColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $productsData['details'] = $productService->productDataPrint($products, $filteredColumns);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.product_details', [
            'productDetails' => $productsData['details'],
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function searchProductsByOnlyArticleNumber(Request $request): array
    {
        $request->validate([
            'article_number' => ['required', 'string'],
        ]);

        $filterData = [
            'article_number' => $request->get('article_number'),
        ];

        $productService = resolve(ProductService::class);

        return $productService->getProductDetailsByArticleNumber($filterData, session('admin_company_id'));
    }

    public function getProductSalesSummary(Request $request): array
    {
        $companyId = session('admin_company_id');
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $products = $this->productQueries->getProductSalesSummary($filterData, $companyId);

        return [
            'products' => $products,
            'total_sales' => $products->sum('total_sales'),
            'total_units_sold' => $products->sum('total_units_sold'),
        ];
    }

    public function removeProductImage(int $productId, int $mediaId): void
    {
        $this->productQueries->removeProductImage($productId, $mediaId);
    }

    public function removeProductVideo(int $productId, int $mediaId): void
    {
        $this->productQueries->removeProductVideo($productId, $mediaId);
    }

    public function removeProductThumbnail(int $productId): void
    {
        $this->productQueries->removeProductThumbnail($productId);
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        ProductSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::PRODUCT->value,
            $admin,
            session('admin_company_id')
        );
    }

    public function removeSalesChannelReferencesData(Request $request): void
    {
        $productIds = [];
        if ($request->has('product_ids')) {
            $productIds = $request->get('product_ids');
        }

        if (
            $request->has('filter_data') &&
            array_key_exists('all_product_selected', $request->get('filter_data')) &&
            $request->get('filter_data')['all_product_selected']
        ) {
            $productIds = $this->getSelectAllProductIds($request);
        }

        $saleChannelId = $request->integer('sale_channel_id');

        $chunkProductIds = array_chunk($productIds, 500);

        foreach ($chunkProductIds as $productIds) {
            RemoveProductChannelReferenceDataJob::dispatch($productIds, $saleChannelId);
        }
    }

    public function getSelectAllProductIds(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('filter_data')['search_text'],
            'sort_by' => $request->get('filter_data')['sort_by'],
            'sort_direction' => $request->get('filter_data')['sort_direction'],
            'per_page' => $request->get('filter_data')['per_page'],
            'status' => $request->get('filter_data')['status'],
            'batch' => $request->get('filter_data')['batch'] ?? null,
            'date_range' => $request->get('filter_data')['date_range'] ?? null,
            'product_type_id' => $request->get('filter_data')['product_type_id'] ?? null,
            'category_ids' => $request->get('filter_data')['category_ids'] ?? [],
            'brand_ids' => $request->get('filter_data')['brand_ids'] ?? [],
            'color_ids' => $request->get('filter_data')['color_ids'] ?? [],
            'size_ids' => $request->get('filter_data')['size_ids'] ?? [],
            'department_ids' => $request->get('filter_data')['department_ids'] ?? [],
            'article_numbers' => $request->get('filter_data')['article_numbers'] ?? [],
            'tag_ids' => $request->get('filter_data')['tag_ids'] ?? [],
            'style_ids' => $request->get('filter_data')['style_ids'] ?? [],
            'product_collection_ids' => $request->get('filter_data')['product_collection_ids'] ?? [],
            'attributes' => $request->get('filter_data')['attributes'] ?? [],
            'product_sync_type_id' => (int) $request->get('filter_data')['product_sync_type_id'],
        ];

        $products = $this->productQueries->getProductIds($filterData, session('admin_company_id'));

        return $products->pluck('id')->toArray();
    }

    public function checkProductExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');
        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();
        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductWithJob($admin, $filterData, $companyId, $filteredColumns);
    }

    public function exportLoyaltyPointProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);

        $loyaltyPointProducts = $productLoyaltyPointQueries->getLoyaltyPointProducts(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new LoyaltyPointProductExport($loyaltyPointProducts), $filename);
    }

    public function exportBoxProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        $boxProductQueries = resolve(BoxProductQueries::class);

        $boxProducts = $boxProductQueries->getBoxProducts($filterData, session('admin_company_id'));

        return Excel::download(new BoxProductExport($boxProducts), $filename);
    }

    public function checkProductLoyaltyPointExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductLoyaltyPointWithJob($admin, $filterData, $companyId);
    }

    public function checkBoxProductExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportBoxProductWithJob($admin, $filterData, $companyId);
    }

    public function fetchProductDetailsByArticleNumber(Request $request): array
    {
        $companyId = session('admin_company_id');
        $product = $this->productQueries->getProductDetailsByArticleNumberForUploadImages(
            $request->get('article_number'),
            $companyId
        );

        return [
            'product' => new ProductUploadImagesResource($product),
        ];
    }

    public function uploadImagesByArticleNumber(
        ProductImageUploadByArticleNumberData $productImageUploadByArticleNumberData
    ): RedirectResponse {
        DB::beginTransaction();

        try {
            $this->productQueries->uploadImagesByArticleNumber(
                $productImageUploadByArticleNumberData,
                session('admin_company_id')
            );

            DB::commit();

            return to_route('admin.products.index')
                ->with('success', 'Product image uploaded successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Product', [
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

    public function exportProductsForImportBulkUpdate(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        $products = $this->productQueries->getProductsWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new BulkUpdateProductExport($products, $allPermissionLists), $filename);
    }

    public function checkProductExportLimitForImportBulkUpdate(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductWithJobForImportBulkUpdate($admin, $filterData, $companyId);
    }

    private function getExportProductsFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];
    }
}
