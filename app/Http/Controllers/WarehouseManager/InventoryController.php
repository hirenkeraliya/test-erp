<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function getStocks(Request $request): array
    {
        $validatedData = $request->validate([
            'source_location_id' => ['required', 'integer'],
            'destination_location_id' => ['required', 'integer'],
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['required', 'integer'],
        ]);

        $companyId = session('warehouse_manager_selected_location_company_id');
        $inventoryService = resolve(InventoryService::class);

        [$sourceLocationId, $destinationLocationId] = $inventoryService->getLocation(
            (int) $validatedData['source_location_id'],
            (int) $validatedData['destination_location_id'],
            $companyId
        );

        if (! $sourceLocationId || ! $destinationLocationId) {
            abort(412, 'Invalid Source or Destination location selected');
        }

        /** @var array $productIds */
        $productIds = $validatedData['product_ids'];

        if (collect($productIds)->duplicates()->isNotEmpty()) {
            abort(412, 'Duplicate products detected in the selection. Please remove duplicates and try again.');
        }

        $productQueries = resolve(ProductQueries::class);
        $allProductsExist = $productQueries->doAllActiveProductsExist($companyId, $validatedData['product_ids']);

        if (! $allProductsExist) {
            abort(
                412,
                'One of the selected products does not match with our records. Can you please verify the product is not active/belongs to another company/is not listed?'
            );
        }

        DB::beginTransaction();

        try {
            $sourceInventories = collect([]);
            $destinationInventories = collect([]);
            $inventoryQueries = resolve(InventoryQueries::class);
            foreach ($validatedData['product_ids'] as $productId) {
                $sourceInventory = $inventoryQueries->fetchOrCreate((int) $sourceLocationId, (int) $productId);

                $sourceInventories->push($sourceInventory);

                $destinationInventory = $inventoryQueries->fetchOrCreate(
                    (int) $destinationLocationId,
                    (int) $productId
                );

                $destinationInventories->push($destinationInventory);
            }

            DB::commit();

            return [
                'source_inventories' => $sourceInventories,
                'destination_inventories' => $destinationInventories,
            ];
        } catch (Exception $exception) {
            Log::error('Warehouse Manager Get Stocks', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function getLocationStocksForPurchaseOrder(Request $request): array
    {
        $productIds = [];
        if (array_key_exists('product_ids', $request->all())) {
            $productIds = (array) $request->input('product_ids');
        }

        if (array_key_exists('product_id', $request->all())) {
            $productIds = [$request->input('product_id')];
        }

        $locationId = (int) $request->input('location_id');
        $externalLocationId = (int) $request->input('external_location_id');

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->getLocationStock($productIds, $locationId, $externalLocationId);
    }
}
