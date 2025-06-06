<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Exports\OnlineProductReportExport;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\AdminOnlineProductsReportListResource;
use App\Domains\Product\Services\ProductReportService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OnlineProductsReportController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $regionQueries = resolve(RegionQueries::class);
        $regions = $regionQueries->getRegionByCompanyId($companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        return Inertia::render('reports/online_products_report/Index', [
            'locations' => $locations,
            'productCollections' => $productCollections,
            'regions' => $regions,
            'exportPermission' => PermissionList::getExportPermissionName('online_product_report'),
            'helpCenterMessages' => 'The product report display each product units sold, orders, units returned, and return orders.',
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchOnlineProductsReport(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'size_ids' => $request->get('size_ids'),
            'color_ids' => $request->get('color_ids'),
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'date_range' => $request->get('date_range'),
            'tag_ids' => $request->get('tag_ids'),
            'region_ids' => $request->get('region_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $this->productQueries->getPaginatedOnlineProductsReport($filterData, $companyId);

        $consolidatedProducts = $this->productQueries->getOnlineProductsReportForExport($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminOnlineProductsReportListResource::collection($lengthAwarePaginator),
            'total_units_sold' => $consolidatedProducts->sum('sum_order_quantity'),
            'total_sales' => $consolidatedProducts->sum('sum_order_amount'),
            'total_units_return' => $consolidatedProducts->sum('sum_order_return_quantity'),
            'total_sale_returns' => $consolidatedProducts->sum('sum_order_return_amount'),
            'sales_collection' => ($consolidatedProducts->sum('sum_order_amount') - $consolidatedProducts->sum(
                'sum_order_return_amount'
            )),
        ];
    }

    public function exportOnlineProductsReport(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'size_ids' => $request->get('size_ids'),
            'color_ids' => $request->get('color_ids'),
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'date_range' => $request->get('date_range'),
            'tag_ids' => $request->get('tag_ids'),
            'region_ids' => $request->get('region_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'export_columns' => $request->get('export_columns'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $products = $this->productQueries->getOnlineProductsReportForExport($filterData, session('admin_company_id'));

        return Excel::download(new OnlineProductReportExport($products, $filteredColumns), $filename);
    }

    public function printOnlineProducts(Request $request): string
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'size_ids' => $request->get('size_ids'),
            'color_ids' => $request->get('color_ids'),
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'date_range' => $request->get('date_range'),
            'tag_ids' => $request->get('tag_ids'),
            'region_ids' => $request->get('region_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $companyId = session('admin_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productService = resolve(ProductReportService::class);

        return $productService->onlineProductPrint($filterData, $companyId, $filteredColumns);
    }
}
