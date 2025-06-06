<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\DreamPrice;

use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Resources\EcommerceDreamPriceApiListResource;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class DreamPriceController extends Controller
{
    public function __construct(
        protected DreamPriceQueries $dreamPriceQueries
    ) {
    }

    public function getList(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
        ];

        $lengthAwarePaginator = $this->dreamPriceQueries->getListWithProductsInEcommerce(
            $saleChannel->getCompanyId(),
            $saleChannel->getDefaultLocationId(),
            $filteredData
        );

        return [
            'dream_prices' => EcommerceDreamPriceApiListResource::collection($lengthAwarePaginator->getCollection()),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getDreamPriceProductList(Request $request): array
    {
        $saleChannel = $request->user();

        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'dream_price_id' => ['required', 'integer'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'dream_price_id' => $validatedData['dream_price_id'],
        ];

        $lengthAwarePaginator = $dreamPriceProductQueries->getDreamPriceProduct($filteredData);

        return [
            'data' => $lengthAwarePaginator->getCollection(),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }
}
