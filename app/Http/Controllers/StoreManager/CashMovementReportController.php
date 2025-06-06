<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovement\Exports\CashMovementExport;
use App\Domains\CashMovement\Resources\StoreManagerCashMovementReportResource;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CashMovementReportController extends Controller
{
    public function __construct(
        protected CashMovementQueries $cashMovementQueries
    ) {
    }

    public function index(): Response
    {
        $counterQueries = resolve(CounterQueries::class);

        $counters = $counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Inertia::render('reports/CashMovements', [
            'counters' => $counters,
            'cashMovementType' => CashMovementTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('cash_movement'),
            'helpCenterMessages' => 'Show all the cash movements report with counter, store, authorizer person who perform this action, cash movement reason and amount offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchCashMovements(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cash_movement_type' => $request->get('cash_movement_type'),
        ];

        $lengthAwarePaginator = $this->cashMovementQueries->getPaginatedCashMovementListsForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerCashMovementReportResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCashMovements(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cash_movement_type' => $request->get('cash_movement_type'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $cashMovements = $this->cashMovementQueries->getCashMovementListsForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return Excel::download(new CashMovementExport($cashMovements, $filteredColumns), $filename);
    }
}
