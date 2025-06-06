<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\Resources\StoreManagerAppCashierListResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashierController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getCashiers(Request $request): array
    {
        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filterData = [
            'location_id' => $validatedData['location_id'] ?? $validatedData['store_id'],
            'search_text' => $validatedData['search_text'] ?? null,
        ];

        $cashierQueries = resolve(CashierQueries::class);
        $cashiersList = $cashierQueries->getListForStoreManagerApp($filterData);

        return [
            'cashiers' => StoreManagerAppCashierListResource::collection($cashiersList),
        ];
    }
}
