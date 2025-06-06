<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\DataObjects\MasterProductImageUploadData;
use App\Domains\MasterProduct\Exports\MasterProductExport;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProduct\Resources\EditMasterProductResource;
use App\Domains\MasterProduct\Resources\MasterProductListResource;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MasterProductController extends Controller
{
    public function __construct(
        protected MasterProductQueries $masterProductQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->isAvailable(session('admin_company_id'));

        return Inertia::render('master_products/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('master_product'),
            'uploadImagePermission' => 'master_product_' . PermissionList::MASTER_PRODUCT_UPLOAD_IMAGE->value,
            'activeProduct' => Statuses::ACTIVE->value,
            'saleChannel' => $saleChannel,
            'productStatuses' => ProductStatuses::getList(),
            'productBatches' => ProductBatches::getList(),
            'productSyncTypes' => ProductSyncTypes::getList(),
            'allStatus' => ProductStatuses::ACTIVE,
            'allBatch' => ProductBatches::ALL,
            'productTypes' => ProductTypes::getList(),
            'allProductSyncType' => ProductSyncTypes::ALL_PRODUCT->value,
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchMasterProducts(Request $request): array
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
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'product_sync_type_id' => (int) $request->get('product_sync_type_id'),
        ];

        $lengthAwarePaginator = $this->masterProductQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MasterProductListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $masterProductService = resolve(MasterProductService::class);

        return Inertia::render(
            'master_products/Manage',
            $masterProductService->getCommonRecords(session('admin_company_id'))
        );
    }

    public function store(MasterProductData $masterProductData, Request $request): RedirectResponse
    {
        $masterProductService = resolve(MasterProductService::class);
        $companyId = session('admin_company_id');
        $masterProductService->checkRequestDetails($masterProductData->brand_id, $companyId, $masterProductData);
        $masterProductService->validateBoxProductVariantLoyaltyPointMembership($masterProductData);

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $this->masterProductQueries->addNew($masterProductData, session('admin_company_id'), $user);

            DB::commit();

            return to_route('admin.master_products.index')
                ->with('success', 'Master Product added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Item', [
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

    public function edit(int $masterProductId): Response
    {
        $masterProduct = $this->masterProductQueries->getByIdWithMediaCategoriesAndTags(
            $masterProductId,
            session('admin_company_id')
        );
        $masterProductService = resolve(MasterProductService::class);
        $masterProduct['uploaded_images'] = $masterProduct->getDiskBasedMediaUrls('images');
        $masterProduct['uploaded_videos'] = $masterProduct->getDiskBasedMediaUrls('videos');
        $masterProduct['type_name'] = ProductTypes::getFormattedCaseName($masterProduct->type_id);
        $masterProduct['thumbnail_url'] = $masterProduct->getDiskBasedFirstMediaUrl('thumbnail');
        $masterProduct['custom_field_values'] = $masterProductService->prepareCustomFieldValues(
            $masterProduct->attachedTemplates
        );

        return Inertia::render('master_products/Manage', [
            'masterProduct' => (new EditMasterProductResource($masterProduct))->jsonSerialize(),
            'updateUnitOfMeasure' => config('app.update_unit_of_measure'),
            ...$masterProductService->getCommonRecords(session('admin_company_id')),
        ]);
    }

    public function uploadImage(MasterProductImageUploadData $masterProductImageUploadData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->masterProductQueries->uploadImage($masterProductImageUploadData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.master_products.index')
                ->with('success', 'Master product image uploaded successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Master Product', [
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

    public function update(MasterProductData $masterProductData, int $masterProductId): RedirectResponse
    {
        $masterProductService = resolve(MasterProductService::class);
        $companyId = session('admin_company_id');
        $masterProductService->checkRequestDetails($masterProductData->brand_id, $companyId, $masterProductData);
        $masterProductService->validateBoxProductVariantLoyaltyPointMembership($masterProductData);
        DB::beginTransaction();

        try {
            $this->masterProductQueries->update($masterProductData, $masterProductId, $companyId);

            DB::commit();

            return to_route('admin.master_products.index')
                ->with('success', 'Master Product updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Master Product', [
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

    public function exportMasterProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'product_type_id' => $request->get('product_type_id'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'product_sync_type_id' => (int) $request->get('product_sync_type_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var Admin $admin */
        $admin = $request->user();

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        $masterProducts = $this->masterProductQueries->getMasterProductsWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(
            new MasterProductExport($masterProducts, $filteredColumns, $allPermissionLists),
            $filename
        );
    }

    public function removeMasterProductImage(int $masterProductId, int $mediaId): void
    {
        $this->masterProductQueries->removeMasterProductImage($masterProductId, $mediaId);
    }

    public function removeMasterProductVideo(int $masterProductId, int $mediaId): void
    {
        $this->masterProductQueries->removeMasterProductVideo($masterProductId, $mediaId);
    }

    public function removeMasterProductThumbnail(int $masterProductId): void
    {
        $this->masterProductQueries->removeMasterProductThumbnail($masterProductId);
    }

    public function existsMasterProductUpc(string $upc): array
    {
        $productQueries = resolve(ProductQueries::class);

        return [
            'status' => $productQueries->existsByUpc($upc, session('admin_company_id')),
        ];
    }

    public function syncData(): void
    {
        // ToDo: Add Job For sync data
    }

    public function removeMasterProductVariants(int $masterProductId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productQueries->removeProductVariantByMasterProductId($masterProductId);
    }

    public function removeMasterProductVariant(int $productVariantId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productQueries->removeMasterProductVariantById($productVariantId);
    }

    public function removeProductVariantImage(int $productVariantId, int $mediaId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productQueries->removeProductImage($productVariantId, $mediaId);
    }

    public function removeProductVariantVideo(int $productVariantId, int $mediaId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productQueries->removeProductVideo($productVariantId, $mediaId);
    }

    public function removeProductVariantThumbnail(int $productVariantId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $productQueries->removeProductThumbnail($productVariantId);
    }

    /**
     * @return array<string, LazyCollection>
     */
    public function getFilteredArticleNumber(Request $request): array
    {
        return [
            'articleNumbers' => $this->masterProductQueries->getFilteredArticleNumberByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }
}
