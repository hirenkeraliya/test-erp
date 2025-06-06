<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\PosPromoterListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromoterController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getList(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->counter_update_id;

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);
        $companyId = CommonFunctions::getCashierCompanyId($cashier);
        $promoterQueries = resolve(PromoterQueries::class);
        $promoterLists = $promoterQueries->getPromoterListForPosAndOrders(
            $location->id,
            $companyId,
            afterUpdatedAt: $afterUpdatedAt
        );

        return [
            'promoters' => PosPromoterListResource::collection($promoterLists),
        ];
    }
}
