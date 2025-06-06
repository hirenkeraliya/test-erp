<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use App\Domains\AggregateProcessTracker\Services\AggregateProcessTrackerService;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughDateTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughMainReportTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughBalanceExport;
use App\Domains\SellThroughAggregate\Exports\SellThroughReceivedExport;
use App\Domains\SellThroughAggregate\Exports\SellThroughSoldExport;
use App\Domains\SellThroughAggregate\Jobs\UpdateDailyAggregateMainDataJob;
use App\Domains\SellThroughAggregate\Services\SellThroughByAttributeServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByBrandServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByColorServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByDepartmentServices;
use App\Domains\SellThroughAggregate\Services\SellThroughBySizeServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByStyleServices;
use App\Domains\SellThroughAggregate\Services\SellThroughCategoryServices;
use App\Domains\SellThroughAggregate\Services\SellThroughLocationServices;
use App\Domains\SellThroughAggregate\Services\SellThroughProductArticleNumberServices;
use App\Domains\SellThroughAggregate\Services\SellThroughProductUpcServices;
use App\Domains\SellThroughAggregate\Services\SellThroughSummaryServices;
use App\Http\Controllers\Controller;
use App\Models\AggregateProcessTracker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughAggregateReportController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        $locationId = (int) $request->get('location_id');
        $mainReportType = $locationId > 0 ? SellThroughMainReportTypes::BY_LOCATION->value : SellThroughMainReportTypes::BY_COMPANY->value;
        $productId = (int) $request->get('product_id');
        $articleNumber = $request->get('article_number');
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');
        $reportType = (int) $request->get('report_type');
        $attributeType = (int) $request->get('attribute_type');

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        $helpCenterMessages = '
            <p> The sell-through report display comprehensive analysis and insights by advanced filters such as categories, sizes, stores, article numbers and till selected date. Additionally, display pie and bar charts for visual representation. </p>

            <div>
                <b>Inclusions For Stock Received: </b>
                <p>We have the 1. Goods Received Note 2. Stock Adjustment In 3. Stock Adjustment Out 4. Stock Transfer 5. Delivery Order. By default 1, 2 and 3 are selected and below having the location selections. by default all stores is selected behind the scene. and this are only based on the stock which is in received column.</p>
            </div>

            <div>
                <b>Filter By: </b>
                <p><b>All (Default Selection): </b> The "Sold" column will include the sum of actual sold items and free items sold.</p>
                <p><b>Only Sold: </b> The "Sold" column will show only the actual sold items.</p>
                <p><b>Only Free Items Sold: </b> The "Sold" column will show only the free items sold.</p>
            </div>


            <div>
                <b>Location Selection: </b>
                <p><b>Store or Warehouse: </b> You can choose between store or warehouse as the selection type.</p>
                <p><b>Multi-selection: </b> This allows you to select multiple locations.</p>
                <p><b>Data: </b> Based on the selected locations, the data will be filtered accordingly.</p>
            </div>
        ';

        $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);
        $aggregateProcessTracker = $aggregateProcessTrackerQueries->getLastRefreshDateAndStatusForJobType(
            AggregateProcessTrackerModules::SELL_THROUGH->value,
            $companyId
        );

        $aggregateProcessTrackerService = resolve(AggregateProcessTrackerService::class);
        $aggregateProcessTracker = $aggregateProcessTrackerService->aggregateProcessTracker($aggregateProcessTracker);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'))->all();
        }

        return Inertia::render('reports/sell_through_aggregate_reports/Index', [
            'sellThroughTypes' => SellThroughTypes::getList(),
            'sellThroughFilterTypes' => SellThroughFilterTypes::getList(),
            'staticSellThroughFilterTypes' => SellThroughFilterTypes::getFormattedArrayForStaticUse(),
            'sellThroughMainReportTypes' => SellThroughMainReportTypes::getList(),
            'staticSellThroughMainReportTypes' => SellThroughMainReportTypes::getFormattedArrayForStaticUse(),
            'staticSellThroughTypes' => SellThroughTypes::getFormattedArrayForStaticUse(),
            'sellThroughIncludeTypes' => SellThroughIncludeTypes::getList(),
            'staticSellThroughIncludeTypes' => SellThroughIncludeTypes::getFormattedArrayForStaticUse(),
            'sellThroughDateTypes' => SellThroughDateTypes::getList(),
            'staticSellThroughDateTypes' => SellThroughDateTypes::getFormattedArrayForStaticUse(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'productCollections' => $productCollections,
            'exportPermission' => PermissionList::getExportPermissionName('sell_through'),
            'dashboardFilterData' => [
                'location_id' => $locationId > 0 ? $locationId : null,
                'product_id' => $productId > 0 ? $productId : null,
                'article_number' => $articleNumber ?? null,
                'selectedArticleNumbers' => $articleNumber ? [
                    'article_number' => $articleNumber,
                ] : [],
                'main_report_type' => $articleNumber ? $mainReportType : null,
                'sort_direction' => $sortDirection,
                'sort_by' => $sortBy,
                'report_type' => $articleNumber ? SellThroughTypes::BY_MASTER_PRODUCT->value : $reportType,
                'attribute_type' => $attributeType,
                'select_date_type' => SellThroughDateTypes::ACCUMULATED->value,
                'default_include_type' => $this->getDefaultIncludeType($locationId > 0, $productId > 0),
            ],
            'aggregateProcessTracker' => $aggregateProcessTracker,
            'helpCenterMessages' => $helpCenterMessages,
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchSellThroughDetails(Request $request): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $sellThroughByColorServices = resolve(SellThroughByColorServices::class);

            return $sellThroughByColorServices->fetchSellThroughDetailsByColor($filterData, $companyId);
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

            return $sellThroughByStyleServices->fetchSellThroughDetailsByStyle($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

            return $sellThroughProductArticleNumberServices->fetchSellThroughDetailsByProductArticleNumber(
                $filterData,
                $companyId
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

            return $sellThroughProductUpcServices->fetchSellThroughDetailsByProductUpc($filterData, $companyId);
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

            return $sellThroughLocationServices->fetchSellThroughDetailsByStore($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

            return $sellThroughByDepartmentServices->fetchSellThroughDetailsByDepartment($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

            return $sellThroughByBrandServices->fetchSellThroughDetailsByBrand($filterData, $companyId);
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

            return $sellThroughCategoryServices->fetchSellThroughDetailsByCategory($filterData, $companyId);
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $sellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

            return $sellThroughByAttributeServices->fetchSellThroughDetailsByAttribute($filterData, $companyId);
        }

        $sellThroughBySizeServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughBySizeServices->fetchSellThroughDetailsBySize($filterData, $companyId);
    }

    public function fetchSellThroughDetailsForChart(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getFilterData($request);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $sellThroughByColorServices = resolve(SellThroughByColorServices::class);

            return $sellThroughByColorServices->sellThroughDetailsByColorForChart($filterData, $companyId);
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

            return $sellThroughByStyleServices->sellThroughDetailsByStyleForChart($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

            return $sellThroughProductArticleNumberServices->sellThroughDetailsByProductArticleNumberForChart(
                $filterData,
                $companyId
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

            return $sellThroughProductUpcServices->sellThroughDetailsByProductUpcForChart($filterData, $companyId);
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

            return $sellThroughLocationServices->sellThroughDetailsByStoreForChart($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

            return $sellThroughByDepartmentServices->sellThroughDetailsByDepartmentForChart(
                $filterData,
                $companyId
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

            return $sellThroughByBrandServices->sellThroughDetailsByBrandForChart($filterData, $companyId);
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

            return $sellThroughCategoryServices->sellThroughDetailsByCategoryForChart($filterData, $companyId);
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $sellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

            return $sellThroughByAttributeServices->sellThroughDetailsByAttributeForChart($filterData, $companyId);
        }

        $sellThroughBySizeServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughBySizeServices->sellThroughDetailsBySizeForChart($filterData, $companyId);
    }

    public function printSellThroughAggregateDetails(Request $request): string
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getFilterData($request);
        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $sellThroughByColorServices = resolve(SellThroughByColorServices::class);

            return $sellThroughByColorServices->printSellThroughDetailsByColor(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

            return $sellThroughByStyleServices->printSellThroughDetailsByStyle(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

            return $sellThroughProductArticleNumberServices->printSellThroughDetailsByProductArticleNumber(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

            return $sellThroughProductUpcServices->printSellThroughDetailsByProductUpc(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

            return $sellThroughLocationServices->printSellThroughDetailsByStore(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

            return $sellThroughByDepartmentServices->printSellThroughDetailsByDepartment(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

            return $sellThroughByBrandServices->printSellThroughDetailsByBrand(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::SUMMARY->value === $filterData['report_type']) {
            $sellThroughSummaryServices = resolve(SellThroughSummaryServices::class);

            return $sellThroughSummaryServices->printSellThroughDetails($filterData, $companyId, $getFilterLabels);
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

            return $sellThroughCategoryServices->printSellThroughDetailsByCategory(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $sellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

            return $sellThroughByAttributeServices->printSellThroughDetailsByAttribute(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        $sellThroughBySizeServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughBySizeServices->printSellThroughDetailsBySize(
            $filterData,
            $companyId,
            $getFilterLabels
        );
    }

    public function exportSellThroughAggregateDetails(Request $request, string $filename): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getFilterData($request);
        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $sellThroughByColorServices = resolve(SellThroughByColorServices::class);

            return $sellThroughByColorServices->exportSellThroughDetailsByColor(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

            return $sellThroughByStyleServices->exportSellThroughDetailsByStyle(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

            return $sellThroughProductArticleNumberServices->exportSellThroughDetailsByProductArticleNumber(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

            return $sellThroughProductUpcServices->exportSellThroughDetailsByProductUpc(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

            return $sellThroughLocationServices->exportSellThroughDetailsByStore(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

            return $sellThroughByDepartmentServices->exportSellThroughDetailsByDepartment(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

            return $sellThroughByBrandServices->exportSellThroughDetailsByBrand(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

            return $sellThroughCategoryServices->exportSellThroughDetailsByCategory(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $sellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

            return $sellThroughByAttributeServices->exportSellThroughDetailsByAttribute(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        $sellThroughBySizeServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughBySizeServices->exportSellThroughDetailsBySize(
            $filterData,
            $companyId,
            $filename,
            $getFilterLabels
        );
    }

    private function getFilterData(Request $request): array
    {
        return [
            'date' => $request->get('date'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
            'report_type' => (int) $request->get('report_type'),
            'attribute_type' => (int) $request->get('attribute_type'),
            'filter_by' => (int) $request->get('filter_by'),
            'per_page' => $request->get('per_page'),
            'page' => $request->get('page'),
            'product_id' => $request->get('product_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'size_id' => $request->get('size_id'),
            'color_ids' => $request->get('color_ids'),
            'department_ids' => (array) $request->get('department_ids'),
            'tag_ids' => (array) $request->get('tag_ids'),
            'article_numbers' => (array) $request->get('article_numbers'),
            'style_ids' => (array) $request->get('style_ids'),
            'include_by' => is_array(
                $request->get('include_by')
            ) ? array_map('intval', $request->get('include_by')) : [],
            'includes_by_goods_receive_note_in_location_ids' => is_array(
                $request->get('includes_by_goods_receive_note_in_location_ids')
            ) ? array_map('intval', $request->get('includes_by_goods_receive_note_in_location_ids')) : [],
            'includes_by_goods_receive_note_out_location_ids' => is_array(
                $request->get('includes_by_goods_receive_note_out_location_ids')
            ) ? array_map('intval', $request->get('includes_by_goods_receive_note_out_location_ids')) : [],
            'includes_by_stock_adjustment_in_location_ids' => is_array(
                $request->get('includes_by_stock_adjustment_in_location_ids')
            ) ? array_map('intval', $request->get('includes_by_stock_adjustment_in_location_ids')) : [],
            'includes_by_stock_adjustment_out_location_ids' => is_array(
                $request->get('includes_by_stock_adjustment_out_location_ids')
            ) ? array_map('intval', $request->get('includes_by_stock_adjustment_out_location_ids')) : [],
            'includes_by_stock_transfer_in_location_ids' => is_array(
                $request->get('includes_by_stock_transfer_in_location_ids')
            ) ? array_map('intval', $request->get('includes_by_stock_transfer_in_location_ids')) : [],
            'includes_by_stock_transfer_out_location_ids' => is_array(
                $request->get('includes_by_stock_transfer_out_location_ids')
            ) ? array_map('intval', $request->get('includes_by_stock_transfer_out_location_ids')) : [],
            'includes_by_delivery_order_in_location_ids' => is_array(
                $request->get('includes_by_delivery_order_in_location_ids')
            ) ? array_map('intval', $request->get('includes_by_delivery_order_in_location_ids')) : [],
            'includes_by_delivery_order_out_location_ids' => is_array(
                $request->get('includes_by_delivery_order_out_location_ids')
            ) ? array_map('intval', $request->get('includes_by_delivery_order_out_location_ids')) : [],
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'search_text' => $request->get('search_text'),
            'attributes' => $request->get('attributes'),
            'export_columns' => $request->get('export_columns'),
        ];
    }

    private function getDefaultIncludeType(bool $isStoreIdSelected, bool $isProductSelected): array
    {
        if (! $isProductSelected) {
            return [];
        }

        if (! $isStoreIdSelected) {
            return [
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
            ];
        }

        return [
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
            SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
            SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
        ];
    }

    /**
     * @return array<mixed, array<'balance'|'location_name', mixed>>
     */
    public function fetchBalanceDetailsByUpc(Request $request, int $productId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

        return $sellThroughProductUpcServices->fetchBalanceDetailsByUpc($filterData, $productId, $companyId);
    }

    public function fetchSoldDetailsByUpc(Request $request, int $productId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

        return $sellThroughProductUpcServices->fetchSoldDetailsByUpc($filterData, $productId, $companyId);
    }

    public function fetchReceivedDetailsByUpc(Request $request, int $productId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);

        return $sellThroughProductUpcServices->fetchReceivedDetailsByUpc($filterData, $productId, $companyId);
    }

    public function exportBalanceDetailsByUpc(string $filename, Request $request): BinaryFileResponse
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchBalanceDetailsByUpc($filterData, $productId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByUpc(string $filename, Request $request): BinaryFileResponse
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchSoldDetailsByUpc($filterData, $productId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByUpc(string $filename, Request $request): BinaryFileResponse
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchReceivedDetailsByUpc($filterData, $productId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByUpc(Request $request): string
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchBalanceDetailsByUpc($filterData, $productId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_UPC->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByUpc(Request $request): string
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchSoldDetailsByUpc($filterData, $productId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_UPC->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByUpc(Request $request): string
    {
        $productId = (int) $request->productId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductUpcServices = resolve(SellThroughProductUpcServices::class);
        $data = $sellThroughProductUpcServices->fetchReceivedDetailsByUpc($filterData, $productId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_UPC->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByColor(Request $request, int $colorId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);

        return $sellThroughByStyleServices->fetchSoldDetailsByColor($filterData, $colorId, $companyId);
    }

    public function fetchReceivedDetailsByColor(Request $request, int $colorId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);

        return $sellThroughByStyleServices->fetchReceivedDetailsByColor($filterData, $colorId, $companyId);
    }

    public function fetchBalanceDetailsByColor(Request $request, int $colorId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);

        return $sellThroughByStyleServices->fetchBalanceDetailsByColor($filterData, $colorId, $companyId);
    }

    public function exportBalanceDetailsByColor(string $filename, Request $request): BinaryFileResponse
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchBalanceDetailsByColor($filterData, $colorId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByColor(string $filename, Request $request): BinaryFileResponse
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchSoldDetailsByColor($filterData, $colorId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByColor(string $filename, Request $request): BinaryFileResponse
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchReceivedDetailsByColor($filterData, $colorId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByColor(Request $request): string
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchBalanceDetailsByColor($filterData, $colorId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::COLORS->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByColor(Request $request): string
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchSoldDetailsByColor($filterData, $colorId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::COLORS->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByColor(Request $request): string
    {
        $colorId = (int) $request->colorId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByColorServices::class);
        $data = $sellThroughByStyleServices->fetchReceivedDetailsByColor($filterData, $colorId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::COLORS->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsBySize(Request $request, int $sizeId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughProductArticleNumberServices->fetchSoldDetailsBySize($filterData, $sizeId, $companyId);
    }

    public function fetchReceivedDetailsBySize(Request $request, int $sizeId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughProductArticleNumberServices->fetchReceivedDetailsBySize($filterData, $sizeId, $companyId);
    }

    public function fetchBalanceDetailsBySize(Request $request, int $sizeId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);

        return $sellThroughProductArticleNumberServices->fetchBalanceDetailsBySize($filterData, $sizeId, $companyId);
    }

    public function exportBalanceDetailsBySize(string $filename, Request $request): BinaryFileResponse
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchBalanceDetailsBySize($filterData, $sizeId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsBySize(string $filename, Request $request): BinaryFileResponse
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchSoldDetailsBySize($filterData, $sizeId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsBySize(string $filename, Request $request): BinaryFileResponse
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchReceivedDetailsBySize($filterData, $sizeId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsBySize(Request $request): string
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchBalanceDetailsBySize($filterData, $sizeId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_MASTER_PRODUCT->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsBySize(Request $request): string
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchSoldDetailsBySize($filterData, $sizeId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::SIZES->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsBySize(Request $request): string
    {
        $sizeId = (int) $request->sizeId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughBySizeServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchReceivedDetailsBySize($filterData, $sizeId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::SIZES->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByStyle(Request $request, int $styleId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

        return $sellThroughByStyleServices->fetchSoldDetailsByStyle($filterData, $styleId, $companyId);
    }

    public function fetchReceivedDetailsByStyle(Request $request, int $styleId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

        return $sellThroughByStyleServices->fetchReceivedDetailsByStyle($filterData, $styleId, $companyId);
    }

    public function fetchBalanceDetailsByStyle(Request $request, int $styleId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);

        return $sellThroughByStyleServices->fetchBalanceDetailsByStyle($filterData, $styleId, $companyId);
    }

    public function exportBalanceDetailsByStyle(string $filename, Request $request): BinaryFileResponse
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchBalanceDetailsByStyle($filterData, $styleId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByStyle(string $filename, Request $request): BinaryFileResponse
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchSoldDetailsByStyle($filterData, $styleId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByStyle(string $filename, Request $request): BinaryFileResponse
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchReceivedDetailsByStyle($filterData, $styleId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByStyle(Request $request): string
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchBalanceDetailsByStyle($filterData, $styleId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::STYLES->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByStyle(Request $request): string
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchSoldDetailsByStyle($filterData, $styleId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::STYLES->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByStyle(Request $request): string
    {
        $styleId = (int) $request->styleId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByStyleServices = resolve(SellThroughByStyleServices::class);
        $data = $sellThroughByStyleServices->fetchReceivedDetailsByStyle($filterData, $styleId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::STYLES->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByBrand(Request $request, int $brandId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

        return $sellThroughByBrandServices->fetchSoldDetailsByBrand($filterData, $brandId, $companyId);
    }

    public function fetchReceivedDetailsByBrand(Request $request, int $brandId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

        return $sellThroughByBrandServices->fetchReceivedDetailsByBrand($filterData, $brandId, $companyId);
    }

    public function fetchBalanceDetailsByBrand(Request $request, int $brandId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);

        return $sellThroughByBrandServices->fetchBalanceDetailsByBrand($filterData, $brandId, $companyId);
    }

    public function exportBalanceDetailsByBrand(string $filename, Request $request): BinaryFileResponse
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchBalanceDetailsByBrand($filterData, $brandId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByBrand(string $filename, Request $request): BinaryFileResponse
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchSoldDetailsByBrand($filterData, $brandId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByBrand(string $filename, Request $request): BinaryFileResponse
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchReceivedDetailsByBrand($filterData, $brandId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByBrand(Request $request): string
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchBalanceDetailsByBrand($filterData, $brandId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BRANDS->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByBrand(Request $request): string
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchSoldDetailsByBrand($filterData, $brandId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BRANDS->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByBrand(Request $request): string
    {
        $brandId = (int) $request->brandId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByBrandServices = resolve(SellThroughByBrandServices::class);
        $data = $sellThroughByBrandServices->fetchReceivedDetailsByBrand($filterData, $brandId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BRANDS->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByDepartment(Request $request, int $departmentId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

        return $sellThroughByDepartmentServices->fetchSoldDetailsByDepartment($filterData, $departmentId, $companyId);
    }

    public function fetchReceivedDetailsByDepartment(Request $request, int $departmentId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

        return $sellThroughByDepartmentServices->fetchReceivedDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );
    }

    public function fetchBalanceDetailsByDepartment(Request $request, int $departmentId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);

        return $sellThroughByDepartmentServices->fetchBalanceDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );
    }

    public function exportBalanceDetailsByDepartment(string $filename, Request $request): BinaryFileResponse
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchBalanceDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByDepartment(string $filename, Request $request): BinaryFileResponse
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchSoldDetailsByDepartment($filterData, $departmentId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByDepartment(string $filename, Request $request): BinaryFileResponse
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchReceivedDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByDepartment(Request $request): string
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchBalanceDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::DEPARTMENTS->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByDepartment(Request $request): string
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchSoldDetailsByDepartment($filterData, $departmentId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::DEPARTMENTS->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByDepartment(Request $request): string
    {
        $departmentId = (int) $request->departmentId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughByDepartmentServices = resolve(SellThroughByDepartmentServices::class);
        $data = $sellThroughByDepartmentServices->fetchReceivedDetailsByDepartment(
            $filterData,
            $departmentId,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::DEPARTMENTS->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByLocation(Request $request, int $locationId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

        return $sellThroughLocationServices->fetchSoldDetailsByLocation($filterData, $locationId, $companyId);
    }

    public function fetchReceivedDetailsByLocation(Request $request, int $locationId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

        return $sellThroughLocationServices->fetchReceivedDetailsByLocation($filterData, $locationId, $companyId);
    }

    public function fetchBalanceDetailsByLocation(Request $request, int $locationId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);

        return $sellThroughLocationServices->fetchBalanceDetailsByLocation($filterData, $locationId, $companyId);
    }

    public function exportBalanceDetailsByLocation(string $filename, Request $request): BinaryFileResponse
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchBalanceDetailsByLocation($filterData, $locationId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByLocation(string $filename, Request $request): BinaryFileResponse
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchSoldDetailsByLocation($filterData, $locationId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByLocation(string $filename, Request $request): BinaryFileResponse
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchReceivedDetailsByLocation($filterData, $locationId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByLocation(Request $request): string
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchBalanceDetailsByLocation($filterData, $locationId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::LOCATIONS->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByLocation(Request $request): string
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchSoldDetailsByLocation($filterData, $locationId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::LOCATIONS->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByLocation(Request $request): string
    {
        $locationId = (int) $request->locationId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughLocationServices = resolve(SellThroughLocationServices::class);
        $data = $sellThroughLocationServices->fetchReceivedDetailsByLocation($filterData, $locationId, $companyId);
        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::LOCATIONS->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByCategory(Request $request, int $categoryId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

        return $sellThroughCategoryServices->fetchSoldDetailsByCategory($filterData, $categoryId, $companyId);
    }

    public function fetchReceivedDetailsByCategory(Request $request, int $categoryId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

        return $sellThroughCategoryServices->fetchReceivedDetailsByCategory($filterData, $categoryId, $companyId);
    }

    public function fetchBalanceDetailsByCategory(Request $request, int $categoryId): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);

        return $sellThroughCategoryServices->fetchBalanceDetailsByCategory($filterData, $categoryId, $companyId);
    }

    public function exportBalanceDetailsByCategory(string $filename, Request $request): BinaryFileResponse
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchBalanceDetailsByCategory($filterData, $categoryId, $companyId);

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByCategory(string $filename, Request $request): BinaryFileResponse
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchSoldDetailsByCategory($filterData, $categoryId, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByCategory(string $filename, Request $request): BinaryFileResponse
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchReceivedDetailsByCategory($filterData, $categoryId, $companyId);

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByCategory(Request $request): string
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchBalanceDetailsByCategory($filterData, $categoryId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::CATEGORIES->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByCategory(Request $request): string
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchSoldDetailsByCategory($filterData, $categoryId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::CATEGORIES->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByCategory(Request $request): string
    {
        $categoryId = (int) $request->categoryId;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughCategoryServices = resolve(SellThroughCategoryServices::class);
        $data = $sellThroughCategoryServices->fetchReceivedDetailsByCategory($filterData, $categoryId, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::CATEGORIES->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByArticleNumber(Request $request, string $articleNumber): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

        return $sellThroughProductArticleNumberServices->fetchSoldDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );
    }

    public function fetchReceivedDetailsByArticleNumber(Request $request, string $articleNumber): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

        return $sellThroughProductArticleNumberServices->fetchReceivedDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );
    }

    public function fetchBalanceDetailsByArticleNumber(Request $request, string $articleNumber): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);

        return $sellThroughProductArticleNumberServices->fetchBalanceDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );
    }

    public function exportBalanceDetailsByArticleNumber(string $filename, Request $request): BinaryFileResponse
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchBalanceDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByArticleNumber(string $filename, Request $request): BinaryFileResponse
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchSoldDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByArticleNumber(string $filename, Request $request): BinaryFileResponse
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchReceivedDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByArticleNumber(Request $request): string
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchBalanceDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_MASTER_PRODUCT->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByArticleNumber(Request $request): string
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchSoldDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_MASTER_PRODUCT->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByArticleNumber(Request $request): string
    {
        $articleNumber = $request->articleNumber;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $sellThroughProductArticleNumberServices = resolve(SellThroughProductArticleNumberServices::class);
        $data = $sellThroughProductArticleNumberServices->fetchReceivedDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_MASTER_PRODUCT->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function fetchSoldDetailsByAttribute(Request $request, string $attribute): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

        return $SellThroughByAttributeServices->fetchSoldDetailsByAttribute($filterData, $attribute, $companyId);
    }

    public function fetchReceivedDetailsByAttribute(Request $request, string $attribute): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

        return $SellThroughByAttributeServices->fetchReceivedDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );
    }

    public function fetchBalanceDetailsByAttribute(Request $request, string $attribute): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);

        return $SellThroughByAttributeServices->fetchBalanceDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );
    }

    public function exportBalanceDetailsByAttribute(string $filename, Request $request): BinaryFileResponse
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchBalanceDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );

        return Excel::download(new SellThroughBalanceExport($data['data']), $filename);
    }

    public function exportSoldDetailsByAttribute(string $filename, Request $request): BinaryFileResponse
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchSoldDetailsByAttribute($filterData, $attribute, $companyId);

        return Excel::download(new SellThroughSoldExport($data['data']), $filename);
    }

    public function exportReceivedDetailsByAttribute(string $filename, Request $request): BinaryFileResponse
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchReceivedDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );

        return Excel::download(new SellThroughReceivedExport($data['data']), $filename);
    }

    public function printBalanceDetailsByAttribute(Request $request): string
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchBalanceDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_ATTRIBUTES->value);

        return $this->sellThroughBalanceCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printSoldDetailsByAttribute(Request $request): string
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchSoldDetailsByAttribute($filterData, $attribute, $companyId);

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_ATTRIBUTES->value);

        return $this->sellThroughSoldCommonView($data, $filterData, $reportType, $companyId);
    }

    public function printReceivedDetailsByAttribute(Request $request): string
    {
        $attribute = $request->attribute;

        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $SellThroughByAttributeServices = resolve(SellThroughByAttributeServices::class);
        $data = $SellThroughByAttributeServices->fetchReceivedDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );

        $reportType = SellThroughTypes::getFormattedCaseName(SellThroughTypes::BY_ATTRIBUTES->value);

        return $this->sellThroughReceivedCommonView($data, $filterData, $reportType, $companyId);
    }

    public function sellThroughBalanceCommonView(
        array $data,
        array $filterData,
        string $reportType,
        int $companyId
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        return view('prints.sell_through_balance_details', [
            'sellThroughBalanceDetails' => $data['data'],
            'company' => $company,
            'reportType' => $reportType,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function sellThroughSoldCommonView(
        array $data,
        array $filterData,
        string $reportType,
        int $companyId
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        return view('prints.sell_through_sold_details', [
            'sellThroughSoldDetails' => $data['data'],
            'company' => $company,
            'reportType' => $reportType,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function sellThroughReceivedCommonView(
        array $data,
        array $filterData,
        string $reportType,
        int $companyId
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        return view('prints.sell_through_received_details', [
            'sellThroughReceivedDetails' => $data['data'],
            'company' => $company,
            'reportType' => $reportType,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function getLatestDataSync(): array
    {
        $companyId = session('admin_company_id');

        $aggregateProcessTrackerQueries = resolve(AggregateProcessTrackerQueries::class);

        $aggregateProcessTracker = $aggregateProcessTrackerQueries->getLastRefreshDateAndStatusForJobType(
            AggregateProcessTrackerModules::SELL_THROUGH->value,
            $companyId
        );

        if (! $aggregateProcessTracker instanceof AggregateProcessTracker) {
            UpdateDailyAggregateMainDataJob::dispatch(date: Carbon::now()->format('Y-m-d'))
                ->onQueue(config('horizon.default_queue_name'));

            return [
                'message' => 'The data sync has started. This process may take a few moments.',
            ];
        }

        if (AggregateProcessTrackerStatuses::FAILED === $aggregateProcessTracker->status) {
            UpdateDailyAggregateMainDataJob::dispatch(date: Carbon::now()->format('Y-m-d'))
                ->onQueue(config('horizon.default_queue_name'));

            return [
                'message' => 'The previous sync failed. The data sync has started again. Please wait while it completes.',
            ];
        }

        if (AggregateProcessTrackerStatuses::IN_PROGRESS === $aggregateProcessTracker->status) {
            return [
                'message' => 'Oops, the sync is already in progress! Please wait until it finishes.',
            ];
        }

        UpdateDailyAggregateMainDataJob::dispatch(date: Carbon::now()->format('Y-m-d'))
            ->onQueue(config('horizon.default_queue_name'));

        return [
            'message' => 'The data sync has started. This process may take a few moments',
        ];
    }
}
