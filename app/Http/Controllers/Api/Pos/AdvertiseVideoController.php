<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Location\LocationQueries;
use App\Domains\PosAdvertisement\PosAdvertisementQueries;
use App\Domains\PosAdvertisement\Resources\PosAdvertisementListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdvertiseVideoController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $filterData = [
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
        ];

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $companyId = CommonFunctions::getCashierCompanyId($cashier);
        $posAdvertisementQueries = resolve(PosAdvertisementQueries::class);
        $advertisements = $posAdvertisementQueries->getList($companyId, $location->id, $filterData);

        return [
            'advertisements' => PosAdvertisementListResource::collection($advertisements),
        ];
    }
}
