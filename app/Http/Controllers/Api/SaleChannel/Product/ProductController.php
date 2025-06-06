<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Product;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\EcommerceProductListResource;
use App\Domains\Product\Resources\EcommerceProductStockListResource;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
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
            'article_number' => ['sometimes', 'nullable'],
            'has_article_number' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'has_article_number' => array_key_exists(
                'has_article_number',
                $request->all()
            ) ? (bool) $validatedData['has_article_number'] : null,
            'article_number' => array_key_exists(
                'article_number',
                $request->all()
            ) ? (string) $validatedData['article_number'] : null,
        ];

        $products = $this->productQueries->getActivePaginatedRegularProductsForEcommerce(
            $saleChannel->getCompanyId(),
            $filteredData
        );

        return [
            'products' => EcommerceProductListResource::collection($products->getCollection()),
            'total_records' => $products->total(),
            'last_page' => $products->lastPage(),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
        ];
    }

    public function getProductStock(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:product_id,stock'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getInventoriesByLocation(
            $saleChannel->getDefaultLocationId(),
            $filteredData
        );

        return [
            'products' => EcommerceProductStockListResource::collection($inventories->getCollection()),
            'total_records' => $inventories->total(),
            'last_page' => $inventories->lastPage(),
            'current_page' => $inventories->currentPage(),
            'per_page' => $inventories->perPage(),
        ];
    }

    public function getArticleNumbers(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $productQueries = resolve(ProductQueries::class);
        $articleNumbers = $productQueries->getProductsArticleNumberForEcommerce($saleChannel->getCompanyId());

        return [
            'article_numbers' => $articleNumbers->pluck('article_number'),
        ];
    }

    public function saveProductChannelReference(Request $request): void
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')],
            'external_product_id' => ['required', 'string'],
            'external_variant_id' => ['sometimes', 'nullable', 'string'],
        ]);

        $data = [
            'sale_channel_id' => $saleChannel->id,
            'product_id' => $validatedData['product_id'],
            'external_product_id' => $validatedData['external_product_id'],
            'external_variant_id' => $validatedData['external_variant_id'] ?? null,
        ];

        $productChannelReference = resolve(ProductChannelReferenceQueries::class);
        $productChannelReference->addNew($data);
    }
}
