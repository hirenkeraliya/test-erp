<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Inventory\Exports\InventoryExport;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\InventoryReportResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryReportController extends Controller
{
    public function fetchInventories(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'color_id' => $request->get('color_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'size_id' => $request->get('size_id'),
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'stock_type' => $request->get('stock_type'),
            'style_ids' => $request->get('style_ids'),
            'selling_type' => $request->get('selling_type'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $companyId = (int) $request->get('external_company_id');

        $lengthAwarePaginator = $inventoryQueries->inventoryReportsList($filterData, $companyId);

        $totalCount = $inventoryQueries->getFilteredTotalsForInventoryReport($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => InventoryReportResource::collection($lengthAwarePaginator),
            'total_available_stock' => $totalCount['total_available_stock'],
            'total_current_stock' => $totalCount['total_current_stock'],
            'total_reserved_stock' => $totalCount['total_reserved_stock'],
            'total_transit_stock' => $totalCount['total_transit_stock'],
        ];
    }

    public function getStoresWarehousesAndRegions(Request $request): array
    {
        $companyId = (int) $request->get('external_company_id');

        return [
            'stores' => $this->getStores($companyId),
            'warehouses' => $this->getWarehouses($companyId),
            'regions' => $this->getRegions($companyId),
        ];
    }

    public function getStoresAndRegions(Request $request): array
    {
        $companyId = (int) $request->get('external_company_id');

        return [
            'stores' => $this->getStores($companyId),
            'regions' => $this->getRegions($companyId),
        ];
    }

    public function getWarehousesAndRegions(Request $request): array
    {
        $companyId = (int) $request->get('external_company_id');

        return [
            'warehouses' => $this->getWarehouses($companyId),
            'regions' => $this->getRegions($companyId),
        ];
    }

    public function exportInventories(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'color_id' => $request->get('color_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'size_id' => $request->get('size_id'),
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'stock_type' => $request->get('stock_type'),
            'style_ids' => $request->get('style_ids'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = (int) $request->get('external_company_id');
        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->inventoryListsForExport($filterData, $companyId);

        return Excel::download(new InventoryExport($inventories, $filteredColumns), $filename);
    }

    public function getFilteredInventoryProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        $productQueries = resolve(ProductQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'products' => $productQueries->getActiveFilteredInventoryProducts($filterData, $companyId),
        ];
    }

    public function getFilteredInventoryCategories(Request $request): array
    {
        $categoryQueries = resolve(CategoryQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'categories' => $categoryQueries->getFilteredCategoriesByCompanyId(
                $request->input('search_text'),
                $companyId
            ),
        ];
    }

    public function getFilteredInventoryBrands(Request $request): array
    {
        $brandQueries = resolve(BrandQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'brands' => $brandQueries->getFilteredBrandsByCompanyId($request->input('search_text'), $companyId),
        ];
    }

    public function getFilteredInventorySizes(Request $request): array
    {
        $sizeQueries = resolve(SizeQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'sizes' => $sizeQueries->getFilteredSizesByCompanyId($request->input('search_text'), $companyId),
        ];
    }

    public function getFilteredInventoryColors(Request $request): array
    {
        $colorQueries = resolve(ColorQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'colors' => $colorQueries->getFilteredColorsByCompanyId($request->input('search_text'), $companyId),
        ];
    }

    public function getFilteredInventoryAttributes(Request $request): array
    {
        $companyId = (int) $request->get('external_company_id');

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes($companyId);
        }

        return [
            'attributes' => $attributes ?? collect([]),
        ];
    }

    public function getFilteredInventoryDepartments(Request $request): array
    {
        $departmentQueries = resolve(DepartmentQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'departments' => $departmentQueries->getFilteredDepartmentsByCompanyId(
                $request->input('search_text'),
                $companyId
            ),
        ];
    }

    public function getFilteredInventoryArticleNumbers(Request $request): array
    {
        $productQueries = resolve(ProductQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'articleNumbers' => $productQueries->getFilteredArticleNumberByCompanyId(
                $request->input('search_text'),
                $companyId
            ),
        ];
    }

    public function getFilteredInventoryTags(Request $request): array
    {
        $tagQueries = resolve(TagQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'tags' => $tagQueries->getFilteredTagsByCompanyId($request->input('search_text'), $companyId),
        ];
    }

    public function getFilteredInventoryStyles(Request $request): array
    {
        $styleQueries = resolve(StyleQueries::class);

        $companyId = (int) $request->get('external_company_id');

        return [
            'styles' => $styleQueries->getFilteredStylesByCompanyId($request->input('search_text'), $companyId),
        ];
    }

    private function getStores(int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getWithExternalInventoriesByType($companyId, LocationTypes::STORE->value);
    }

    private function getWarehouses(int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getWithExternalInventoriesByType($companyId, LocationTypes::WAREHOUSE->value);
    }

    private function getRegions(int $companyId): Collection
    {
        $regionQueries = resolve(RegionQueries::class);

        return $regionQueries->getRegionByCompanyId($companyId);
    }
}
