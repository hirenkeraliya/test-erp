<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Resources\PosCashbackListResource;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashbackController extends Controller
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

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $cashbackQueries = resolve(CashbackQueries::class);
        $cashbackList = $cashbackQueries->getListForPosWithRelatedData($location, $afterUpdatedAt);

        return [
            'cashbacks' => PosCashbackListResource::collection($cashbackList),
        ];
    }
}
