<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\DataObjects\WarehouseManagerApiStockTransferData;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Resources\StockTransferApiListResource;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\DataObjects\WarehouseManagerApiStockTransferItemData;
use App\Domains\StockTransferItem\Resources\StockTransferItemsListResource;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function getPaginatedStockTransfers(
        Request $request,
        WarehouseManagerApiStockTransferData $warehouseManagerApiStockTransferData,
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $dateRange = [
            $warehouseManagerApiStockTransferData->start_date,
            $warehouseManagerApiStockTransferData->end_date,
        ];

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            $warehouseManagerApiStockTransferData->id
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($warehouseManagerApiStockTransferData->id);

        $filterData = [
            'search_text' => $warehouseManagerApiStockTransferData->search_text,
            'sort_by' => $warehouseManagerApiStockTransferData->sort_by,
            'sort_direction' => $warehouseManagerApiStockTransferData->sort_direction,
            'per_page' => $warehouseManagerApiStockTransferData->per_page,
            'stock_transfer_date' => $dateRange,
            'transfer_type' => $warehouseManagerApiStockTransferData->transfer_type,
            'location_id' => $warehouseManagerApiStockTransferData->id,
            'select_status' => $warehouseManagerApiStockTransferData->status,
            'stock_transfer_id' => null,
            'dashboard_transfer_type' => null,
        ];

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferList = $stockTransferQueries->warehouseManagerListQueryForApi($filterData, $companyId);

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
        WarehouseManagerApiStockTransferItemData $warehouseManagerApiStockTransferItemData,
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        /** @var int $locationId */
        $locationId = $warehouseManagerApiStockTransferItemData->warehouse_id ??
            $warehouseManagerApiStockTransferItemData->location_id;

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            (int) $locationId,
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse((int) $locationId);

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByPaginatedStockTransferId(
            $warehouseManagerApiStockTransferItemData->all(),
            (int) $companyId,
        );

        return [
            'stock_transfer_items' => StockTransferItemsListResource::collection($stockTransferItems),
            'last_page' => $stockTransferItems->lastPage(),
            'current_page' => $stockTransferItems->currentPage(),
            'per_page' => $stockTransferItems->perPage(),
            'total_records' => $stockTransferItems->total(),
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
