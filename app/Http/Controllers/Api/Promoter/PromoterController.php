<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\CommonFunctions;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\StoreWiseProductStockResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\DataObjects\PromoterProductData;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ProductDetailsForApplicationResource;
use App\Domains\Product\Resources\PromoterProductListResource;
use App\Domains\Promoter\DataObjects\PromoterApplicationData;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\ApplicationProfileDetailsResource;
use App\Domains\Store\DataObjects\PromoterStoreData;
use App\Domains\Store\Resources\PromoterAppStoreListResource;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PromoterController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedProductList(Request $request, PromoterProductData $promoterProductData): array
    {
        $filteredData = [
            'per_page' => $promoterProductData->per_page,
            'sort_by' => $promoterProductData->sort_by,
            'search_text' => $promoterProductData->search_text,
            'sort_direction' => $promoterProductData->sort_direction,
            'location_id' => $promoterProductData->store_id ?? $promoterProductData->location_id,
            'stock_product' => $promoterProductData->stock_product,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $lengthAwarePaginator = $productQueries->getProductsForApplication($filteredData, $companyId);

        return [
            'products' => PromoterProductListResource::collection($lengthAwarePaginator),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getStoreList(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $validatedData = $request->validate([
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filterData = [
            'search_text' => $validatedData['search_text'] ?? null,
        ];

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadLocationsWithSearch($promoter, $filterData);

        return [
            'stores' => PromoterAppStoreListResource::collection($promoter->getLocations()),
            'locations' => PromoterAppStoreListResource::collection($promoter->getLocations()),
        ];
    }

    public function getProductDetails(Request $request, int $productId, int $locationId): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $productDetail = $productQueries->getProductDetailsForApplication($productId, $companyId);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getByProductIdsAndLocation($locationId, (array) $productId);

        return [
            'productDetails' => (new ProductDetailsForApplicationResource($productDetail)),
            'stock' => CommonFunctions::truncateDecimal((float) $inventory->first()?->stock),
        ];
    }

    public function getStoreStock(Request $request, PromoterStoreData $promoterStoreData, int $productId): array
    {
        $filteredData = [
            'per_page' => $promoterStoreData->per_page,
            'sort_by' => $promoterStoreData->sort_by,
            'sort_direction' => $promoterStoreData->sort_direction,
            'search_text' => $promoterStoreData->search_text,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryStocksForApplication(
            $filteredData,
            LocationTypes::STORE->value,
            $companyId,
            $productId
        );

        return [
            'store_stock' => StoreWiseProductStockResource::collection($inventory),
            'location_stock' => StoreWiseProductStockResource::collection($inventory),
            'total_records' => $inventory->total(),
            'last_page' => $inventory->lastPage(),
            'current_page' => $inventory->currentPage(),
            'per_page' => $inventory->perPage(),
        ];
    }

    public function updateProfile(PromoterApplicationData $promoterApplicationData, Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $responseData = [
            'status_code' => null,
            'message' => null,
        ];

        DB::beginTransaction();

        try {
            $employeeQueries = resolve(EmployeeQueries::class);
            $employeeQueries->updateProfile($promoterApplicationData, $promoter->employee_id);

            $promoterQueries = resolve(PromoterQueries::class);
            $promoterQueries->updateUsername($promoter, $promoterApplicationData->username);

            $responseData['message'] = 'Profile Update Successfully!.';
            $responseData['status_code'] = '200';

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            $responseData['message'] = 'Something Went Wrong.';
            $responseData['status_code'] = $throwable->getCode();

            Log::error('Promoter application update profile failed error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return $responseData;
    }

    public function getProfileDetails(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->getByIdWithEmployeeAndLocations($promoter->id, $companyId);

        return [
            'promoter_details' => new ApplicationProfileDetailsResource($promoter),
        ];
    }

    public function emailVerification(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $employee = $employeeQueries->getById($promoter->employee_id, $companyId);

        if (null === $employee->email) {
            abort(412, 'The email is not set.');
        }

        if ($employee->is_email_verified) {
            abort(412, 'The email is already verified.');
        }

        EmailVerificationJob::dispatch($employee)->delay(now()->addSeconds(5))->onQueue('high');

        return [
            'message' => 'The verification mail sent successfully.',
        ];
    }
}
