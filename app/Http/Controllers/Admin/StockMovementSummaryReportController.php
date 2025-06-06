<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\AggregateProcessTracker\AggregateProcessTrackerQueries;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use App\Domains\AggregateProcessTracker\Services\AggregateProcessTrackerService;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughDateTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughMainReportTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Jobs\UpdateDailyAggregateMainDataJob;
use App\Domains\StockMovement\Services\StockMovementAttributeServices;
use App\Domains\StockMovement\Services\StockMovementByBrandServices;
use App\Domains\StockMovement\Services\StockMovementByColorServices;
use App\Domains\StockMovement\Services\StockMovementByDepartmentServices;
use App\Domains\StockMovement\Services\StockMovementBySizeServices;
use App\Domains\StockMovement\Services\StockMovementByStyleServices;
use App\Domains\StockMovement\Services\StockMovementCategoryServices;
use App\Domains\StockMovement\Services\StockMovementLocationServices;
use App\Domains\StockMovement\Services\StockMovementProductArticleNumberServices;
use App\Domains\StockMovement\Services\StockMovementProductUpcServices;
use App\Domains\StockMovement\Services\StockMovementSummaryServices;
use App\Http\Controllers\Controller;
use App\Models\AggregateProcessTracker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementSummaryReportController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        $helpCenterMessages = '
            <p> The stock movement report display comprehensive analysis and insights by advanced filters such as categories, sizes, stores, article numbers and till selected date. </p>

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

        return Inertia::render('reports/stock_movement_summary_reports/Index', [
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
            'aggregateProcessTracker' => $aggregateProcessTracker,
            'helpCenterMessages' => $helpCenterMessages,
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchStockMovementSummaryDetails(Request $request): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $stockMovementByColorServices = resolve(StockMovementByColorServices::class);

            return $stockMovementByColorServices->fetchStockMovementDetailsByColor($filterData, $companyId);
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $stockMovementByStyleServices = resolve(StockMovementByStyleServices::class);

            return $stockMovementByStyleServices->fetchStockMovementDetailsByStyle($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $stockMovementProductArticleNumberServices = resolve(StockMovementProductArticleNumberServices::class);

            return $stockMovementProductArticleNumberServices->fetchStockMovementDetailsByProductArticleNumber(
                $filterData,
                $companyId
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $stockMovementProductUpcServices = resolve(StockMovementProductUpcServices::class);

            return $stockMovementProductUpcServices->fetchStockMovementDetailsByProductUpc($filterData, $companyId);
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $stockMovementLocationServices = resolve(StockMovementLocationServices::class);

            return $stockMovementLocationServices->fetchStockMovementDetailsByStore($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $stockMovementByDepartmentServices = resolve(StockMovementByDepartmentServices::class);

            return $stockMovementByDepartmentServices->fetchStockMovementDetailsByDepartment($filterData, $companyId);
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $stockMovementByBrandServices = resolve(StockMovementByBrandServices::class);

            return $stockMovementByBrandServices->fetchStockMovementDetailsByBrand($filterData, $companyId);
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $stockMovementCategoryServices = resolve(StockMovementCategoryServices::class);

            return $stockMovementCategoryServices->fetchStockMovementDetailsByCategory($filterData, $companyId);
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $stockMovementAttributeServices = resolve(StockMovementAttributeServices::class);

            return $stockMovementAttributeServices->fetchStockMovementDetailsByAttribute($filterData, $companyId);
        }

        $stockMovementBySizeServices = resolve(StockMovementBySizeServices::class);

        return $stockMovementBySizeServices->fetchStockMovementDetailsBySize($filterData, $companyId);
    }

    public function printStockMovementDetails(Request $request): string
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getFilterData($request);
        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $stockMovementByColorServices = resolve(StockMovementByColorServices::class);

            return $stockMovementByColorServices->printStockMovementDetailsByColor(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $stockMovementByStyleServices = resolve(StockMovementByStyleServices::class);

            return $stockMovementByStyleServices->printStockMovementDetailsByStyle(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $stockMovementProductArticleNumberServices = resolve(StockMovementProductArticleNumberServices::class);

            return $stockMovementProductArticleNumberServices->printStockMovementDetailsByProductArticleNumber(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $stockMovementProductUpcServices = resolve(StockMovementProductUpcServices::class);

            return $stockMovementProductUpcServices->printStockMovementDetailsByProductUpc(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $stockMovementLocationServices = resolve(StockMovementLocationServices::class);

            return $stockMovementLocationServices->printStockMovementDetailsByStore(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $stockMovementByDepartmentServices = resolve(StockMovementByDepartmentServices::class);

            return $stockMovementByDepartmentServices->printStockMovementDetailsByDepartment(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $stockMovementByBrandServices = resolve(StockMovementByBrandServices::class);

            return $stockMovementByBrandServices->printStockMovementDetailsByBrand(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::SUMMARY->value === $filterData['report_type']) {
            $stockMovementSummaryServices = resolve(StockMovementSummaryServices::class);

            return $stockMovementSummaryServices->printStockMovementDetails($filterData, $companyId, $getFilterLabels);
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $stockMovementCategoryServices = resolve(StockMovementCategoryServices::class);

            return $stockMovementCategoryServices->printStockMovementDetailsByCategory(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $stockMovementAttributeServices = resolve(StockMovementAttributeServices::class);

            return $stockMovementAttributeServices->printStockMovementDetailsByAttribute(
                $filterData,
                $companyId,
                $getFilterLabels
            );
        }

        $stockMovementBySizeServices = resolve(StockMovementBySizeServices::class);

        return $stockMovementBySizeServices->printStockMovementDetailsBySize(
            $filterData,
            $companyId,
            $getFilterLabels
        );
    }

    public function exportStockMovementDetails(Request $request, string $filename): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getFilterData($request);
        $getFilterLabels = CommonFunctions::getFilterLabels($filterData, $companyId);

        if (SellThroughTypes::COLORS->value === $filterData['report_type']) {
            $stockMovementByColorServices = resolve(StockMovementByColorServices::class);

            return $stockMovementByColorServices->exportStockMovementDetailsByColor(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::STYLES->value === $filterData['report_type']) {
            $stockMovementByStyleServices = resolve(StockMovementByStyleServices::class);

            return $stockMovementByStyleServices->exportStockMovementDetailsByStyle(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $stockMovementProductArticleNumberServices = resolve(StockMovementProductArticleNumberServices::class);

            return $stockMovementProductArticleNumberServices->exportStockMovementDetailsByProductArticleNumber(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_UPC->value === $filterData['report_type']) {
            $stockMovementProductUpcServices = resolve(StockMovementProductUpcServices::class);

            return $stockMovementProductUpcServices->exportStockMovementDetailsByProductUpc(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::LOCATIONS->value === $filterData['report_type']) {
            $stockMovementLocationServices = resolve(StockMovementLocationServices::class);

            return $stockMovementLocationServices->exportStockMovementDetailsByStore(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::DEPARTMENTS->value) {
            $stockMovementByDepartmentServices = resolve(StockMovementByDepartmentServices::class);

            return $stockMovementByDepartmentServices->exportStockMovementDetailsByDepartment(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if ($filterData['report_type'] === SellThroughTypes::BRANDS->value) {
            $stockMovementByBrandServices = resolve(StockMovementByBrandServices::class);

            return $stockMovementByBrandServices->exportStockMovementDetailsByBrand(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::CATEGORIES->value === $filterData['report_type']) {
            $stockMovementCategoryServices = resolve(StockMovementCategoryServices::class);

            return $stockMovementCategoryServices->exportStockMovementDetailsByCategory(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        if (SellThroughTypes::BY_ATTRIBUTES->value === $filterData['report_type']) {
            $stockMovementAttributeServices = resolve(StockMovementAttributeServices::class);

            return $stockMovementAttributeServices->exportStockMovementDetailsByAttribute(
                $filterData,
                $companyId,
                $filename,
                $getFilterLabels
            );
        }

        $stockMovementBySizeServices = resolve(StockMovementBySizeServices::class);

        return $stockMovementBySizeServices->exportStockMovementDetailsBySize(
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
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'search_text' => $request->get('search_text'),
            'attribute_type' => (int) $request->get('attribute_type'),
            'attributes' => $request->get('attributes'),
        ];
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
