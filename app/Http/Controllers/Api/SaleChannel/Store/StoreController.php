<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Store;

use App\Domains\Location\LocationQueries;
use App\Domains\Store\Resources\EcommerceStoreListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(
        protected LocationQueries $locationQueries
    ) {
    }

    public function getStoreList(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'sort_by' => ['sometimes', 'string', 'in:id,name,code'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
        ];

        $locations = $this->locationQueries->getStoresByCompanyIdForEcommerce(
            $saleChannel->getCompanyId(),
            $filteredData
        );

        return [
            'stores' => EcommerceStoreListResource::collection($locations->getCollection()),
            'locations' => EcommerceStoreListResource::collection($locations->getCollection()),
            'total_records' => $locations->total(),
            'last_page' => $locations->lastPage(),
            'current_page' => $locations->currentPage(),
            'per_page' => $locations->perPage(),
        ];
    }
}
