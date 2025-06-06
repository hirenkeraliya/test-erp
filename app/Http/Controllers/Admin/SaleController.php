<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Exports\SaleExport;
use App\Domains\Sale\Resources\SaleItemsReportResource;
use App\Domains\Sale\Resources\SaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleController extends Controller
{
    public function __construct(
        protected SaleQueries $saleQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $offlineSaleId = $request->get('offline_sale_id');

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales/Index', [
            'locations' => $locations,
            'offlineSaleId' => $offlineSaleId ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('sale'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::SALE->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'Only regular, complete credit and complete layaway sales without exchanges report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchRegularSales(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
        ];

        if (null !== $request->get('offline_sale_id')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleQueries->getPaginatedRegularAndCompleteSalesWithRelations(
            $filterData,
            session('admin_company_id')
        );

        $consolidatedSales = $this->saleQueries->getFilteredTotalsForReport(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_sold' => $consolidatedSales->total_units_sold,
            /* @phpstan-ignore-next-line */
            'total_sales' => $consolidatedSales->total_sales,
            /* @phpstan-ignore-next-line */
            'total_sales_amount' => $consolidatedSales->total_sales_amount,
        ];
    }

    public function exportSales(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        if (null !== $request->get('offline_sale_id')) {
            $filterData['date_range'] = null;
        }

        $sales = $this->saleQueries->getRegularAndLayawaySalesWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new SaleExport($sales, $filteredColumns), $filename);
    }

    /**
     * @return array<string, SaleItemsReportResource>
     */
    public function fetchSaleItemsBySaleId(int $saleId): array
    {
        $saleDetails = $this->saleQueries->getSaleItemsBy($saleId, session('admin_company_id'));

        return [
            'sale_details' => new SaleItemsReportResource($saleDetails),
        ];
    }

    public function printDigitalInvoice(int $saleId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($saleId, ModelMapping::SALE->name);
    }
}
