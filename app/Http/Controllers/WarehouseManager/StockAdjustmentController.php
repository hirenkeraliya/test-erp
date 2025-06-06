<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StockAdjustment\Exports\StockAdjustmentExport;
use App\Domains\StockAdjustment\Resources\StockAdjustmentListResource;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\StockAdjustmentItem\Exports\StockAdjustmentItemsExport;
use App\Domains\StockAdjustmentItem\Resources\StockAdjustmentItemsListResource;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentQueries $stockAdjustmentQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $stockAdjustmentId = $request->get('stock_adjustment_id');

        return Inertia::render('stock_adjustments/Index', [
            'stockAdjustmentId' => $stockAdjustmentId,
            'exportPermission' => PermissionList::getExportPermissionName('stock_adjustment'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchStockAdjustments(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'stock_adjustment_id' => $request->get('stock_adjustment_id'),
        ];

        $lengthAwarePaginator = $this->stockAdjustmentQueries
            ->warehouseManagerListQuery(
                $filterData,
                session('warehouse_manager_selected_location_company_id'),
                session('warehouse_manager_selected_location_id')
            );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StockAdjustmentListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchItems(int $stockAdjustmentId): array
    {
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);

        $stockAdjustmentItems = $stockAdjustmentItemQueries->getItemsByStockAdjustmentIdForWarehouseManager(
            $stockAdjustmentId,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        return [
            'data' => StockAdjustmentItemsListResource::collection($stockAdjustmentItems),
        ];
    }

    public function exportItems(int $stockAdjustmentId, string $filename): BinaryFileResponse
    {
        $stockAdjustment = $this->stockAdjustmentQueries->getByIdWithItemsForManagerPanel(
            $stockAdjustmentId,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id'),
        );

        return Excel::download(new StockAdjustmentItemsExport($stockAdjustment), $filename);
    }

    public function exportStockAdjustments(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'stock_adjustment_id' => $request->get('stock_adjustment_id'),
        ];

        $stockAdjustments = $this->stockAdjustmentQueries->getWarehouseManagerStockAdjustmentsExport(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        return Excel::download(new StockAdjustmentExport($stockAdjustments), $filename);
    }
}
