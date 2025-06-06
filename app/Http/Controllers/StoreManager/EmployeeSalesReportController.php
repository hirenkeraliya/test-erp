<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SaleItem\Exports\EmployeeSalesExport;
use App\Domains\SaleItem\Resources\EmployeeSalesReportListResource;
use App\Domains\SaleItem\SaleItemQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeSalesReportController extends Controller
{
    public function __construct(
        protected SaleItemQueries $saleItemQueries
    ) {
    }

    public function index(): Response
    {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections(
            session('store_manager_selected_location_company_id')
        );

        return Inertia::render('reports/employee_sales_report/Index', [
            'productCollections' => $productCollections,
            'exportPermission' => PermissionList::getExportPermissionName('employee_sale'),
            'helpCenterMessages' => 'Only includes regular, complete credit, complete layaway and non-exchange sales. It contains employee and product details, along with the units sold and returned. Advanced filters, search options, and seamless export capabilities are provided for in-depth analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchEmployeeSales(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'employee_id' => $request->get('employee_id'),
            'product_id' => $request->get('product_id'),
            'date_range' => $request->get('date_range'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];

        $lengthAwarePaginator = $this->saleItemQueries->getPaginatedEmployeeSalesReportListForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => EmployeeSalesReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportEmployeeSales(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'employee_id' => $request->get('employee_id'),
            'product_id' => $request->get('product_id'),
            'date_range' => $request->get('date_range'),
            'product_collection_id' => $request->get('product_collection_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $employeeSales = $this->saleItemQueries->getPaginatedEmployeeSalesListForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new EmployeeSalesExport($employeeSales, $filteredColumns), $filename);
    }
}
