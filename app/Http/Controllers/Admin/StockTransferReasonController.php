<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StockTransferReason\DataObjects\StockTransferReasonData;
use App\Domains\StockTransferReason\Exports\StockTransferReasonExport;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferReasonController extends Controller
{
    public function __construct(
        protected StockTransferReasonQueries $stockTransferReasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('stock_transfer_reasons/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('stock_transfer_reason'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchStockTransferReasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->stockTransferReasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(StockTransferReasonData $stockTransferData): RedirectResponse
    {
        $this->stockTransferReasonQueries->addNew($stockTransferData, session('admin_company_id'));

        return to_route('admin.stock_transfer_reasons.index')->with(
            'success',
            'The stock transfer reason has been added successfully.'
        );
    }

    public function edit(int $stockTransferReasonId): Response
    {
        return Inertia::render('stock_transfer_reasons/Manage', [
            'stockTransferReason' => $this->stockTransferReasonQueries->getById(
                $stockTransferReasonId,
                session('admin_company_id')
            ),
        ]);
    }

    public function update(StockTransferReasonData $stockTransferData, int $stockTransferReasonId): RedirectResponse
    {
        $this->stockTransferReasonQueries->update(
            $stockTransferData,
            $stockTransferReasonId,
            session('admin_company_id')
        );

        return to_route('admin.stock_transfer_reasons.index')->with(
            'success',
            'Stock Transfer Reason updated successfully.'
        );
    }

    public function exportStockTransferReasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $stockTransferReasons = $this->stockTransferReasonQueries->getStockTransferReasonsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new StockTransferReasonExport($stockTransferReasons), $filename);
    }
}
