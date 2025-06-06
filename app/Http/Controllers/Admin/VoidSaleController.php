<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Exports\VoidSaleExport;
use App\Domains\Sale\Resources\VoidedSalesItemsReportResource;
use App\Domains\Sale\Resources\VoidedSalesReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoidSaleController extends Controller
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

        $voidSaleNumber = $request->get('void_sale_number');
        $voidSaleOfflineNumber = $request->get('offline_sale_id');

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales/void_sales/Index', [
            'locations' => $locations,
            'voidSaleNumber' => $voidSaleNumber ?? null,
            'voidSaleOfflineNumber' => $voidSaleOfflineNumber ?? null,
            'moduleType' => ModelMapping::SALE->name,
            'allowEInvoice' => $allowEInvoice,
            'exportPermission' => PermissionList::getExportPermissionName('void_sale'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'helpCenterMessages' => 'Void sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchVoidSales(Request $request): array
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
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
        ];

        $filterData['search_text'] = $request->get('void_sale_offline_number') ?? $filterData['search_text'];
        $filterData['void_sale_number'] = $request->get('void_sale_number') ?? null;
        if (null !== $request->get('void_sale_offline_number') || null !== $request->get('void_sale_number')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleQueries->getPaginatedVoidSalesWithRelations(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => VoidedSalesReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportVoidSale(string $filename, Request $request): BinaryFileResponse
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
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $filterData['search_text'] = $request->get('void_sale_offline_number') ?? null;
        $filterData['void_sale_number'] = $request->get('void_sale_number') ?? null;
        if (null !== $request->get('void_sale_offline_number') || null !== $request->get('void_sale_number')) {
            $filterData['date_range'] = null;
        }

        $voidSale = $this->saleQueries->getVoidSalesWithRelationForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new VoidSaleExport($voidSale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, VoidedSalesItemsReportResource>
     */
    public function fetchVoidSaleItemsBySaleId(int $saleId): array
    {
        $voidSaleDetails = $this->saleQueries->getVoidSaleItemsBy($saleId, session('admin_company_id'));

        return [
            'void_sale_details' => new VoidedSalesItemsReportResource($voidSaleDetails),
        ];
    }

    public function printDigitalInvoice(int $saleId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($saleId, ModelMapping::SALE->name);
    }
}
