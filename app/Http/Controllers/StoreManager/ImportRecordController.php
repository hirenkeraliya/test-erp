<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Resources\StockTakesImportRecordListResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class ImportRecordController extends Controller
{
    public function __construct(
        protected ImportRecordQueries $importRecordQueries
    ) {
    }

    public function index(?int $id = null): Response
    {
        return Inertia::render('import_records/Index', [
            'importRecordId' => $id,
            'statuses' => Status::getStatuses(),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchImportRecords(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'import_record_id' => $request->get('import_record_id'),
            'date_range' => $request->get('date_range'),
            'import_type' => $request->get('import_type'),
            'status' => $request->get('status'),
        ];

        $lengthAwarePaginator = $this->importRecordQueries->listQueryForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StockTakesImportRecordListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function getPendingImportRecordCount(string $moduleType): array
    {
        return [
            'pending_counts' => $this->importRecordQueries->getPendingImportRecordCount(
                $moduleType,
                session('store_manager_selected_location_company_id')
            ),
        ];
    }
}
