<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Domains\SaleReturn\Exports\DifferentStoreReturnsExport;
use App\Domains\SaleReturn\Resources\DifferentStoreReturnsReportListResource;
use App\Domains\SaleReturn\Resources\SaleReturnItemsReportResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DifferentStoreReturnsController extends Controller
{
    public function __construct(
        protected SaleReturnQueries $saleReturnQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales/sale_returns/DifferentStoreReturns', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('different_store_return'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::SALE_RETURN->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'Different location sale returns data offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchDifferentStoreReturns(Request $request): array
    {
        $filterData = $this->getFilters($request);

        $lengthAwarePaginator = $this->saleReturnQueries->getPaginatedDifferentStoreReturnsWithRelation(
            $filterData,
            session('admin_company_id')
        );

        $consolidatedSaleReturns = $this->saleReturnQueries->getFilteredTotalsDifferentStoreForReport(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DifferentStoreReturnsReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_returned' => $consolidatedSaleReturns->total_units_returned,
            /* @phpstan-ignore-next-line */
            'total_return_sales' => $consolidatedSaleReturns->total_return_sales,
            /* @phpstan-ignore-next-line */
            'total_return_amount' => $consolidatedSaleReturns->total_return_amount,
        ];
    }

    public function exportDifferentStoreReturns(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilters($request);
        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $saleReturns = $this->saleReturnQueries->getDifferentStoreReturnWithRelationForExport(
            $filterData,
            session('admin_company_id')
        );

        /** @var array $saleReturnsArray */
        $saleReturnsArray = DifferentStoreReturnsReportListResource::collection($saleReturns)->toArray($request);

        return Excel::download(
            new DifferentStoreReturnsExport(collect($saleReturnsArray), $filteredColumns),
            $filename
        );
    }

    /**
     * @return array<string, SaleReturnItemsReportResource>
     */
    public function fetchSaleReturnItemsForDifferentStore(int $saleReturnId): array
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

    private function getFilters(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'original_sale_location_ids' => (array) $request->get('original_sale_location_ids'),
            'original_sale_counter_ids' => (array) $request->get('original_sale_counter_ids'),
            'original_sale_cashier_id' => $request->get('original_sale_cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
        ];
    }
}
