<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Counter\CounterQueries;
use App\Domains\PaymentType\Exports\PaymentTypeReportExport;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PaymentType\Resources\PaymentTransactionListResource;
use App\Domains\PaymentType\Resources\PaymentTypeReportListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentTypeReportController extends Controller
{
    public function __construct(
        protected PaymentTypeQueries $paymentTypeQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $counterQueries = resolve(CounterQueries::class);

        $counters = $counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return Inertia::render('reports/payment_type/Index', [
            'counters' => $counters,
            'paymentTypes' => $paymentTypeQueries->getAllPaymentTypesForReport($companyId),
            'exportPermission' => PermissionList::getExportPermissionName('payment_type_report'),
            'helpCenterMessages' => 'Only regular, pending/complete credit and pending/complete layaway sales with except by credit note payment type are considered for the payment types report with number of transactions and amount and offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchPaymentTypeReport(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'counter_ids' => $request->get('counter_ids'),
            'payment_type_id' => $request->get('payment_type_id'),
            'date' => $request->get('date'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        $lengthAwarePaginator = $paymentTypeQueries->getPaymentTypeListForReport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );
        $getTotalBadge = $paymentTypeQueries->getBadgesTotal(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $totalAmount = $getTotalBadge->sum('total_amount');
        $totalTransactions = $getTotalBadge->sum('total_transactions');

        return [
            'data' => PaymentTypeReportListResource::collection($lengthAwarePaginator->getCollection()),
            'total_records' => $lengthAwarePaginator->total(),
            'total_amount_badge' => $totalAmount,
            'total_transactions_badge' => $totalTransactions,
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function fetchTransactions(Request $request): array
    {
        $filterData = [
            'id' => $request->get('id'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'counter_ids' => $request->get('counter_ids'),
            'date' => $request->get('date'),
        ];

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        $transactionList = $paymentTypeQueries->getPaymentTypeTransactionList($filterData);

        return [
            'transaction_details' => PaymentTransactionListResource::collection($transactionList),
        ];
    }

    public function exportPaymentTypes(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'counter_ids' => $request->get('counter_ids'),
            'payment_type_id' => $request->get('payment_type_id'),
            'date' => $request->get('date'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        $paymentTypeList = $paymentTypeQueries->getPaymentTypeListExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new PaymentTypeReportExport($paymentTypeList), $filename);
    }
}
