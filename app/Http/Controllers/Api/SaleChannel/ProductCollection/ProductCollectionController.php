<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\ProductCollection;

use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\EcommerceProductCollectionListResource;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
    public function __construct(
        protected ProductCollectionQueries $productCollectionQueries
    ) {
    }

    public function getPaginatedList(Request $request): array
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

        $productCollections = $this->productCollectionQueries->getPaginatedProductCollectionsForEcommerce(
            $filteredData,
            $saleChannel->getCompanyId()
        );

        return [
            'product_collections' => EcommerceProductCollectionListResource::collection(
                $productCollections->getCollection()
            ),
            'total_records' => $productCollections->total(),
            'last_page' => $productCollections->lastPage(),
            'current_page' => $productCollections->currentPage(),
            'per_page' => $productCollections->perPage(),
        ];
    }

    public function getProductIds(Request $request): array
    {
        $saleChannel = $request->user();

        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'product_collection_id' => ['required', 'integer'],
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
            'product_collection_id' => $validatedData['product_collection_id'],
        ];

        $lengthAwarePaginator = $productCollectionProductQueries->getProductCollectionProducts($filteredData);

        return [
            'product_ids' => $lengthAwarePaginator->pluck('product_id'),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }
}
