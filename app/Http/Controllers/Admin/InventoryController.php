<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\InventoryByProductAndStoreResource;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $companyId = session('admin_company_id');
        $inventoryService = resolve(InventoryService::class);

        [$sourceLocationId, $destinationLocationId] = $inventoryService->getLocation(
            (int) $validatedData['source_location_id'],
            (int) $validatedData['destination_location_id'],
            $companyId
        );

        /** @var array $productIds */
        $productIds = $validatedData['product_ids'];

        if (collect($productIds)->duplicates()->isNotEmpty()) {
            abort(412, 'Duplicate products detected in the selection. Please remove duplicates and try again.');
        }

        if (! $sourceLocationId || ! $destinationLocationId) {
            abort(412, 'Invalid Source or Destination location selected');
        }

        $productQueries = resolve(ProductQueries::class);
        $allProductsExist = $productQueries->doAllActiveProductsExist($companyId, $validatedData['product_ids']);

        if (! $allProductsExist) {
            abort(
                412,
                'One of the selected products does not match with our records. Can you please verify the product is not active/belongs to another company/is not listed?'
            );
        }

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

        return [
            'source_inventories' => $sourceInventories,
            'destination_inventories' => $destinationInventories,
        ];
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

    public function getMatchingUpcProductWithStore(Request $request): array
    {
        $validatedData = $request->validate([
            'import_product_upc' => ['required', 'array'],
            'import_product_upc.*.upc' => ['required', 'string'],
            'import_product_upc.*.code' => ['required', 'string'],
        ]);

        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->getActiveProductsByUpcAndStoreCode(
            $validatedData['import_product_upc'],
            session('admin_company_id')
        );

        return [
            'products' => InventoryByProductAndStoreResource::collection($inventories),
            'products_count' => $inventories->count(),
        ];
    }
}
