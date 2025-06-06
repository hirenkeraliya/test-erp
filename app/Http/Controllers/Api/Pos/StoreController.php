<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Store\Resources\StoreBasicDetailsResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function cashierStores(Request $request, CashierQueries $cashierQueries): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $collection = $cashierQueries->loadLocationsAndGetWithBasicColumns($cashier, $afterUpdatedAt);

        return [
            'stores' => StoreBasicDetailsResource::collection($collection),
            'locations' => StoreBasicDetailsResource::collection($collection),
        ];
    }
}
