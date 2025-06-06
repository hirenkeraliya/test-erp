<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SaleItem\Exports\MemberSalesExport;
use App\Domains\SaleItem\Resources\AdminMemberSalesReportListResource;
use App\Domains\SaleItem\Resources\MemberReportSaleDetailsResource;
use App\Domains\SaleItem\SaleItemQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberSalesReportController extends Controller
{
    public function __construct(
        protected SaleItemQueries $saleItemQueries
    ) {
    }

    public function index(): Response
    {
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections(session('admin_company_id'));

        return Inertia::render('reports/member_sales_report/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('member_sale'),
            'locations' => $locations,
            'productCollections' => $productCollections,
            'helpCenterMessages' => 'Only regular, complete credit, complete layaway and non-exchange sales are considered for the member sales report with the product type, color, size, number of the sold unit and return unit and offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchMemberSales(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'member_id' => $request->get('member_id'),
            'product_id' => $request->get('product_id'),
            'date_range' => $request->get('date_range'),
            'location_id' => $request->get('location_id'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];

        $lengthAwarePaginator = $this->saleItemQueries->getPaginatedMemberSalesReportList(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminMemberSalesReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportMemberSales(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'member_id' => $request->get('member_id'),
            'product_id' => $request->get('product_id'),
            'date_range' => $request->get('date_range'),
            'location_id' => $request->get('location_id'),
            'product_collection_id' => $request->get('product_collection_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $memberSales = $this->saleItemQueries->getPaginatedMemberSalesListForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new MemberSalesExport($memberSales, $filteredColumns), $filename);
    }

    public function fetchSaleDetailsBySaleItemId(int $saleItemId): array
    {
        $saleDetails = $this->saleItemQueries->getSaleDetailsById($saleItemId);

        return [
            'sale_details' => new MemberReportSaleDetailsResource($saleDetails),
        ];
    }
}
