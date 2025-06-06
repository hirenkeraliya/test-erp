<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SaleAnalysis\Exports\SaleAnalysisExport;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleAnalysisByGradeReportController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $saleThroughRatioQueries = resolve(SaleThroughRatioQueries::class);
        $saleThroughRatios = $saleThroughRatioQueries->getCachedPercentageAndName(
            $companyId,
            $orderByPercentage = false
        );
        $locationId = (int) $request->get('location_id');
        $productId = (int) $request->get('product_id');

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getByIdForDashboardFilter($productId, $companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        return Inertia::render('reports/sale_analysis_reports/Index', [
            'productCollections' => $productCollections,
            'locations' => $locations,
            'saleThroughRatios' => $saleThroughRatios,
            'exportPermission' => PermissionList::getExportPermissionName('sale_analysis'),
            'dashboardFilterData' => [
                'location_id' => $locationId > 0 ? $locationId : null,
                'product_id' => $productId > 0 ? $productId : null,
                'selectedProduct' => $product ? [
                    'id' => $product->id,
                    'name' => $product->name,
                ] : [],
            ],
            'attributes' => $attributes ?? collect([]),
            'helpCenterMessages' => 'The Sale Analysis Grade Report offers a comprehensive analysis of sales performance based on grading criteria. It provides valuable insights into sales performance across different grades or categories, aiding in strategic decision-making and sales optimization efforts. Only regular, pending/complete credit, and pending/complete layaway sales is considered. Advanced filters, search options, and seamless export capabilities are provided for detailed analysis and insights.',
        ]);
    }

    public function fetchSaleAnalysisByGradeReport(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'page' => $request->get('page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date' => $request->get('date'),
            'article_numbers' => (array) $request->get('article_numbers'),
            'category_ids' => (array) $request->get('category_ids'),
            'brand_ids' => (array) $request->get('brand_ids'),
            'color_ids' => (array) $request->get('color_ids'),
            'size_ids' => (array) $request->get('size_ids'),
            'style_ids' => (array) $request->get('style_ids'),
            'tag_ids' => (array) $request->get('tag_ids'),
            'department_ids' => (array) $request->get('department_ids'),
            'product_id' => $request->get('product_id'),
            'location_id' => $request->get('location_id'),
            'grade_filter' => $request->get('grade_filter'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => (array) $request->get('attributes'),
        ];

        $products = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $companyId);

        [$lengthAwarePaginator, $totalAmountData] = $this->getFilterPaginatedRecords(
            $products,
            $filterData,
            $companyId
        );

        return [
            'data' => $lengthAwarePaginator->getCollection(),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function fetchTotalSaleAnalysisByGradeReport(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'page' => $request->get('page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date' => $request->get('date'),
            'article_numbers' => (array) $request->get('article_numbers'),
            'category_ids' => (array) $request->get('category_ids'),
            'brand_ids' => (array) $request->get('brand_ids'),
            'color_ids' => (array) $request->get('color_ids'),
            'size_ids' => (array) $request->get('size_ids'),
            'style_ids' => (array) $request->get('style_ids'),
            'tag_ids' => (array) $request->get('tag_ids'),
            'department_ids' => (array) $request->get('department_ids'),
            'product_id' => $request->get('product_id'),
            'location_id' => $request->get('location_id'),
            'grade_filter' => $request->get('grade_filter'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];

        $products = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $companyId);

        [$totalAmountData, $saleThroughRatios] = $this->getFilterRecords($products, $filterData, $companyId);

        return [
            'totals' => $totalAmountData,
            'saleThroughRatios' => $saleThroughRatios,
        ];
    }

    public function printSaleAnalysisByGradeReport(Request $request): string
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'page' => $request->get('page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date' => $request->get('date'),
            'article_numbers' => (array) $request->get('article_numbers'),
            'category_ids' => (array) $request->get('category_ids'),
            'brand_ids' => (array) $request->get('brand_ids'),
            'color_ids' => (array) $request->get('color_ids'),
            'size_ids' => (array) $request->get('size_ids'),
            'style_ids' => (array) $request->get('style_ids'),
            'tag_ids' => (array) $request->get('tag_ids'),
            'department_ids' => (array) $request->get('department_ids'),
            'product_id' => $request->get('product_id'),
            'grade_filter' => $request->get('grade_filter'),
            'location_id' => $request->get('location_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => (array) $request->get('attributes'),
        ];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $location = null;

        $locationQueries = resolve(LocationQueries::class);
        if ($filterData['location_id']) {
            $location = $locationQueries->getByIdWithNameAndCode($companyId, (int) $filterData['location_id']);
        }

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        $products = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $companyId);

        $productRecords = $this->getFilterRecordsForPrint($products, $filterData, $companyId);

        $variantColumns = config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'];

        $columns = ['Name', 'Upc', 'Article Number', ...$variantColumns, 'Product Grade', 'Units Sold', 'Sales'];

        $variantFields = config('app.product_variant')
            ? [
                'attributes' => '',
            ]
            : [
                'color' => '',
                'size' => '',
            ];

        $saleAnalysisTotals = [
            'name' => 'Total',
            'upc' => '',
            'article_number' => '',
            ...$variantFields,
            'sale_analysis_grade' => '',
            'total_units_sold' => $productRecords->sum('total_units_sold'),
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                $productRecords->sum('total_sales')
            ),
        ];

        return view('prints.sale_analysis', [
            'saleAnalysis' => $productRecords->toArray(),
            'saleAnalysisTotals' => $saleAnalysisTotals,
            'dateRange' => $filterData['date'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'location' => $location,
            'company' => $company,
            'columns' => $columns,
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function exportSaleAnalysisByGradeReport(Request $request, string $filename): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'page' => $request->get('page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date' => $request->get('date'),
            'article_numbers' => (array) $request->get('article_numbers'),
            'category_ids' => (array) $request->get('category_ids'),
            'brand_ids' => (array) $request->get('brand_ids'),
            'color_ids' => (array) $request->get('color_ids'),
            'size_ids' => (array) $request->get('size_ids'),
            'style_ids' => (array) $request->get('style_ids'),
            'tag_ids' => (array) $request->get('tag_ids'),
            'department_ids' => (array) $request->get('department_ids'),
            'product_id' => $request->get('product_id'),
            'grade_filter' => $request->get('grade_filter'),
            'location_id' => $request->get('location_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => (array) $request->get('attributes'),
        ];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $location = null;

        if ($filterData['location_id']) {
            $locationQueries = resolve(LocationQueries::class);
            $location = $locationQueries->getByIdWithNameAndCode($companyId, (int) $filterData['location_id']);
        }

        $products = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $companyId);

        $productRecords = $this->getFilterRecordsForPrint($products, $filterData, $companyId);

        $variantColumns = config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'];
        $columns = ['Name', 'Upc', 'Article Number', ...$variantColumns, 'Product Grade', 'Units Sold', 'Sales'];

        $variantFields = config('app.product_variant')
            ? [
                'attributes' => '',
            ]
            : [
                'color' => '',
                'size' => '',
            ];

        $saleAnalysisTotals = [
            'name' => 'Total',
            'upc' => '',
            'article_number' => '',
            ...$variantFields,
            'sale_analysis_grade' => '',
            'total_units_sold' => $productRecords->sum('total_units_sold'),
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                $productRecords->sum('total_sales')
            ),
        ];

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return Excel::download(
            new SaleAnalysisExport(
                $productRecords->toArray(),
                $saleAnalysisTotals,
                $company,
                $location,
                $filterData['date'],
                $columns,
                $filterHeaderData
            ),
            $filename
        );
    }

    private function getFilterPaginatedRecords(Collection $products, array $filterData, int $companyId): array
    {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $saleThroughRatioQueries = resolve(SaleThroughRatioQueries::class);
        $saleThroughRatios = $saleThroughRatioQueries->getCachedPercentageAndName($companyId);

        $productRecords = $products->map(function ($product) use ($saleThroughRatios) {
            $product->sale_analysis_grade = '';
            $product->sell_through = CommonFunctions::truncateDecimal((float) $product->sell_through);
            foreach ($saleThroughRatios as $saleThroughRatio) {
                if ($product->sell_through <= $saleThroughRatio->percentage) {
                    $product->sale_analysis_grade_id = $saleThroughRatio->id;
                    $product->sale_analysis_grade = $saleThroughRatio->name;
                    break;
                }
            }

            $count = $saleThroughRatios->isNotEmpty() ? count($saleThroughRatios) : 0;
            if ($count > 0 && $product->sell_through > $saleThroughRatios[$count - 1]->percentage) {
                $product->sale_analysis_grade_id = $saleThroughRatios[$count - 1]->id;
                $product->sale_analysis_grade = $saleThroughRatios[$count - 1]->name;
            }

            return $product->toArray();
        });

        if ('sale_analysis_grade' === $filterData['sort_by']) {
            if ('desc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortByDesc('sale_analysis_grade');
            }

            if ('asc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortBy('sale_analysis_grade');
            }
        }

        $totalAmountData = [];

        foreach ($saleThroughRatios->sort() as $saleThroughRatio) {
            if (array_key_exists($saleThroughRatio->name, $totalAmountData)) {
                continue;
            }

            $totalAmountData[$saleThroughRatio->name] = [
                $productRecords->where('sale_analysis_grade_id', $saleThroughRatio->id)->sum('total_sales'),
            ];
        }

        if (array_key_exists('grade_filter', $filterData) && null !== $filterData['grade_filter']) {
            $lengthAwarePaginator = new LengthAwarePaginator(
                $productRecords->whereIn('sale_analysis_grade_id', $filterData['grade_filter'])->forPage(
                    $filterData['page'],
                    $filterData['per_page']
                ),
                $productRecords->count(),
                $filterData['per_page'],
                $filterData['page']
            );

            $result = [$lengthAwarePaginator, $totalAmountData];

            Cache::put($cacheKey, $result, $cacheExpireTime);

            return $result;
        }

        $lengthAwarePaginator = new LengthAwarePaginator(
            $productRecords->forPage($filterData['page'], $filterData['per_page']),
            $productRecords->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        $result = [$lengthAwarePaginator, $totalAmountData];

        Cache::put($cacheKey, $result, $cacheExpireTime);

        return $result;
    }

    private function getFilterRecords(Collection $products, array $filterData, int $companyId): array
    {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $saleThroughRatioQueries = resolve(SaleThroughRatioQueries::class);
        $saleThroughRatios = $saleThroughRatioQueries->getCachedPercentageAndName($companyId);

        $productRecords = $products->map(function ($product) use ($saleThroughRatios) {
            $product->sale_analysis_grade = '';
            $product->sell_through = CommonFunctions::truncateDecimal((float) $product->sell_through);
            foreach ($saleThroughRatios as $saleThroughRatio) {
                if ($product->sell_through <= $saleThroughRatio->percentage) {
                    $product->sale_analysis_grade_id = $saleThroughRatio->id;
                    $product->sale_analysis_grade = $saleThroughRatio->name;
                    break;
                }
            }

            $count = $saleThroughRatios->isNotEmpty() ? count($saleThroughRatios) : 0;
            if ($count > 0 && $product->sell_through > $saleThroughRatios[$count - 1]->percentage) {
                $product->sale_analysis_grade_id = $saleThroughRatios[$count - 1]->id;
                $product->sale_analysis_grade = $saleThroughRatios[$count - 1]->name;
            }

            return $product->toArray();
        });

        if ('sale_analysis_grade' === $filterData['sort_by']) {
            if ('desc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortByDesc('sale_analysis_grade');
            }

            if ('asc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortBy('sale_analysis_grade');
            }
        }

        $totalAmountData = [];

        if (array_key_exists('grade_filter', $filterData) && null !== $filterData['grade_filter']) {
            foreach ($saleThroughRatios->sort() as $saleThroughRatio) {
                if (array_key_exists($saleThroughRatio->name, $totalAmountData)) {
                    continue;
                }

                if ((int) $filterData['grade_filter'] === $saleThroughRatio->id) {
                    $totalAmountData[] = [
                        'name' => $saleThroughRatio->name,
                        'amount' => $productRecords->where('sale_analysis_grade_id', $saleThroughRatio->id)->sum(
                            'total_sales'
                        ),
                        'percentage' => $saleThroughRatio->percentage,
                    ];

                    continue;
                }

                $totalAmountData[] = [
                    'name' => $saleThroughRatio->name,
                    'amount' => 0,
                    'percentage' => $saleThroughRatio->percentage,
                ];
            }

            $result = [$totalAmountData, $saleThroughRatios->sortByDesc('percentage')->values()->toArray()];

            Cache::put($cacheKey, $result, $cacheExpireTime);

            return $result;
        }

        foreach ($saleThroughRatios->sort() as $saleThroughRatio) {
            if (array_key_exists($saleThroughRatio->name, $totalAmountData)) {
                continue;
            }

            $totalAmountData[] = [
                'name' => $saleThroughRatio->name,
                'amount' => $productRecords->where('sale_analysis_grade_id', $saleThroughRatio->id)->sum('total_sales'),
                'percentage' => $saleThroughRatio->percentage,
            ];
        }

        $result = [$totalAmountData, $saleThroughRatios->sortByDesc('percentage')->values()->toArray()];

        Cache::put($cacheKey, $result, $cacheExpireTime);

        return $result;
    }

    private function getFilterRecordsForPrint(Collection $products, array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = $this->generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $saleThroughRatioQueries = resolve(SaleThroughRatioQueries::class);
        $saleThroughRatios = $saleThroughRatioQueries->getCachedPercentageAndName($companyId);

        $productRecords = $products->map(function ($product) use ($saleThroughRatios) {
            $product->sale_analysis_grade = '';
            $product->sell_through = CommonFunctions::truncateDecimal((float) $product->sell_through);
            foreach ($saleThroughRatios as $saleThroughRatio) {
                if ($product->sell_through <= $saleThroughRatio->percentage) {
                    $product->sale_analysis_grade_id = $saleThroughRatio->id;
                    $product->sale_analysis_grade = $saleThroughRatio->name;
                    break;
                }
            }

            $count = $saleThroughRatios->isNotEmpty() ? count($saleThroughRatios) : 0;
            if ($count > 0 && $product->sell_through > $saleThroughRatios[$count - 1]->percentage) {
                $product->sale_analysis_grade_id = $saleThroughRatios[$count - 1]->id;
                $product->sale_analysis_grade = $saleThroughRatios[$count - 1]->name;
            }

            return $product->toArray();
        });

        if ('sale_analysis_grade' === $filterData['sort_by']) {
            if ('desc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortByDesc('sale_analysis_grade');
            }

            if ('asc' === $filterData['sort_direction']) {
                $productRecords = $productRecords->sortBy('sale_analysis_grade');
            }
        }

        if (array_key_exists('grade_filter', $filterData) && null !== $filterData['grade_filter']) {
            $result = $productRecords->whereIn('sale_analysis_grade_id', $filterData['grade_filter']);

            Cache::put($cacheKey, $result, $cacheExpireTime);

            return $result;
        }

        $result = $productRecords;

        Cache::put($cacheKey, $result, $cacheExpireTime);

        return $result;
    }

    private function generateFilteredCacheKeyWithExpiration(
        array $filterData,
        string $functionName,
        int $companyId
    ): array {
        $kebabFunctionName = Str::kebab($functionName);

        $string = '';
        foreach ($filterData as $value) {
            if (is_array($value)) {
                $string .= implode('', $value);
            } elseif ('' !== $value) {
                $string .= $value;
            }
        }

        return [$kebabFunctionName.'-'.$string.'-'.$companyId, now()->addMinutes(20)];
    }
}
