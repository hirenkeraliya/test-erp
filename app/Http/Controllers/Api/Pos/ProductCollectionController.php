<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\PosProductCollectionListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
    public function __construct(
        protected ProductCollectionQueries $productCollectionQueries
    ) {
    }

    public function getPaginatedList(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

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

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $productCollections = $this->productCollectionQueries->getPaginatedProductCollectionsForPos(
            $filteredData,
            $companyId
        );

        return [
            'product_collections' => PosProductCollectionListResource::collection(
                $productCollections->getCollection()
            ),
            'total_records' => $productCollections->total(),
            'last_page' => $productCollections->lastPage(),
            'current_page' => $productCollections->currentPage(),
            'per_page' => $productCollections->perPage(),
        ];
    }
}
