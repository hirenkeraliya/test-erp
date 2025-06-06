<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Exports\CreditSaleExport;
use App\Domains\Sale\Resources\CreditSaleItemsReportResource;
use App\Domains\Sale\Resources\CreditSaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintCreditSaleReportService;
use App\Domains\Sale\Services\PrintCreditSaleTaxInvoiceService;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditSaleController extends Controller
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
        $locationId = (int) $request->input('location_id');
        $selectedLocations = null;

        $offlineSaleId = $request->get('offline_sale_id');

        if (0 !== $locationId) {
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);

            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales/credit_sales/Index', [
            'locations' => $locations,
            'offlineSaleId' => $offlineSaleId,
            'statuses' => CreditAndLayawaySaleStatuses::getList(),
            'creditSaleStatusPending' => $request->get('status_id') ? (int) $request->get(
                'status_id'
            ) : CreditAndLayawaySaleStatuses::PENDING->value,
            'exportPermission' => PermissionList::getExportPermissionName('credit_sale'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'dashboardFilterData' => [
                'location_ids' => $locationId > 0 ? [$locationId] : null,
                'selectedLocations' => $selectedLocations,
            ],
            'moduleType' => ModelMapping::SALE->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'Pending or Complete credit sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchPendingCreditSales(Request $request): array
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
            'e_invoice_submitted' => (int) $request->get('e_invoice_submitted'),
        ];

        $lengthAwarePaginator = $this->saleQueries->getPaginatedPendingCreditSalesWithRelations(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => CreditSaleReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCreditSales(string $filename, Request $request): BinaryFileResponse
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
            'e_invoice_submitted' => (int) $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $creditSale = $this->saleQueries->getPendingCreditSalesWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new CreditSaleExport($creditSale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, CreditSaleItemsReportResource>
     */
    public function fetchCreditSaleItemsBySaleId(int $saleId): array
    {
        $creditSaleDetails = $this->saleQueries->getCreditSaleItemsBy($saleId, session('admin_company_id'));

        return [
            'credit_sale_details' => new CreditSaleItemsReportResource($creditSaleDetails),
        ];
    }

    public function printCreditSale(int $saleId): string
    {
        $printCreditSaleReportService = resolve(PrintCreditSaleReportService::class);

        return $printCreditSaleReportService->print($saleId, session('admin_company_id'), null);
    }

    public function printCreditSaleTaxInvoice(int $saleId): string
    {
        $printCreditSaleTaxInvoiceService = resolve(PrintCreditSaleTaxInvoiceService::class);

        return $printCreditSaleTaxInvoiceService->print($saleId, session('admin_company_id'), null);
    }

    public function printDigitalInvoice(int $saleId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($saleId, ModelMapping::SALE->name);
    }
}
