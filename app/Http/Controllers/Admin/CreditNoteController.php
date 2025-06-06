<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNote\Exports\CreditNoteExport;
use App\Domains\CreditNote\Resources\AdminCreditNoteReportResource;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditNoteController extends Controller
{
    public function __construct(
        protected CreditNoteQueries $creditNoteQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $creditNoteId = (int) $request->get('credit_note_id');

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('sales_management/CreditNotes', [
            'statuses' => [
                'active' => CreditNoteStatuses::ACTIVE->value,
                'used' => CreditNoteStatuses::USED->value,
                'expired' => CreditNoteStatuses::EXPIRED->value,
                'refunded' => CreditNoteStatuses::REFUNDED->value,
            ],
            'creditNoteStatuses' => CreditNoteStatuses::getList(),
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('credit_note'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::CREDIT_NOTE->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'The credit notes report, including Credit Note Use Details information, and provide advanced filters, search options, and seamless export capabilities for thorough analysis and insights.',
            'creditNoteId' => $creditNoteId,
        ]);
    }

    /**
     * @return array<string, int|float|AnonymousResourceCollection>
     */
    public function fetchCreditNotes(Request $request): array
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
            'status_id' => $request->get('status_id'),
            'employee_id' => $request->get('employee_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'credit_note_id' => $request->get('credit_note_id'),
        ];

        $lengthAwarePaginator = $this->creditNoteQueries->getPaginatedListByCompanyWithRelations(
            $filterData,
            session('admin_company_id')
        );

        $sumOfAvailableAmount = $this->creditNoteQueries->getSumOfAvailableAmountByCompany(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_available_amount' => $sumOfAvailableAmount,
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminCreditNoteReportResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCreditNotes(string $filename, Request $request): BinaryFileResponse
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
            'status_id' => $request->get('status_id'),
            'employee_id' => $request->get('employee_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'credit_note_id' => $request->get('credit_note_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $creditNotes = $this->creditNoteQueries->getCreditNoteListByCompanyWithRelationsForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new CreditNoteExport($creditNotes, $filteredColumns), $filename);
    }

    public function printDigitalInvoice(int $creditNoteId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($creditNoteId, ModelMapping::CREDIT_NOTE->name);
    }
}
