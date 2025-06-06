<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForStoreManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\StoreManagerApiGoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\StoreManagerApiGoodsReceivedNoteProductData;
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
use App\Domains\StoreManager\Services\StoreManagerService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoodsReceivedNoteController extends Controller
{
    public function getGoodsReceivedNotes(
        Request $request,
        StoreManagerApiGoodsReceivedNoteData $storeManagerApiGoodsReceivedNoteData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        /** @var int $locationId */
        $locationId = $storeManagerApiGoodsReceivedNoteData->store_id ?? $storeManagerApiGoodsReceivedNoteData->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            (int) $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore((int) $locationId);

        $dateRange = [
            $storeManagerApiGoodsReceivedNoteData->start_date,
            $storeManagerApiGoodsReceivedNoteData->end_date,
        ];

        $filterData = [
            'search_text' => $storeManagerApiGoodsReceivedNoteData->search_text,
            'sort_by' => $storeManagerApiGoodsReceivedNoteData->sort_by,
            'sort_direction' => $storeManagerApiGoodsReceivedNoteData->sort_direction,
            'per_page' => $storeManagerApiGoodsReceivedNoteData->per_page,
            'date_range' => $dateRange,
        ];

        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        $goodsReceivedNotes = $goodsReceivedNoteQueries->listQueryForStoreManagerApi(
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
        StoreManagerApiGoodsReceivedNoteProductData $storeManagerApiGoodsReceivedNoteProductData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        /** @var int $locationId */
        $locationId = $storeManagerApiGoodsReceivedNoteProductData->store_id ?? $storeManagerApiGoodsReceivedNoteProductData->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $goodsReceivedNoteProducts = $goodsReceivedNoteProductQueries->getByGrnIdForApi(
            $companyId,
            $storeManagerApiGoodsReceivedNoteProductData->all()
        );

        return [
            'data' => GoodsReceivedNoteProductsResource::collection($goodsReceivedNoteProducts),
            'last_page' => $goodsReceivedNoteProducts->lastPage(),
            'current_page' => $goodsReceivedNoteProducts->currentPage(),
            'per_page' => $goodsReceivedNoteProducts->perPage(),
            'total_records' => $goodsReceivedNoteProducts->total(),
        ];
    }

    public function store(
        Request $request,
        GoodsReceivedNoteStoreForStoreManagerAppData $goodsReceivedNoteStoreForStoreManagerAppData
    ): void {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerService = resolve(StoreManagerService::class);

        $storeManagerService->checkAuthorizationForStoreManager(
            $storeManager->id,
            $goodsReceivedNoteStoreForStoreManagerAppData->location_id
        );

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($goodsReceivedNoteStoreForStoreManagerAppData->location_id);

        $vendorQueries = resolve(VendorQueries::class);
        $vendor = $vendorQueries->getByIdAndCompanyId(
            $goodsReceivedNoteStoreForStoreManagerAppData->vendor_id,
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
            $goodsReceivedNoteStoreForStoreManagerAppData->uploaded_file,
            [],
            $companyId,
            ImportTypes::GOODS_RECEIVE_NOTE->value
        );

        DB::beginTransaction();

        try {
            $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

            $goodsReceivedNote = $goodsReceivedNoteQueries->addNewForInternalApplication(
                $goodsReceivedNoteStoreForStoreManagerAppData,
                $companyId,
                $grnReferenceNumber,
                $storeManager,
            );

            $importRecordData = new ImportRecordData(
                ImportTypes::GOODS_RECEIVE_NOTE->value,
                $goodsReceivedNoteStoreForStoreManagerAppData->uploaded_file
            );

            $importRecord = $importRecordQueries->addNew(
                $importRecordData,
                $storeManager,
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
