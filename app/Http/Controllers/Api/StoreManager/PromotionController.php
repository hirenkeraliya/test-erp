<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promotion\DataObjects\StoreManagerApiPromotionData;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Resources\ApplicationPromotionListResource;
use App\Domains\Promotion\Resources\ApplicationPromotionWithPromoCodeListResource;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function getPromotions(Request $request, StoreManagerApiPromotionData $storeManagerApiPromotionData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filterData = [
            'sort_by' => $storeManagerApiPromotionData->sort_by,
            'sort_direction' => $storeManagerApiPromotionData->sort_direction,
            'per_page' => $storeManagerApiPromotionData->per_page,
            'location_id' => $storeManagerApiPromotionData->store_id ?? $storeManagerApiPromotionData->location_id,
            'search_text' => $storeManagerApiPromotionData->search_text,
            'after_updated_at' => $storeManagerApiPromotionData->after_updated_at,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $promotionQueries = resolve(PromotionQueries::class);
        $promotions = $promotionQueries->getPromotionsForApplication($filterData, $companyId);

        return [
            'data' => ApplicationPromotionListResource::collection($promotions->getCollection()),
            'total_records' => $promotions->total(),
            'last_page' => $promotions->lastPage(),
            'current_page' => $promotions->currentPage(),
            'per_page' => $promotions->perPage(),
        ];
    }

    public function getStoreWisePromotion(Request $request, int $locationId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $promotionQueries = resolve(PromotionQueries::class);
        $promotions = $promotionQueries->getPromotionsStoreWiseForApplication($companyId, $locationId, $afterUpdatedAt);

        return [
            'data' => ApplicationPromotionListResource::collection($promotions),
        ];
    }

    public function getManualPromotions(
        Request $request,
        StoreManagerApiPromotionData $storeManagerApiPromotionData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filterData = [
            'sort_by' => $storeManagerApiPromotionData->sort_by,
            'sort_direction' => $storeManagerApiPromotionData->sort_direction,
            'per_page' => $storeManagerApiPromotionData->per_page,
            'location_id' => $storeManagerApiPromotionData->store_id ?? $storeManagerApiPromotionData->location_id,
            'search_text' => $storeManagerApiPromotionData->search_text,
            'after_updated_at' => $storeManagerApiPromotionData->after_updated_at,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $promotionQueries = resolve(PromotionQueries::class);
        $promotions = $promotionQueries->getManualPromotionsForApplication($filterData, $companyId);

        return [
            'data' => ApplicationPromotionListResource::collection($promotions->getCollection()),
            'total_records' => $promotions->total(),
            'last_page' => $promotions->lastPage(),
            'current_page' => $promotions->currentPage(),
            'per_page' => $promotions->perPage(),
        ];
    }

    public function getStoreWiseManualPromotion(Request $request, int $locationId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $promotionQueries = resolve(PromotionQueries::class);
        $promotions = $promotionQueries->getManualPromotionsStoreWiseForApplication(
            $companyId,
            $locationId,
            $afterUpdatedAt
        );

        return [
            'data' => ApplicationPromotionListResource::collection($promotions),
        ];
    }

    public function getPromotionWithPromoCode(Request $request, string $promoCode): array
    {
        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $promotionQueries = resolve(PromotionQueries::class);
        $promotion = $promotionQueries->getPromotionsOfProvidedPromoCodeForApplication(
            $companyId,
            (int) $locationId,
            $promoCode
        );

        return [
            'promotion' => $promotion instanceof Promotion ? new ApplicationPromotionWithPromoCodeListResource(
                $promotion
            ) : [],
        ];
    }
}
