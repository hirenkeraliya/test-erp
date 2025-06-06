<?php

declare(strict_types=1);

namespace App\Domains\InventoryUnit;

use App\Domains\Batch\BatchQueries;
use App\Models\InventoryUnit;
use Closure;
use Illuminate\Support\Collection;

class InventoryUnitQueries
{
    public function addNew(
        string $quantity,
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId,
        ?int $serialNumberId = null
    ): void {
        InventoryUnit::create([
            'inventory_id' => $inventoryId,
            'purchase_amount_id' => $purchaseAmountId,
            'batch_id' => $batchId,
            'quantity' => $quantity,
            'serial_number_id' => $serialNumberId,
        ]);
    }

    public function addNewAndGetId(
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId,
        ?int $serialNumberId = null
    ): InventoryUnit {
        return InventoryUnit::firstOrCreate([
            'inventory_id' => $inventoryId,
            'purchase_amount_id' => $purchaseAmountId,
            'batch_id' => $batchId,
            'serial_number_id' => $serialNumberId,
        ]);
    }

    public function getById(int $inventoryUnitId): InventoryUnit
    {
        return InventoryUnit::select('id', 'inventory_id', 'quantity', 'reserved_stock')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->findOrFail($inventoryUnitId);
    }

    public function getByInventoryId(int $inventoryId, ?int $serialNumberId = null): Collection
    {
        return InventoryUnit::where('inventory_id', $inventoryId)
            ->where('quantity', '>', 0)
            ->where('serial_number_id', $serialNumberId)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    }

    public function getByInventoryBatchId(int $inventoryId, int $batchId, ?int $serialNumberId = null): Collection
    {
        return InventoryUnit::where('inventory_id', $inventoryId)
            ->where('batch_id', $batchId)
            ->where('quantity', '>', 0)
            ->where('serial_number_id', $serialNumberId)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    }

    public function getByInventoryIdOrderByBatchExpiryDate(int $inventoryId, ?int $serialNumberId = null): Collection
    {
        $batchQueries = new BatchQueries();

        return InventoryUnit::where('inventory_id', $inventoryId)
            ->where('quantity', '>', 0)
            ->orderBy($batchQueries->getExpiryDateSubOrderQuery('inventory_units.batch_id'), 'asc')
            ->where('serial_number_id', $serialNumberId)
            ->lockForUpdate()
            ->get();
    }

    public function decreaseStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->decrement('quantity', $quantity);
    }

    public function increaseReservedStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->decrement('quantity', $quantity);
        $inventoryUnit->increment('reserved_stock', $quantity);
    }

    public function decreaseReservedStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->decrement('reserved_stock', $quantity);
    }

    public function revertReservedStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->decrement('reserved_stock', $quantity);
        $inventoryUnit->increment('quantity', $quantity);
    }

    public function positiveQuantityRecordsOnly(): Closure
    {
        return fn ($query) => $query->select('id', 'inventory_id', 'batch_id', 'quantity')->where(
            'quantity',
            '>',
            0
        );
    }

    public function getBasicColumnNames(): string
    {
        return 'id,inventory_id,purchase_amount_id,batch_id,quantity,reserved_stock,serial_number_id';
    }

    public function getColumnForBatchExpiryReport(): string
    {
        return 'id,inventory_id,batch_id,quantity';
    }

    public function getByInventoryIdBatchIdAndPurchaseAmountId(
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId,
        ?int $serialNumberId = null,
    ): ?InventoryUnit {
        return InventoryUnit::where('inventory_id', $inventoryId)
            ->where('batch_id', $batchId)
            ->where('serial_number_id', $serialNumberId)
            ->where('purchase_amount_id', $purchaseAmountId)
            ->first();
    }

    public function increaseStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->increment('quantity', $quantity);
    }

    public function increaseOnlyReservedStock(InventoryUnit $inventoryUnit, float $quantity): void
    {
        $inventoryUnit->increment('reserved_stock', $quantity);
    }

    public function updateInventoryId(InventoryUnit $inventoryUnit, int $newInventoryId): void
    {
        $inventoryUnit->inventory_id = $newInventoryId;
        $inventoryUnit->save();
    }

    public function getInventoryUnitsByBatchAndUpc(
        array $batchNumbers,
        int $externalLocationId,
        string $upc
    ): Collection {
        return InventoryUnit::query()
            ->select('id', 'inventory_id', 'batch_id', 'quantity')
            ->whereHas('inventory', function ($query) use ($upc, $externalLocationId): void {
                $query->whereHas('product', function ($query) use ($upc): void {
                    $query->where('upc', $upc);
                })
                ->where('location_id', $externalLocationId);
            })
            ->whereHas('batch', function ($query) use ($batchNumbers): void {
                $query->whereIn('number', $batchNumbers);
            })
            ->get();
    }

    public function existsBySerialNumberIdAndInventoryId(int $serialNumberId): bool
    {
        return InventoryUnit::select('id')
            ->where('serial_number_id', $serialNumberId)
            ->where('quantity', -1)
            ->exists();
    }
}
