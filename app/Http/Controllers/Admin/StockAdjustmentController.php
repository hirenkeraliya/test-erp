<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentData;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentFileData;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockAdjustment\Exports\StockAdjustmentExport;
use App\Domains\StockAdjustment\Resources\StockAdjustmentListResource;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\StockAdjustmentItem\Exports\StockAdjustmentItemsExport;
use App\Domains\StockAdjustmentItem\Resources\StockAdjustmentItemsListResource;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

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
            'importRecordStatus' => Status::getStatuses(),
            'stockAdjustmentModelMappingType' => ModelMapping::STOCK_ADJUSTMENT->name,
            'stockAdjustmentStaticDetails' => [
                'sti' => StockAdjustmentTypes::STI->value,
                'sto' => StockAdjustmentTypes::STO->value,
            ],
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

        $lengthAwarePaginator = $this->stockAdjustmentQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StockAdjustmentListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(EmployeeQueries $employeeQueries): Response
    {
        return Inertia::render('stock_adjustments/Manage', [
            'stockAdjustmentTypes' => StockAdjustmentTypes::formattedForSelection(nameInTitleCase: false),
            'stockAdjustmentStaticDetails' => [
                'sti' => StockAdjustmentTypes::STI->value,
                'sto' => StockAdjustmentTypes::STO->value,
            ],
            'employees' => $employeeQueries->getFormattedEmployeesOf(session('admin_company_id')),
        ]);
    }

    public function store(StockAdjustmentData $stockAdjustmentData, Request $request): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $importRecordService = resolve(ImportRecordService::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $importTypeId = ImportTypes::STOCK_ADJUSTMENT_STI->value;

        if ($stockAdjustmentData->type_id === StockAdjustmentTypes::STO->value) {
            $importTypeId = ImportTypes::STOCK_ADJUSTMENT_STO->value;
        }

        $importRecordService->validateColumns($stockAdjustmentData->uploaded_file, [], $companyId, $importTypeId);

        DB::beginTransaction();

        try {
            /** @var Admin $user */
            $user = $request->user();

            $stockAdjustment = $this->stockAdjustmentQueries->addNew($stockAdjustmentData, $companyId, $user);

            $importRecordData = new ImportRecordData($importTypeId, $stockAdjustmentData->uploaded_file);

            $importRecord = $importRecordQueries->addNew($importRecordData, $user, $companyId, $stockAdjustment);

            DB::commit();

            ImportRecordsJob::dispatch($importRecord)->onQueue('high');

            return to_route('admin.stock_adjustments.index')
                ->with('success', 'Stock Adjustment added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Stock-Adjustments', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            throw new RedirectBackWithErrorException($throwable->getMessage());
        }
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getStockAdjustmentItems(int $stockAdjustmentId): array
    {
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $stockAdjustmentItems = $stockAdjustmentItemQueries->getItemsByStockAdjustmentId(
            $stockAdjustmentId,
            session('admin_company_id')
        );

        return [
            'data' => StockAdjustmentItemsListResource::collection($stockAdjustmentItems),
        ];
    }

    public function exportItems(int $stockAdjustmentId, string $filename): BinaryFileResponse
    {
        $stockAdjustment = $this->stockAdjustmentQueries->getByIdWithItems(
            $stockAdjustmentId,
            session('admin_company_id')
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

        $stockAdjustments = $this->stockAdjustmentQueries->getStockAdjustmentsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new StockAdjustmentExport($stockAdjustments), $filename);
    }

    public function reUploadFailedRecord(
        StockAdjustmentFileData $stockAdjustmentFileData,
        int $stockAdjustmentId,
        Request $request
    ): void {
        $companyId = session('admin_company_id');

        $stockAdjustment = $this->stockAdjustmentQueries->getById($stockAdjustmentId, $companyId);

        $importRecordService = resolve(ImportRecordService::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $importTypeId = ImportTypes::STOCK_ADJUSTMENT_STI->value;

        if ($stockAdjustment->type_id === StockAdjustmentTypes::STO->value) {
            $importTypeId = ImportTypes::STOCK_ADJUSTMENT_STO->value;
        }

        /** @var Admin $user */
        $user = $request->user();

        $importRecordService->validateColumns($stockAdjustmentFileData->uploaded_file, [], $companyId, $importTypeId);

        $importRecordData = new ImportRecordData($importTypeId, $stockAdjustmentFileData->uploaded_file);

        $importRecord = $importRecordQueries->addNew($importRecordData, $user, $companyId, $stockAdjustment);

        ImportRecordsJob::dispatch($importRecord)->onQueue('high');
    }
}
