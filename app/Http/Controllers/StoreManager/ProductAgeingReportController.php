<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\CommonFunctions;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductAgeingReport\Enums\AgeCategories;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductAgeingReport\Enums\ProductAgeingReportTypes;
use App\Domains\ProductAgeingReport\Exports\ProductAgeingReportByArticleNumberExport;
use App\Domains\ProductAgeingReport\Exports\ProductAgeingReportByMonthAndYearExport;
use App\Domains\ProductAgeingReport\Exports\ProductAgeingReportByUpcExport;
use App\Domains\ProductAgeingReport\Exports\ProductAgeingReportExport;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\ProductAgeingReport\Resources\ProductsAgeingBasedOnArticleNumberReportListResource;
use App\Domains\ProductAgeingReport\Resources\ProductsAgeingBasedOnUpcReportListResource;
use App\Domains\ProductAgeingReport\Resources\StoreManagerProductsAgeingByMonthAndYearReportListResource;
use App\Domains\ProductAgeingReport\Resources\StoreManagerProductsAgeingReportListResource;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ProductAgeing;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductAgeingReportController extends Controller
{
    public function __construct(
        protected ProductAgeingQueries $productAgeingQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        return Inertia::render('reports/product_ageing_report/Index', [
            'productCollections' => $productCollections,
            'ageCategories' => AgeCategories::getList(),
            'ageOfProductTypes' => AgeOfProductTypes::getList(),
            'staticAgeOfProductTypes' => AgeOfProductTypes::getFormattedArrayForStaticUse(),
            'productAgeingReportTypes' => ProductAgeingReportTypes::getList(),
            'staticProductAgeingReportTypes' => ProductAgeingReportTypes::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('product_ageing'),
            'helpCenterMessages' => 'The Product Ageing Report provides insights into product ageing based on specific conditions like is selling item, regular sales, pending/complete layaway sales, and pending/complete credit sales. It offers a detailed view of product movement and status within your inventory, helping you make informed decisions about inventory management and sales strategies.For the Basic Product Report (Aging Report) and Report by Month and Year, We display product counts as badges for time periods like one month, two months, six months, and more.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchProductsAgeingReport(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $lengthAwarePaginator = $this->productAgeingQueries->getPaginatedProductsAgeingReport(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerProductsAgeingReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function exportProductsAgeingReport(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $products = $this->productAgeingQueries->getProductsAgeingReportForExport(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );

        return Excel::download(new ProductAgeingReportExport($products, $filteredColumns), $filename);
    }

    public function checkProductAgeingExportLimit(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->exportProductAgeingWithJob(
            $storeManager,
            $filterData,
            $companyId,
            $filteredColumns
        );
    }

    public function checkProductAgeingByMonthAndYearExportLimit(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $companyId = session('store_manager_selected_location_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        /** @var Admin $admin */
        $admin = $request->user();

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->exportProductAgeingByMonthAndYearWithJob(
            $admin,
            $filterData,
            $companyId,
            $filteredColumns
        );
    }

    public function printProductsAgeing(Request $request): string
    {
        $filterData = $this->getFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->print($filterData, $companyId, $filteredColumns);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchProductsAgeingReportByMonthAndYear(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $lengthAwarePaginator = $this->productAgeingQueries->getPaginatedProductsAgeingReportByMonthAndYear(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerProductsAgeingByMonthAndYearReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function exportProductsAgeingReportByMonthAndYear(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $products = $this->productAgeingQueries->getProductsAgeingReportByMonthAndYearForExport(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );

        return Excel::download(
            new ProductAgeingReportByMonthAndYearExport(
                $products,
                (int) $filterData['age_of_product_type'],
                $filteredColumns
            ),
            $filename
        );
    }

    public function printProductsAgeingByMonthAndYear(Request $request): string
    {
        $filterData = $this->getFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->printByMonthAndYear($filterData, $companyId, $filteredColumns);
    }

    public function getFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'size_ids' => $request->get('size_ids'),
            'color_ids' => $request->get('color_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'age_of_product_type' => $request->get('age_of_product_type') ?? AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => $request->get('age_category_id'),
            'last_selling_date_range' => (array) $request->get('last_selling_date_range'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];
    }

    public function fetchConsolidateProductsAgeingReportByMonthAndYear(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $companyId = session('store_manager_selected_location_company_id');

        /** @var ProductAgeing $consolidateDataForTotals */
        $consolidateDataForTotals = $this->productAgeingQueries->getConsolidateProductsAgeingReportByMonthAndYear(
            $filterData,
            $companyId,
        );

        $totalQuantitySold = (float) $consolidateDataForTotals->first_month_sold + (float) $consolidateDataForTotals->second_month_sold + (float) $consolidateDataForTotals->third_month_sold + (float) $consolidateDataForTotals->fourth_month_sold + (float) $consolidateDataForTotals->fifth_month_sold + (float) $consolidateDataForTotals->sixth_month_sold + (float) $consolidateDataForTotals->seventh_month_sold + (float) $consolidateDataForTotals->eighth_month_sold + (float) $consolidateDataForTotals->ninth_month_sold + (float) $consolidateDataForTotals->tenth_month_sold + (float) $consolidateDataForTotals->eleventh_month_sold + (float) $consolidateDataForTotals->twelfth_month_sold;

        $totalRemainingStock = $consolidateDataForTotals->quantity_remaining;

        $productAgeingGroup = [];

        foreach (AgeCategories::getList() as $ageCategory) {
            [$startDay, $endDay] = AgeCategories::getDays($ageCategory['id']);

            if (array_key_exists($ageCategory['name'], $productAgeingGroup)) {
                continue;
            }

            $column = 'age_category_' . $startDay . '_' . $endDay;

            $productAgeingGroup[$ageCategory['name']] = $consolidateDataForTotals[$column];
        }

        return [
            'age_categories' => $productAgeingGroup,
            'total_quantity_sold' => CommonFunctions::numberFormat($totalQuantitySold),
            'total_quantity_remaining' => CommonFunctions::numberFormat((float) $totalRemainingStock),
        ];
    }

    public function fetchConsolidateProductsAgeingReport(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $companyId = session('store_manager_selected_location_company_id');

        /** @var ProductAgeing $consolidateDataForTotals */
        $consolidateDataForTotals = $this->productAgeingQueries->getProductsAgeingReportForConsolidate(
            $filterData,
            $companyId
        );

        $totalQuantitySold = (float) $consolidateDataForTotals->quantity_sold;
        $totalRemainingStock = (float) $consolidateDataForTotals->quantity_remaining;

        $productAgeingGroup = [];

        foreach (AgeCategories::getList() as $ageCategory) {
            [$startDay, $endDay] = AgeCategories::getDays($ageCategory['id']);

            if (array_key_exists($ageCategory['name'], $productAgeingGroup)) {
                continue;
            }

            $column = 'age_category_' . $startDay . '_' . $endDay;

            $productAgeingGroup[$ageCategory['name']] = $consolidateDataForTotals[$column];
        }

        return [
            'age_categories' => $productAgeingGroup,
            'total_quantity_sold' => $totalQuantitySold,
            'total_quantity_remaining' => $totalRemainingStock,
        ];
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchProductsAgeingReportByArticleNumber(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $companyId = session('store_manager_selected_location_company_id');

        $lengthAwarePaginator = $this->productAgeingQueries->getPaginatedProductsAgeingReportByArticleNumber(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ProductsAgeingBasedOnArticleNumberReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function fetchConsolidateProductsAgeingReportByArticleNumber(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $companyId = session('store_manager_selected_location_company_id');

        /** @var ?ProductAgeing $consolidateDataForTotals */
        $consolidateDataForTotals = $this->productAgeingQueries->getProductsAgeingReportForConsolidateByArticleNumber(
            $filterData,
            $companyId
        );

        $totalQuantitySold = null !== $consolidateDataForTotals ? (float) $consolidateDataForTotals->quantity_sold : 0;
        $totalRemainingStock = null !== $consolidateDataForTotals ? (float) $consolidateDataForTotals->quantity_remaining : 0;

        return [
            'total_quantity_sold' => $totalQuantitySold,
            'total_quantity_remaining' => $totalRemainingStock,
        ];
    }

    public function exportProductsAgeingReportByArticleNumber(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productAgeings = $this->productAgeingQueries->getProductsAgeingReportByArticleNumberForExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(
            new ProductAgeingReportByArticleNumberExport($productAgeings, $filteredColumns),
            $filename
        );
    }

    public function printProductsAgeingReportByArticleNumber(Request $request): string
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->printByArticleNumber($filterData, $companyId, $filteredColumns);
    }

    public function checkProductAgeingExportLimitByArticleNumber(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $companyId = session('store_manager_selected_location_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->exportProductAgeingByArticleNumberWithJob(
            $storeManager,
            $filterData,
            $companyId,
            $filteredColumns
        );
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchProductsAgeingReportByUpc(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $companyId = session('store_manager_selected_location_company_id');

        $lengthAwarePaginator = $this->productAgeingQueries->getPaginatedProductsAgeingReportByUpc(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ProductsAgeingBasedOnUpcReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function fetchConsolidateProductsAgeingReportByUpc(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $companyId = session('store_manager_selected_location_company_id');

        /** @var ?ProductAgeing $consolidateDataForTotals */
        $consolidateDataForTotals = $this->productAgeingQueries->getProductsAgeingReportForConsolidateByUpc(
            $filterData,
            $companyId
        );

        $totalQuantitySold = null !== $consolidateDataForTotals ? (float) $consolidateDataForTotals->quantity_sold : 0;
        $totalRemainingStock = null !== $consolidateDataForTotals ? (float) $consolidateDataForTotals->quantity_remaining : 0;

        return [
            'total_quantity_sold' => $totalQuantitySold,
            'total_quantity_remaining' => $totalRemainingStock,
        ];
    }

    public function exportProductsAgeingReportByUpc(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productAgeings = $this->productAgeingQueries->getProductsAgeingReportByUpcForExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new ProductAgeingReportByUpcExport($productAgeings, $filteredColumns), $filename);
    }

    public function printProductsAgeingReportByUpc(Request $request): string
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->printByUpc($filterData, $companyId, $filteredColumns);
    }

    public function checkProductAgeingExportLimitByUpc(Request $request): array
    {
        $filterData = $this->getFilterDataByArticleNumberOrUpc($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('store_manager_selected_location_company_id');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->exportProductAgeingByUpcWithJob(
            $storeManager,
            $filterData,
            $companyId,
            $filteredColumns
        );
    }

    private function getFilterDataByArticleNumberOrUpc(Request $request): array
    {
        return [
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
            'article_numbers' => $request->get('article_numbers'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'tag_ids' => $request->get('tag_ids'),
            'age_of_product_type' => $request->get('age_of_product_type') ?? AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => (array) $request->get('last_selling_date_range'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];
    }
}
