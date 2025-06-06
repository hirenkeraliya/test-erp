<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Exports\ExportRecordExport;
use App\Domains\ExportRecord\Resources\MainExportRecordListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportRecordController extends Controller
{
    public function __construct(
        protected ExportRecordQueries $exportRecordQueries
    ) {
    }

    public function index(Request $request, ?int $id = null): Response
    {
        return Inertia::render('export_records/Index', [
            'exportRecordId' => $id,
            'exportTypes' => ExportRecordTypes::getList(),
            'statuses' => ExportRecordStatuses::getList(),
            'staticStatuses' => ExportRecordStatuses::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('export_record'),
            'exportFilterData' => (int) $request->get('export_type') > 0 ? (int) $request->get('export_type') : null,
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchExportRecords(Request $request): array
    {
        $filterData = $this->getFilterData($request);

        $lengthAwarePaginator = $this->exportRecordQueries->listQueryForWarehouseManager(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MainExportRecordListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportRecords(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilterData($request);

        $exportRecords = $this->exportRecordQueries->exportListQueryForWarehouseManager(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new ExportRecordExport($exportRecords), $filename);
    }

    private function getFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'export_record_id' => $request->get('export_record_id'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
            'export_type' => $request->get('export_type'),
        ];
    }
}
