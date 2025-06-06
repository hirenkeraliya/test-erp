<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\DataObjects\StoreManagerStockTransferData;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Resources\StockTransferApiListResource;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\DataObjects\StoreManagerStockTransferItemData;
use App\Domains\StockTransferItem\Resources\StockTransferItemsListResource;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function getPaginatedStockTransfers(
        Request $request,
        StoreManagerStockTransferData $storeManagerStockTransferData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $dateRange = [$storeManagerStockTransferData->start_date, $storeManagerStockTransferData->end_date];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $storeManagerStockTransferData->id
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($storeManagerStockTransferData->id);

        $filterData = [
            'search_text' => $storeManagerStockTransferData->search_text,
            'sort_by' => $storeManagerStockTransferData->sort_by,
            'sort_direction' => $storeManagerStockTransferData->sort_direction,
            'per_page' => $storeManagerStockTransferData->per_page,
            'stock_transfer_date' => $dateRange,
            'transfer_type' => $storeManagerStockTransferData->transfer_type,
            'location_id' => $storeManagerStockTransferData->id,
            'select_status' => $storeManagerStockTransferData->status,
            'stock_transfer_id' => null,
            'dashboard_transfer_type' => null,
        ];

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferList = $stockTransferQueries->storeManagerListQueryForApi($filterData, $companyId);

        return [
            'data' => StockTransferApiListResource::collection($stockTransferList->getCollection()),
            'total_records' => $stockTransferList->total(),
            'last_page' => $stockTransferList->lastPage(),
            'current_page' => $stockTransferList->currentPage(),
            'per_page' => $stockTransferList->perPage(),
        ];
    }

    public function getStockTransferItemsByStockTransferId(
        Request $request,
        StoreManagerStockTransferItemData $storeManagerStockTransferItemData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        /** @var int $locationId */
        $locationId = $storeManagerStockTransferItemData->store_id ?? $storeManagerStockTransferItemData->location_id;

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

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByPaginatedStockTransferId(
            $storeManagerStockTransferItemData->all(),
            (int) $companyId,
        );

        return [
            'stock_transfer_items' => StockTransferItemsListResource::collection($stockTransferItems),
            'total_records' => $stockTransferItems->total(),
            'last_page' => $stockTransferItems->lastPage(),
            'current_page' => $stockTransferItems->currentPage(),
            'per_page' => $stockTransferItems->perPage(),
        ];
    }

    public function getTransferTypes(): array
    {
        return [
            'transfer_types' => TransferTypeForReport::getList(),
        ];
    }

    public function getStatusList(): array
    {
        return [
            'status_list' => StatusTypes::getList(),
        ];
    }
}
