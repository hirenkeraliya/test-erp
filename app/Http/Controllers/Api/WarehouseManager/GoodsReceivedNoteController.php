<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForWarehouseManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\WarehouseManagerApiGoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\WarehouseManagerApiGoodsReceivedNoteProductData;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Resources\GoodsReceivedNoteListApiResource;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteCheckRequestService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\GoodsReceivedNoteProduct\Resources\GoodsReceivedNoteProductsResource;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\Vendor\VendorQueries;
use App\Domains\WarehouseManager\Services\WarehouseManagerService;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoodsReceivedNoteController extends Controller
{
    public function getGoodsReceivedNotes(
        Request $request,
        WarehouseManagerApiGoodsReceivedNoteData $warehouseManagerApiGoodsReceivedNoteData
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        /** @var int $locationId */
        $locationId = $warehouseManagerApiGoodsReceivedNoteData->warehouse_id ??
            $warehouseManagerApiGoodsReceivedNoteData->location_id;

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            (int) $locationId
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($locationId);

        $dateRange = [
            $warehouseManagerApiGoodsReceivedNoteData->start_date,
            $warehouseManagerApiGoodsReceivedNoteData->end_date,
        ];

        $filterData = [
            'search_text' => $warehouseManagerApiGoodsReceivedNoteData->search_text,
            'sort_by' => $warehouseManagerApiGoodsReceivedNoteData->sort_by,
            'sort_direction' => $warehouseManagerApiGoodsReceivedNoteData->sort_direction,
            'per_page' => $warehouseManagerApiGoodsReceivedNoteData->per_page,
            'date_range' => $dateRange,
        ];

        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        $goodsReceivedNotes = $goodsReceivedNoteQueries->listQueryForWarehouseManagerApi(
            $filterData,
            $companyId,
            $locationId
        );

        return [
            'data' => GoodsReceivedNoteListApiResource::collection($goodsReceivedNotes->getCollection()),
            'last_page' => $goodsReceivedNotes->lastPage(),
            'current_page' => $goodsReceivedNotes->currentPage(),
            'per_page' => $goodsReceivedNotes->perPage(),
        ];
    }

    public function getGoodsReceivedNoteProducts(
        Request $request,
        WarehouseManagerApiGoodsReceivedNoteProductData $warehouseManagerApiGoodsReceivedNoteProductData
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        /** @var int $locationId */
        $locationId = $warehouseManagerApiGoodsReceivedNoteProductData->warehouse_id ??
            $warehouseManagerApiGoodsReceivedNoteProductData->location_id;

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            (int) $locationId
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($locationId);

        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $goodsReceivedNoteProducts = $goodsReceivedNoteProductQueries->getByGrnIdForApi(
            $companyId,
            $warehouseManagerApiGoodsReceivedNoteProductData->all()
        );

        return [
            'data' => GoodsReceivedNoteProductsResource::collection($goodsReceivedNoteProducts),
            'total_records' => $goodsReceivedNoteProducts->total(),
            'last_page' => $goodsReceivedNoteProducts->lastPage(),
            'current_page' => $goodsReceivedNoteProducts->currentPage(),
            'per_page' => $goodsReceivedNoteProducts->perPage(),
        ];
    }

    public function store(
        Request $request,
        GoodsReceivedNoteStoreForWarehouseManagerAppData $goodsReceivedNoteStoreForWarehouseManagerAppData
    ): void {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $warehouseManagerService = resolve(WarehouseManagerService::class);

        $warehouseManagerService->checkAuthorizationForWarehouseManager(
            $warehouseManager->id,
            $goodsReceivedNoteStoreForWarehouseManagerAppData->location_id
        );

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse(
            $goodsReceivedNoteStoreForWarehouseManagerAppData->location_id
        );

        $vendorQueries = resolve(VendorQueries::class);
        $vendor = $vendorQueries->getByIdAndCompanyId(
            $goodsReceivedNoteStoreForWarehouseManagerAppData->vendor_id,
            $companyId
        );

        if (! $vendor) {
            abort(412, 'The provided vendor is not registered in our records.');
        }

        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);

        $grnReferenceNumber = $goodsReceivedNoteService->generateGrnReference(
            $goodsReceivedNoteQueries,
            $companyId
        );

        $goodsReceivedNoteCheckRequestService = resolve(GoodsReceivedNoteCheckRequestService::class);
        $goodsReceivedNoteCheckRequestService->validateGrnReference($grnReferenceNumber, $companyId);

        $importRecordService = resolve(ImportRecordService::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        $importRecordService->validateColumns(
            $goodsReceivedNoteStoreForWarehouseManagerAppData->uploaded_file,
            [],
            $companyId,
            ImportTypes::GOODS_RECEIVE_NOTE->value
        );

        DB::beginTransaction();

        try {
            $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

            $goodsReceivedNote = $goodsReceivedNoteQueries->addNewForInternalApplication(
                $goodsReceivedNoteStoreForWarehouseManagerAppData,
                $companyId,
                $grnReferenceNumber,
                $warehouseManager,
            );

            $importRecordData = new ImportRecordData(
                ImportTypes::GOODS_RECEIVE_NOTE->value,
                $goodsReceivedNoteStoreForWarehouseManagerAppData->uploaded_file
            );

            $importRecord = $importRecordQueries->addNew(
                $importRecordData,
                $warehouseManager,
                $companyId,
                $goodsReceivedNote
            );

            DB::commit();

            ImportRecordsJob::dispatch($importRecord)->onQueue('high');
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

            abort(412, $throwable->getMessage());
        }
    }
}
