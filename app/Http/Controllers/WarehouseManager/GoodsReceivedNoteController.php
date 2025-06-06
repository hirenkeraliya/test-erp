<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteFileData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteListData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteUpdateStatusData;
use App\Domains\GoodsReceivedNote\Exports\GoodsReceivedNoteExport;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteCheckRequestService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNotePrintService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\Exports\GoodsReceivedNoteProductsExport;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\GoodsReceivedNoteProduct\Resources\GoodsReceivedNoteProductsResource;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Vendor\VendorQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class GoodsReceivedNoteController extends Controller
{
    public function __construct(
        protected GoodsReceivedNoteQueries $goodsReceivedNoteQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $grnNumber = $request->get('grn_number');

        return Inertia::render('goods_received_notes/Index', [
            'grnNumber' => $grnNumber ?? null,
            'importRecordStatus' => Status::getStatuses(),
            'goodsReceivedNoteModelMappingType' => ModelMapping::GOODS_RECEIVED_NOTE->name,
            'exportPermission' => PermissionList::getExportPermissionName('goods_received_note'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, DataCollection>
     */
    public function fetchGoodsReceivedNotes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'grn_number' => $request->get('grn_number'),
        ];

        $lengthAwarePaginator = $this->goodsReceivedNoteQueries->listQueryForWarehouseManager(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => GoodsReceivedNoteListData::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $vendorQueries = resolve(VendorQueries::class);

        return Inertia::render('goods_received_notes/Manage', [
            'vendors' => $vendorQueries->getVendorByCompanyId(
                session('warehouse_manager_selected_location_company_id')
            ),
        ]);
    }

    public function store(GoodsReceivedNoteData $goodsReceivedNoteData, Request $request): RedirectResponse
    {
        $companyId = session('warehouse_manager_selected_location_company_id');
        $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);
        $grnReferenceNumber = $goodsReceivedNoteService->generateGrnReference(
            $this->goodsReceivedNoteQueries,
            $companyId
        );

        $goodsReceivedNoteCheckRequestService = resolve(GoodsReceivedNoteCheckRequestService::class);
        $goodsReceivedNoteCheckRequestService->validateGrnReference($grnReferenceNumber, $companyId);

        $importRecordService = resolve(ImportRecordService::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $importRecordService->validateColumns(
            $goodsReceivedNoteData->uploaded_file,
            [],
            $companyId,
            ImportTypes::GOODS_RECEIVE_NOTE->value
        );

        DB::beginTransaction();

        try {
            /** @var WarehouseManager $user */
            $user = $request->user();

            $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

            $goodsReceivedNote = $goodsReceivedNoteQueries->addNew(
                $goodsReceivedNoteData,
                $companyId,
                $grnReferenceNumber,
                $user
            );

            $importRecordData = new ImportRecordData(
                ImportTypes::GOODS_RECEIVE_NOTE->value,
                $goodsReceivedNoteData->uploaded_file
            );

            $importRecord = $importRecordQueries->addNew($importRecordData, $user, $companyId, $goodsReceivedNote);

            DB::commit();

            ImportRecordsJob::dispatch($importRecord)->onQueue('high');

            return to_route('warehouse_manager.goods_received_notes.index')
                ->with('success', 'The Goods Received Note has been added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Goods-Received-Note', [
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
    public function getGoodsReceivedNoteProducts(int $goodsReceivedNoteId): array
    {
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $goodsReceivedNoteProducts = $goodsReceivedNoteProductQueries->getByGrnId(
            $goodsReceivedNoteId,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'data' => GoodsReceivedNoteProductsResource::collection($goodsReceivedNoteProducts),
        ];
    }

    public function goodsReceivedNotePrint(int $goodsReceivedNoteId): string
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $goodsReceivedNote = $goodsReceivedNoteQueries->getByIdWithGoodsReceivedNoteProduct(
            $goodsReceivedNoteId,
            session('warehouse_manager_selected_location_company_id')
        );

        $goodsReceivedNotePrintService = resolve(GoodsReceivedNotePrintService::class);

        return $goodsReceivedNotePrintService->goodsReceivedNotePrint($goodsReceivedNote);
    }

    public function exportGoodReceivedNote(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'grn_number' => $request->get('grn_number'),
        ];

        $goodsReceivedNotes = $this->goodsReceivedNoteQueries->getGoodeReceiveNotesExportForWarehouseManager(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id'),
        );

        return Excel::download(new GoodsReceivedNoteExport($goodsReceivedNotes), $filename);
    }

    public function exportGoodReceivedNoteProducts(int $goodsReceivedNoteId, string $fileName): BinaryFileResponse
    {
        $goodsReceiveNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $goodsReceivedNoteProducts = $goodsReceiveNoteProductQueries->getByGrnId(
            $goodsReceivedNoteId,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new GoodsReceivedNoteProductsExport($goodsReceivedNoteProducts), $fileName);
    }

    public function reUploadFailedRecord(
        GoodsReceivedNoteFileData $goodsReceivedNoteFileData,
        int $goodsReceivedNoteId,
        Request $request
    ): void {
        $companyId = session('warehouse_manager_selected_location_company_id');

        $goodsReceivedNote = $this->goodsReceivedNoteQueries->getById($goodsReceivedNoteId, $companyId);

        $importRecordService = resolve(ImportRecordService::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $importRecordService->validateColumns(
            $goodsReceivedNoteFileData->uploaded_file,
            [],
            $companyId,
            ImportTypes::GOODS_RECEIVE_NOTE->value
        );

        /** @var WarehouseManager $user */
        $user = $request->user();

        $importRecordData = new ImportRecordData(
            ImportTypes::GOODS_RECEIVE_NOTE->value,
            $goodsReceivedNoteFileData->uploaded_file
        );

        $importRecord = $importRecordQueries->addNew($importRecordData, $user, $companyId, $goodsReceivedNote);

        ImportRecordsJob::dispatch($importRecord)->onQueue('high');
    }

    public function markAsCancel(
        Request $request,
        GoodsReceivedNoteUpdateStatusData $goodsReceivedNoteUpdateStatusData,
        int $goodsReceivedNoteId
    ): RedirectResponse {
        $companyId = session('warehouse_manager_selected_location_company_id');

        $requestLock = Cache::lock('goods_received_note_cancel_operation_working_' . $goodsReceivedNoteId);

        $goodsReceivedNote = $this->goodsReceivedNoteQueries->getByIdWithSerialNumberRelation(
            $goodsReceivedNoteId,
            $companyId
        );

        if ($requestLock->get()) {
            if ($goodsReceivedNote->cancelled_at) {
                throw new RedirectBackWithErrorException('Goods received note already canceled.');
            }

            if ($goodsReceivedNote->importRecord && $goodsReceivedNote->importRecord->status !== Status::COMPLETED->value) {
                throw new RedirectBackWithErrorException('Goods received note has been processed.');
            }

            $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);
            $isFoundNotActiveSerialNumber = $goodsReceivedNoteService->checkGoodReceivedNoteProduct($goodsReceivedNote);
            if ($isFoundNotActiveSerialNumber) {
                throw new RedirectBackWithErrorException(
                    'You cannot cancel the goods received note (GRN) because one of the products is a serial product, and its serial number status is not active. As a result, the GRN cancellation is not allowed.'
                );
            }

            $goodsReceivedNoteService->markAsDeleteStatus($goodsReceivedNote);
            DB::beginTransaction();

            try {
                /** @var WarehouseManager $user */
                $user = $request->user();

                $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);

                $goodsReceivedNoteService->rollbackInventory(
                    $goodsReceivedNote,
                    $user,
                    $goodsReceivedNoteUpdateStatusData->remarks
                );

                DB::commit();

                return to_route('warehouse_manager.goods_received_notes.index')
                    ->with('success', 'The Goods Received Note has been canceled successfully.');
            } catch (Throwable $throwable) {
                Log::error('Goods-Received-Note', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);

                DB::rollBack();

                throw new RedirectBackWithErrorException($throwable->getMessage());
            } finally {
                $requestLock->release();
            }
        } else {
            throw new RedirectBackWithErrorException('Goods received notes cancel operation already in progress.');
        }
    }
}
