<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\Location\LocationQueries;
use App\Domains\Promotion\DataObjects\PaginatedManualPromotionDataForPos;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Resources\PosPromotionListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $locationQueries = resolve(LocationQueries::class);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $promotionQueries = resolve(PromotionQueries::class);
        $promotionsList = $promotionQueries->getListForPosAsPerTimeFrameWithRelatedData($location, $afterUpdatedAt);

        return [
            'promotions' => PosPromotionListResource::collection($promotionsList),
        ];
    }

    public function getPaginatedManualPromotion(
        Request $request,
        PaginatedManualPromotionDataForPos $paginatedManualPromotionDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $locationQueries = resolve(LocationQueries::class);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'per_page' => $paginatedManualPromotionDataForPos->per_page,
            'search_text' => $paginatedManualPromotionDataForPos->search_text,
            'after_updated_at' => $paginatedManualPromotionDataForPos->after_updated_at,
        ];

        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $promotionQueries = resolve(PromotionQueries::class);
        $promotionsList = $promotionQueries->getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly(
            $location,
            $filterData
        );

        return [
            'promotions' => PosPromotionListResource::collection($promotionsList),
            'total_records' => $promotionsList->total(),
            'last_page' => $promotionsList->lastPage(),
            'current_page' => $promotionsList->currentPage(),
            'per_page' => $promotionsList->perPage(),
        ];
    }

    public function getPromotionWithPromoCode(Request $request, string $promoCode): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $locationQueries = resolve(LocationQueries::class);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $promotionQueries = resolve(PromotionQueries::class);
        $promotion = $promotionQueries->getPromotionOfProvidedPromoCode($location, $promoCode);

        return [
            'promotion' => $promotion instanceof Promotion ? new PosPromotionListResource($promotion) : [],
        ];
    }
}
