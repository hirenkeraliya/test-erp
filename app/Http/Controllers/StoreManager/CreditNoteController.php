<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNote\Exports\CreditNoteExport;
use App\Domains\CreditNote\Resources\StoreManagerCreditNoteReportResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use App\Models\Employee;
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
        $companyId = session('store_manager_selected_location_company_id');
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        $cashiers = $cashierQueries->getAllCashiersByCompany($companyId);
        $counters = $counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            $companyId
        );

        $creditNoteId = (int) $request->get('credit_note_id');

        $cashiers->transform(function ($cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'id' => $cashier->id,
                'name' => $employee->getFullName(),
            ];
        });

        return Inertia::render('sales_management/CreditNotes', [
            'statuses' => [
                'active' => CreditNoteStatuses::ACTIVE->value,
                'used' => CreditNoteStatuses::USED->value,
                'expired' => CreditNoteStatuses::EXPIRED->value,
                'refunded' => CreditNoteStatuses::REFUNDED->value,
            ],
            'creditNoteStatuses' => CreditNoteStatuses::getList(),
            'cashiers' => $cashiers,
            'counters' => $counters,
            'exportPermission' => PermissionList::getExportPermissionName('credit_note'),
            'helpCenterMessages' => 'The credit notes report, including Credit Note Use Details information, and provide advanced filters, search options, and seamless export capabilities for thorough analysis and insights.',
            'creditNoteId' => $creditNoteId,
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchCreditNotes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'status_id' => $request->get('status_id'),
            'credit_note_id' => $request->get('credit_note_id'),
        ];

        $lengthAwarePaginator = $this->creditNoteQueries->getPaginatedListByCompanyWithRelationsForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerCreditNoteReportResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCreditNotes(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'status_id' => $request->get('status_id'),
            'credit_note_id' => $request->get('credit_note_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $creditNotes = $this->creditNoteQueries->getCreditNoteListByCompanyWithRelationsForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );

        return Excel::download(new CreditNoteExport($creditNotes, $filteredColumns), $filename);
    }
}
