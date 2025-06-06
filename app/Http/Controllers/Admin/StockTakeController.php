<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StockTake\Exports\StockTakeExport;
use App\Domains\StockTake\Resources\StockTakeListResource;
use App\Domains\StockTake\StockTakeQueries;
use App\Domains\StockTakeProduct\Exports\StockTakeProductExport;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTakeController extends Controller
{
    public function __construct(
        protected StockTakeQueries $stockTakeQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('reports/StockTakes', [
            'exportPermission' => PermissionList::getExportPermissionName('stock_take'),
            'helpCenterMessages' => 'Show only the submitted stock take reports and provide search options and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchStockTakes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->stockTakeQueries->getAdminListQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StockTakeListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportStockTakeProducts(int $stockTakeId, string $fileName): BinaryFileResponse
    {
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $stockTakeProducts = $stockTakeProductQueries->getSubmittedStockTakeProductsByStockTakeId(
            $stockTakeId,
            session('admin_company_id')
        );

        return Excel::download(new StockTakeProductExport($stockTakeProducts), $fileName);
    }

    public function exportStockTakes(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $stockTakes = $this->stockTakeQueries->getStockTakesExport($filterData, session('admin_company_id'));

        return Excel::download(new StockTakeExport($stockTakes, $filteredColumns), $filename);
    }
}
