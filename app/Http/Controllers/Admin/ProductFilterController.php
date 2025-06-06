<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductFilterController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->productQueries->getActiveFilteredProducts($filterData, session('admin_company_id')),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredInventoryProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->productQueries->getActiveFilteredInventoryProducts(
                $filterData,
                session('admin_company_id')
            ),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
        ];

        $products = $this->productQueries->getActiveProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('admin_company_id')
        );

        return [
            'products' => $products->toArray(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredInventoryProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'has_inventory' => $request->input('has_inventory'),
            'location_id' => $request->input('location_id'),
        ];

        $products = $this->productQueries->getActiveInventoryProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('admin_company_id')
        );

        return [
            'products' => $products->toArray(),
        ];
    }

    /**
     * @return array<string, Product>
     */
    public function getProduct(int $productId): array
    {
        return [
            'product' => $this->productQueries->getActiveProductWithBasicColumnsById(
                $productId,
                session('admin_company_id')
            ),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredRegularProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
        ];

        $products = $this->productQueries->getActiveRegularProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('admin_company_id')
        );

        return [
            'products' => $products->toArray(),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredRegularProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->productQueries->getActiveFilteredRegularProducts(
                $filterData,
                session('admin_company_id')
            ),
        ];
    }
}
