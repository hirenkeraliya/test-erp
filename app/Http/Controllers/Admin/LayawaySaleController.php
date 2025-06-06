<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Exports\LayawaySaleExport;
use App\Domains\Sale\Resources\LayawaySaleItemsReportResource;
use App\Domains\Sale\Resources\LayawaySaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Domains\Sale\Services\PrintLayawaySaleReportService;
use App\Domains\Sale\Services\PrintLayawaySaleTaxInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LayawaySaleController extends Controller
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

        return Inertia::render('sales/layaway_sales/Index', [
            'locations' => $locations,
            'offlineSaleId' => $offlineSaleId,
            'statuses' => CreditAndLayawaySaleStatuses::getList(),
            'layawaySaleStatusPending' => $request->get('status_id') ? (int) $request->get(
                'status_id'
            ) : CreditAndLayawaySaleStatuses::PENDING->value,
            'moduleType' => ModelMapping::SALE->name,
            'allowEInvoice' => $allowEInvoice,
            'exportPermission' => PermissionList::getExportPermissionName('layaway_sale'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'helpCenterMessages' => 'Pending or Complete layaway sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchPendingLayawaySales(Request $request): array
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
            'status_id' => (int) $request->get('status_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted') ?? null,
        ];

        $lengthAwarePaginator = $this->saleQueries->getPaginatedPendingLayawaySalesWithRelations(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => LayawaySaleReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportLayawaySales(string $filename, Request $request): BinaryFileResponse
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
            'status_id' => (int) $request->get('status_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted') ?? null,
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $layawaySale = $this->saleQueries->getPendingLayawaySalesWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new LayawaySaleExport($layawaySale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, LayawaySaleItemsReportResource>
     */
    public function fetchLayawaySaleItemsBySaleId(int $saleId): array
    {
        $layawaySaleDetails = $this->saleQueries->getLayawaySaleItemsBy($saleId, session('admin_company_id'));

        return [
            'layaway_sale_details' => new LayawaySaleItemsReportResource($layawaySaleDetails),
        ];
    }

    public function printLayawaySale(int $saleId): string
    {
        $printLayawaySaleReportService = resolve(PrintLayawaySaleReportService::class);

        return $printLayawaySaleReportService->print($saleId, session('admin_company_id'), null);
    }

    public function printSaleTaxInvoice(int $saleId): string
    {
        $printSaleTaxInvoiceService = resolve(PrintLayawaySaleTaxInvoiceService::class);

        return $printSaleTaxInvoiceService->print($saleId, session('admin_company_id'), null);
    }

    public function printDigitalInvoice(int $saleId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($saleId, ModelMapping::SALE->name);
    }
}
