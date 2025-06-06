<?php

declare(strict_types=1);

namespace App\Domains\ReservedStock\Services;

use App\CommonFunctions;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Foundation\Auth\User;

class SaleReservedStockService
{
    public function updateReservedStock(
        SaleItem $saleItem,
        array $item,
        CheckSaleDetailsService $checkSaleDetailsService
    ): void {
        $product = $checkSaleDetailsService->products->firstWhere('id', $item['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $this->updateAssemblyProductReservedStock($saleItem, $item, $checkSaleDetailsService, $product);

            return;
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate(
            $checkSaleDetailsService->location->getKey(),
            (int) $item['id']
        );

        $productBoxUnits = $saleItem->product_box_units > 0 ? $saleItem->product_box_units : 1;
        $itemQuantity = CommonFunctions::numberFormat($item['quantity'] * $productBoxUnits);

        if (! $product->has_batch) {
            $this->addReservedStock($inventory->id, $product, $saleItem, $itemQuantity, null);
        }

        if ($product->has_batch && $checkSaleDetailsService->hasBatchDetails($item)) {
            foreach ($item['batch_details'] as $batchDetail) {
                $batch = $checkSaleDetailsService->batches->where('product_id', $product->id)
                        ->firstWhere('number', $batchDetail['batch_number']);
                $batchId = $batch?->id;

                $batchQuantity = CommonFunctions::numberFormat($productBoxUnits * $batchDetail['quantity']);

                $this->addReservedStock($inventory->id, $product, $saleItem, $batchQuantity, $batchId);
            }
        }

        $inventoryQueries->increaseReservedStock($inventory, $itemQuantity);
    }

    public function updateAssemblyProductReservedStock(
        SaleItem $saleItem,
        array $item,
        CheckSaleDetailsService $checkSaleDetailsService,
        Product $assemblyProduct
    ): void {
        foreach ($assemblyProduct->assemblyChildProducts as $assemblyChildProduct) {
            /** @var Product $product */
            $product = $assemblyChildProduct->product;
            $inventoryQueries = resolve(InventoryQueries::class);
            $inventory = $inventoryQueries->fetchOrCreate(
                $checkSaleDetailsService->location->getKey(),
                $product->id
            );

            $itemQuantity = CommonFunctions::numberFormat($item['quantity'] * $assemblyChildProduct->units);

            $this->addReservedStock($inventory->id, $product, $saleItem, $itemQuantity, null);

            $inventoryQueries->increaseReservedStock($inventory, $itemQuantity);
        }
    }

    public function addReservedStock(
        int $inventoryId,
        Product $product,
        Model $saleItem,
        float $quantity,
        ?int $batchId,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $totalQuantity = abs($quantity);
        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventoryId, $batchId);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $this->updateInventoryUnitsForSaleItem($inventoryId, $inventoryUnit, $saleItem, $totalQuantity);
                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;

            $this->updateInventoryUnitsForSaleItem(
                $inventoryId,
                $inventoryUnit,
                $saleItem,
                (float) $inventoryUnit->quantity,
            );
        }

        if ($totalQuantity > 0.0) {
            $this->updateNegativeInventoryUnitsForSaleItem($inventoryId, $saleItem, $totalQuantity, $batchId);
        }
    }

    public function updateInventoryUnitsForSaleItem(
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        Model $saleItem,
        float $quantity,
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStockQueries->addNew($inventoryId, $inventoryUnit->id, $quantity, $saleItem);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->increaseReservedStock($inventoryUnit, $quantity);
    }

    public function updateNegativeInventoryUnitsForSaleItem(
        int $inventoryId,
        Model $saleItem,
        float $quantity,
        ?int $batchId,
    ): void {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId($inventoryId, $purchaseAmountId, $batchId);

        $this->updateInventoryUnitsForSaleItem($inventoryId, $inventoryUnit, $saleItem, $quantity);
    }

    public function removeReservationStock(SaleItem $saleItem, User $user, string $happenedAt): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($saleItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->refreshInventory($reservedStock->inventory);
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $reservedStock->quantity;
            $inventoryStock = ($inventory->stock + $inventory->reserved_stock);
            $inventoryStock -= $quantity;

            $saleItemUnitQueries->addNew($saleItem, $inventory->id, $inventoryUnit, $quantity);

            $inventoryUpdateQueries->addNew(
                $inventory->product_id,
                (float) ('-' . $quantity),
                $inventory->location_id,
                $saleItem,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
                $happenedAt
            );

            $inventoryUnitQueries->decreaseReservedStock($inventoryUnit, $quantity);
            $inventoryQueries->decreaseReservedStock($inventory, $quantity);
            $reservedStockQueries->delete($reservedStock);
        }
    }

    public function revertReservedStock(SaleItem $saleItem): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($saleItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->refreshInventory($reservedStock->inventory);
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->revertReservedStock($inventoryUnit, $quantity);
            $inventoryQueries->revertReservedStock($inventory, $quantity);
            $reservedStockQueries->delete($reservedStock);
        }
    }
}
