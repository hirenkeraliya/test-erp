<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProduct\Resources\EditMasterProductResource;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\DataObjects\DraftProductListData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\DraftProductListResource;
use App\Domains\Product\Resources\DraftProductViewModelResource;
use App\Domains\Product\Resources\MatchActiveProductsListResource;
use App\Domains\Product\Services\ProductService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\MasterProduct;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DraftProductController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $companyId = session('admin_company_id');

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithCreatorCanApproveDraftProductById($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes($companyId);
        }

        return Inertia::render('draft_products/Index', [
            'productStatuses' => [],
            'productBatches' => ProductBatches::getList(),
            'allStatus' => '',
            'allBatch' => ProductBatches::ALL,
            'productTypes' => ProductTypes::getList(),
            'productSyncTypes' => ProductSyncTypes::getList(),
            'allProductSyncType' => ProductSyncTypes::ALL_PRODUCT->value,
            'exportPermission' => PermissionList::getExportPermissionName('draft_product'),
            'creatorCanApproveDraftProduct' => $company->creator_can_approve_draft_product,
            'user' => [
                'id' => $user->id,
                'type' => ModelMapping::ADMIN->name,
            ],
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchDraftProducts(DraftProductListData $draftProductListData): array
    {
        $companyId = session('admin_company_id');

        $productQueries = resolve(ProductQueries::class);

        $lengthAwarePaginator = $productQueries->fetchDraftList($draftProductListData->toArray(), $companyId);

        $lengthAwarePaginator->getCollection()->transform(function ($draftProduct) use ($productQueries, $companyId) {
            $matchCount = $productQueries->matchProductCount($draftProduct, $companyId);
            $draftProduct->match_count = $matchCount;

            return $draftProduct;
        });

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DraftProductListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function approved(Request $request): RedirectResponse
    {
        $companyId = session('admin_company_id');

        /** @var User $user */
        $user = $request->user();
        $draftProductIds = $request->selectedRecords;

        $productQueries = resolve(ProductQueries::class);
        $count = $productQueries->getCurrentUserProductCount($draftProductIds, $user->id);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithCreatorCanApproveDraftProductById($companyId);

        if (! $company->creator_can_approve_draft_product && $count > 0) {
            throw new RedirectWithErrorException(
                'admin.draft_products.index',
                'Sorry! You created one of the selected products that can not be approved.'
            );
        }

        DB::beginTransaction();

        try {
            $productQueries->markAsApproved($draftProductIds, $companyId, $user);

            DB::commit();

            return to_route('admin.draft_products.index')
                ->with('success', 'Product approved successfully.');
        } catch (Throwable $throwable) {
            Log::error('Approve Draft Product', [
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
        /** @var User $user */
        $user = auth()->user();

        $companyId = session('admin_company_id');
        $companyQueries = resolve(CompanyQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productService = resolve(ProductService::class);

        $isDraftProduct = $productQueries->checkDraftProduct($productId, session('admin_company_id'));

        if (! $isDraftProduct) {
            throw new RedirectBackWithErrorException('Selected product status is not draft.');
        }

        $company = $companyQueries->getWithCreatorCanApproveDraftProductById($companyId);
        if (config('app.product_variant')) {
            $draftProduct = $productQueries->getByIdDraftProduct($productId, $companyId);
            $masterProductQueries = resolve(MasterProductQueries::class);
            $masterProductService = resolve(MasterProductService::class);
            $masterProduct = $masterProductQueries->getByIdWithMediaCategoriesAndTagsAndStatuses(
                (int) $draftProduct->master_product_id,
                $companyId
            );

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
                ...$masterProductService->getCommonRecords($companyId),
                'isDraftProduct' => true,
                'productId' => $productId,
                'creatorCanApproveDraftProduct' => $company->creator_can_approve_draft_product,
                'user' => [
                    'id' => $user->id,
                    'type' => ModelMapping::ADMIN->name,
                ],
            ]);
        }

        $product = $productQueries->getByIdWithMediaCategoriesAndTagsForDraftProduct($productId, $companyId);

        $product['uploaded_images'] = $product->getDiskBasedMediaUrls('images');
        $product['uploaded_videos'] = $product->getDiskBasedMediaUrls('videos');
        $product['type_name'] = ProductTypes::getFormattedCaseName($product->type_id);
        $product['thumbnail_url'] = $product->getDiskBasedFirstMediaUrl('thumbnail');

        return Inertia::render('products/Manage', [
            'product' => $product,
            'isDraftProduct' => true,
            'creatorCanApproveDraftProduct' => $company->creator_can_approve_draft_product,
            'user' => [
                'id' => $user->id,
                'type' => ModelMapping::ADMIN->name,
            ],
            ...$productService->getCommonRecords($companyId),
        ]);
    }

    public function update(ProductData $productData, int $productId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);
        $isDraftProduct = $productQueries->checkDraftProduct($productId, session('admin_company_id'));

        if (! $isDraftProduct) {
            throw new RedirectBackWithErrorException('Selected product status is not draft.');
        }

        $productService = resolve(ProductService::class);

        $productService->checkRequestDetails($productData->brand_id, $companyId, $productData);
        $productService->validateBoxProductLoyaltyPointMembership($productData);
        DB::beginTransaction();

        try {
            $productQueries->update($productData, $productId, $companyId);

            DB::commit();

            return to_route('admin.draft_products.index')
                ->with('success', 'Draft product updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Draft Product', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . print_r($throwable->getTrace(), true),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function updateMasterProduct(MasterProductData $masterProductData, int $masterProductId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $masterProductQueries = resolve(MasterProductQueries::class);

        $masterProductService = resolve(MasterProductService::class);

        $masterProductService->checkRequestDetails($masterProductData->brand_id, $companyId, $masterProductData);
        $masterProductService->validateBoxProductVariantLoyaltyPointMembership($masterProductData);

        DB::beginTransaction();

        try {
            $masterProductQueries->update($masterProductData, $masterProductId, $companyId);

            DB::commit();

            return to_route('admin.draft_products.index')
                ->with('success', 'Draft product updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Draft Product', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . print_r($throwable->getTrace(), true),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function getDraftProductIdsByExceptLoginUser(
        DraftProductListData $draftProductListData,
        Request $request
    ): array {
        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithCreatorCanApproveDraftProductById($companyId);

        /** @var Admin $user */
        $user = $request->user();

        if ($company->creator_can_approve_draft_product) {
            return $productQueries->getDraftProductIdsByCompanyLevel(
                $draftProductListData->toArray(),
                $companyId,
            )->pluck('id')->toArray();
        }

        return $productQueries->getDraftProductIdsByExceptLoginUser(
            $draftProductListData->toArray(),
            $companyId,
            $user->id,
        )->pluck('id')->toArray();
    }

    public function getDraftProductDetails(int $productId): array
    {
        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);

        $isDraftProduct = $productQueries->checkDraftProduct($productId, $companyId);

        if (! $isDraftProduct) {
            abort(412, 'Selected product status is not draft.');
        }

        $product = $productQueries->getDraftProductDetailsById($productId, $companyId);

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
            $product['uploaded_images'] = $product->getDiskBasedMediaUrls('images');
            $product['uploaded_videos'] = $product->getDiskBasedMediaUrls('videos');
            $product['type_name'] = ProductTypes::getFormattedCaseName($masterProduct->type_id);
            $product['thumbnail_url'] = $product->getDiskBasedFirstMediaUrl('thumbnail');
        } else {
            $product['uploaded_images'] = $product->getDiskBasedMediaUrls('images');
            $product['uploaded_videos'] = $product->getDiskBasedMediaUrls('videos');
            $product['type_name'] = ProductTypes::getFormattedCaseName($product->type_id);
            $product['thumbnail_url'] = $product->getDiskBasedFirstMediaUrl('thumbnail');
        }

        return [
            'product' => new DraftProductViewModelResource($product),
        ];
    }

    public function getMatchActiveProducts(int $draftProductId): array
    {
        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);

        $matchProducts = $productQueries->getMatchActiveProductsByDraftIdAndCompanyId($draftProductId, $companyId);

        return [
            'data' => MatchActiveProductsListResource::collection($matchProducts),
        ];
    }

    public function deleteProducts(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'selectedRecords' => ['required', 'array'],
        ]);

        $draftProductIds = $validatedData['selectedRecords'];

        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);
        $productQueries->deleteDraftProducts($companyId, $draftProductIds);

        return to_route('admin.draft_products.index')
            ->with('success', 'Product(s) deleted successfully.');
    }
}
