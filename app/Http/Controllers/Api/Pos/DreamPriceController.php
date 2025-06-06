<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Resources\PosDreamPriceApiListResource;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DreamPriceController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->counter_update_id;

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPriceList = $dreamPriceQueries->getListWithProducts($companyId, $location->id, $afterUpdatedAt);

        return [
            'dream_prices' => PosDreamPriceApiListResource::collection($dreamPriceList),
        ];
    }
}
