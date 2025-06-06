<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Exports\ProfitsAndLossesReportExport;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\AdminProfitsAndLossesReportListResource;
use App\Domains\Product\Services\ProfitAndLossReportService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfitAndLossReportController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $colorId = null;
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $regionQueries = resolve(RegionQueries::class);
        $regions = $regionQueries->getRegionByCompanyId($companyId);

        $locationId = (int) $request->get('location_id');
        $selectedLocations = null;
        $color = null;
        $selectedColors = null;
        $selectedProduct = null;

        if (0 !== $locationId) {
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);

            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        if ($request->get('color_id')) {
            $colorId = (int) $request->get('color_id');
            $colorQueries = resolve(ColorQueries::class);
            $color = $colorQueries->getById($colorId, $companyId);
        }

        $type = $request->get('type');
        $dateRange = [now()->startOfDay()->format('Y-m-d H:i:s'), now()->endOfDay()->format('Y-m-d H:i:s')];

        if ('year' === $type) {
            $dateRange = [now()->startOfYear()->format('Y-m-d H:i:s'), now()->endOfYear()->format('Y-m-d H:i:s')];
        }

        if ('month' === $type) {
            $dateRange = [
                now()->startOfMonth()->format('Y-m-d H:i:s'),
                now()->endOfMonth()->format('Y-m-d H:i:s'),
            ];
        }

        if ($request->has('date_range') && is_array($request->get('date_range'))) {
            $dateRange = $request->get('date_range');

            $dateRange = [$dateRange[0] . ' 00:00:00', $dateRange[1] . ' 23:59:59'];
        }

        if ($request->has('date')) {
            $endDate = $request->get('date');
            $saleQueries = resolve(SaleQueries::class);
            /** @var Carbon $firstSaleHappenedAt */
            $firstSaleHappenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $saleQueries->getFirstSaleHappenedAt());

            $dateRange = [$firstSaleHappenedAt, CommonFunctions::addEndTime($endDate)];
        }

        if ($request->has('product_id')) {
            $productQueries = resolve(ProductQueries::class);
            $product = $productQueries->getByIdOnlyName((int) $request->get('product_id'), $companyId);

            $selectedProduct = [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }

        $articleNumber = null;
        if ($request->has('product_article_number')) {
            $articleNumber = $request->get('product_article_number');
        }

        $dashboardFilterData = [
            'location_ids' => $locationId > 0 ? [$locationId] : null,
            'product_id' => $request->has('product_id') ? $request->get('product_id') : null,
            'selectedProduct' => $selectedProduct,
            'selectedLocations' => $selectedLocations,
            'articleNumber' => $articleNumber,
            'dateRange' => $dateRange,
            'sort_by' => $type ? 'units_sold' : null,
            'sort_direction' => $type ? 'desc' : null,
        ];

        if ($color) {
            $selectedColors = [
                'code' => $color->code,
                'id' => $color->id,
                'name' => $color->name,
            ];

            $dashboardFilterData['color_ids'] = $colorId > 0 ? [$colorId] : null;
            $dashboardFilterData['selectedColors'] = $selectedColors;
        }

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections(session('admin_company_id'));

        return Inertia::render('reports/profits_and_losses_report/Index', [
            'locations' => $locations,
            'dashboardFilterData' => $dashboardFilterData,
            'regions' => $regions,
            'productCollections' => $productCollections,
            'exportPermission' => PermissionList::getExportPermissionName('profit_and_loss_report'),
            'helpCenterMessages' => 'Display the profits and losses report display amount of sale collection, sales, return sales, profits, and losses. Additionally, provide profits and losses on a product-wise basis. Only active & is selling item, regular, pending/complete credit, and pending/complete layaway sales is considered. Advanced filters, search options, and seamless export capabilities are provided for detailed analysis and insights.',
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetch(Request $request): array
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
            'counter_ids' => $request->get('counter_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $this->productQueries->getPaginatedProfitsAndLossesReport($filterData, $companyId);

        $consolidatedProducts = $this->productQueries->getFilteredTotalsForProfitsAndLossesReport(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminProfitsAndLossesReportListResource::collection($lengthAwarePaginator),
            'total_units_sold' => $consolidatedProducts->sum('total_quantity_sold'),
            'total_sales' => $consolidatedProducts->sum('total_amount_sold'),
            'total_units_return' => $consolidatedProducts->sum('total_quantity_returned'),
            'total_sale_returns' => $consolidatedProducts->sum('total_returned_amount'),
            'sales_collection' => ($consolidatedProducts->sum('total_amount_sold') - $consolidatedProducts->sum(
                'total_returned_amount'
            )),
            'total_purchase_cost' => $consolidatedProducts->sum('total_purchase_cost'),
            'total_profits_or_losses' => CommonFunctions::numberFormat(
                $consolidatedProducts->sum('total_amount_sold') - ($consolidatedProducts->sum(
                    'total_purchase_cost'
                ) + $consolidatedProducts->sum('total_returned_amount'))
            ),
        ];
    }

    public function export(string $filename, Request $request): BinaryFileResponse
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
            'counter_ids' => $request->get('counter_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'export_columns' => $request->get('export_columns'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $products = $this->productQueries->getProfitsAndLossesReportForExport($filterData, session('admin_company_id'));

        return Excel::download(new ProfitsAndLossesReportExport($products, $filteredColumns), $filename);
    }

    public function print(Request $request): string
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
            'counter_ids' => $request->get('counter_ids'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('admin_company_id');

        $profitAndLossReportService = resolve(ProfitAndLossReportService::class);

        return $profitAndLossReportService->print($filterData, $companyId, $filteredColumns);
    }
}
