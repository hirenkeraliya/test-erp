<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\CashMovementReason\DataObjects\CashMovementReasonData;
use App\Domains\CashMovementReason\Enums\StaticCashMovementReasons;
use App\Domains\CashMovementReason\Exports\CashMovementReasonExport;
use App\Domains\CashMovementReason\Resources\AdminCashMovementReasonListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CashMovementReasonController extends Controller
{
    public function __construct(
        protected CashMovementReasonQueries $cashMovementReasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('cash_movement_reasons/Index', [
            'staticCashMovementReasons' => collect(StaticCashMovementReasons::formattedForSelection())->pluck(
                'id'
            )->toArray(),
            'exportPermission' => PermissionList::getExportPermissionName('cash_movement_reason'),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchCashMovementReasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->cashMovementReasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminCashMovementReasonListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('cash_movement_reasons/Manage', [
            'cashMovementTypes' => CashMovementTypes::formattedForSelection(),
        ]);
    }

    public function store(CashMovementReasonData $cashMovementReasonData): RedirectResponse
    {
        $this->cashMovementReasonQueries->addNew($cashMovementReasonData, session('admin_company_id'));

        return to_route('admin.cash_movement_reasons.index')->with(
            'success',
            'Cash movement reason added successfully.'
        );
    }

    public function edit(int $cashMovementReasonId): Response|RedirectResponse
    {
        if ($this->checkForStaticCashMovementReason($cashMovementReasonId)) {
            return to_route('admin.cash_movement_reasons.index')->with(
                'error',
                'This cash movement reason is the system default and cannot be modified'
            );
        }

        $cashMovementReason = $this->cashMovementReasonQueries->getById(
            $cashMovementReasonId,
            session('admin_company_id')
        );

        return Inertia::render('cash_movement_reasons/Manage', [
            'cashMovementTypes' => CashMovementTypes::formattedForSelection(),
            'cashMovementReason' => $cashMovementReason,
        ]);
    }

    public function update(CashMovementReasonData $cashMovementReasonData, int $cashMovementReasonId): RedirectResponse
    {
        $this->cashMovementReasonQueries->update(
            $cashMovementReasonData,
            $cashMovementReasonId,
            session('admin_company_id')
        );

        return to_route('admin.cash_movement_reasons.index')->with(
            'success',
            'Cash movement reason updated successfully.'
        );
    }

    public function exportCashMovementReasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $cashMovementReasons = $this->cashMovementReasonQueries->getCashMovementReasonsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new CashMovementReasonExport($cashMovementReasons), $filename);
    }

    private function checkForStaticCashMovementReason(int $staticCashMovementReasonId): bool
    {
        return StaticCashMovementReasons::getCasesValue()->contains($staticCashMovementReasonId);
    }
}
