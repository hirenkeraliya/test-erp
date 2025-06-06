<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Resources\ApplicationPromotionListResource;
use App\Domains\Promotion\Resources\ApplicationPromotionWithPromoCodeListResource;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function getStoreWisePromotion(Request $request, int $locationId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $promotionQueries = resolve(PromotionQueries::class);
        $promotions = $promotionQueries->getPromotionsStoreWiseForApplication($companyId, $locationId, $afterUpdatedAt);

        return [
            'data' => ApplicationPromotionListResource::collection($promotions),
        ];
    }

    public function getStoreWiseManualPromotion(Request $request, int $locationId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);
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

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

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
