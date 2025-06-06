<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Domains\SaleReturn\Exports\SaleReturnExport;
use App\Domains\SaleReturn\Resources\SaleReturnItemsReportResource;
use App\Domains\SaleReturn\Resources\SaleReturnReportListResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnQueries $saleReturnQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $offlineSaleReturnId = $request->get('offline_sale_return_id');

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales/sale_returns/Index', [
            'locations' => $locations,
            'offlineSaleReturnId' => $offlineSaleReturnId ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('sale_return'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::SALE_RETURN->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'Generate detailed sale return reports by utilizing our comprehensive filter options, enabling precise data searches, and export your filter records effortlessly for further analysis.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchSaleReturns(Request $request): array
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
            'offline_sale_return_id' => $request->get('offline_sale_return_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
        ];

        if (null !== $request->get('offline_sale_return_id')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleReturnQueries->getPaginatedSaleReturnsWithRelations(
            $filterData,
            session('admin_company_id')
        );

        $consolidatedSaleReturns = $this->saleReturnQueries->getFilteredTotalsForReport(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleReturnReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_returned' => $consolidatedSaleReturns->total_units_returned,
            /* @phpstan-ignore-next-line */
            'total_return_sales' => $consolidatedSaleReturns->total_return_sales,
            /* @phpstan-ignore-next-line */
            'total_return_amount' => $consolidatedSaleReturns->total_return_amount,
        ];
    }

    public function exportSaleReturns(string $filename, Request $request): BinaryFileResponse
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
            'offline_sale_return_id' => $request->get('offline_sale_return_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        if (null !== $request->get('offline_sale_return_id')) {
            $filterData['date_range'] = null;
        }

        $saleReturns = $this->saleReturnQueries->getSaleReturnsWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new SaleReturnExport($saleReturns, $filteredColumns), $filename);
    }

    /**
     * @return array<string, SaleReturnItemsReportResource>
     */
    public function fetchSaleReturnItems(int $saleReturnId): array
    {
        $saleReturnDetails = $this->saleReturnQueries->getSaleReturnItemsBy(
            $saleReturnId,
            session('admin_company_id')
        );

        return [
            'sale_return_details' => new SaleReturnItemsReportResource($saleReturnDetails),
        ];
    }

    public function printDigitalInvoice(int $saleReturnId): string
    {
        $saleReturnPrintDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $saleReturnPrintDigitalInvoiceService->print($saleReturnId, ModelMapping::SALE_RETURN->name);
    }
}
