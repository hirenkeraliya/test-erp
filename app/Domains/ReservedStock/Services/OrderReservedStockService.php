<?php

declare(strict_types=1);

namespace App\Domains\ReservedStock\Services;

use App\CommonFunctions;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Auth\User;

class OrderReservedStockService
{
    public function updateReservedStock(
        OrderItem $orderItem,
        array $item,
        CheckOrderDetailsService $checkOrderDetailsService
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate(
            $checkOrderDetailsService->location->getKey(),
            (int) $item['id']
        );

        $product = $checkOrderDetailsService->products->firstWhere('id', $item['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $this->updateAssemblyProductReservedStock($orderItem, $item, $checkOrderDetailsService, $product);

            return;
        }

        if (! $product->has_batch) {
            $this->addReservedStock($inventory->id, $product, $orderItem, (float) $item['quantity'], null);
        }

        $inventoryQueries->increaseReservedStock($inventory, (float) $item['quantity']);
    }

    public function updateAssemblyProductReservedStock(
        OrderItem $orderItem,
        array $item,
        CheckOrderDetailsService $checkSaleDetailsService,
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

            $this->addReservedStock($inventory->id, $product, $orderItem, $itemQuantity, null);

            $inventoryQueries->increaseReservedStock($inventory, $itemQuantity);
        }
    }

    public function addReservedStock(
        int $inventoryId,
        Product $product,
        Model $orderItem,
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
                $this->updateInventoryUnitsForOrderItem($inventoryId, $inventoryUnit, $orderItem, $totalQuantity);
                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;

            $this->updateInventoryUnitsForOrderItem(
                $inventoryId,
                $inventoryUnit,
                $orderItem,
                (float) $inventoryUnit->quantity,
            );
        }

        if ($totalQuantity > 0.0) {
            $this->updateNegativeInventoryUnitsForOrderItem($inventoryId, $orderItem, $totalQuantity, $batchId);
        }
    }

    public function updateInventoryUnitsForOrderItem(
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        Model $orderItem,
        float $quantity,
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStockQueries->addNew($inventoryId, $inventoryUnit->id, $quantity, $orderItem);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->increaseReservedStock($inventoryUnit, $quantity);
    }

    public function updateNegativeInventoryUnitsForOrderItem(
        int $inventoryId,
        Model $orderItem,
        float $quantity,
        ?int $batchId,
    ): void {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId($inventoryId, $purchaseAmountId, $batchId);

        $this->updateInventoryUnitsForOrderItem($inventoryId, $inventoryUnit, $orderItem, $quantity);
    }

    public function removeReservationStock(OrderItem $orderItem, User $user, string $happenedAt): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($orderItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->refreshInventory($reservedStock->inventory);
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $reservedStock->quantity;
            $inventoryStock = ($inventory->stock + $inventory->reserved_stock);
            $inventoryStock -= $quantity;

            $orderItemUnitQueries->addNew($orderItem, $inventory->id, $inventoryUnit, $quantity);

            $inventoryUpdateQueries->addNew(
                $inventory->product_id,
                (float) ('-' . $quantity),
                $inventory->location_id,
                $orderItem,
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

    public function revertReservedStock(OrderItem $orderItem): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($orderItem);
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
