<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Batch\BatchQueries;
use App\Domains\Batch\Exports\BatchExpiryExport;
use App\Domains\Batch\Resources\BatchExpiryReportResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BatchExpiryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reports/batch_expiry/BatchExpiry', [
            'exportPermission' => PermissionList::getExportPermissionName('batch_expiry'),
            'helpCenterMessages' => 'Display the batch expiry report, focusing on products with batch details. Include location and product details, and offer advanced filters, search options, and seamless export capabilities for thorough analysis and insights.',
        ]);
    }

    public function fetchBatchExpiry(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'location_id' => session('warehouse_manager_selected_location_id'),
            'tag_ids' => $request->get('tag_ids'),
            'date_range' => $request->get('date_range'),
        ];

        $batchQueries = resolve(BatchQueries::class);
        $companyId = session('warehouse_manager_selected_location_company_id');

        $lengthAwarePaginator = $batchQueries->batchExpiryReportList($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => BatchExpiryReportResource::collection($lengthAwarePaginator),
        ];
    }

    public function exportBatchExpiry(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'location_id' => session('warehouse_manager_selected_location_id'),
            'tag_ids' => $request->get('tag_ids'),
            'date_range' => $request->get('date_range'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $batchQueries = resolve(BatchQueries::class);
        $companyId = session('warehouse_manager_selected_location_company_id');

        $batches = $batchQueries->batchExpiryReportForExport($filterData, $companyId);

        return Excel::download(new BatchExpiryExport($batches, $filteredColumns), $filename);
    }
}
