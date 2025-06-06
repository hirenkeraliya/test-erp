<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\GoodsReceivedNoteProduct;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;

class GoodsReceivedNoteInventoryService
{
    public function addInventory(
        GoodsReceivedNoteProduct $goodsReceivedNoteProduct,
        User $user,
        int $locationId,
        int $productId,
        ?int $batchId,
        int $purchaseAmountId,
        ?int $serialNumberId = null,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);

        $goodsReceivedNoteProductQuantity = (float) $goodsReceivedNoteProduct->quantity;

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $goodsReceivedNoteProductQuantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit(
            $goodsReceivedNoteProductQuantity,
            $inventory->id,
            $purchaseAmountId,
            $batchId,
            $serialNumberId,
        );

        $inventoryUpdateQueries->addNew(
            $productId,
            $goodsReceivedNoteProductQuantity,
            $locationId,
            $goodsReceivedNoteProduct,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
            null,
            null,
            $serialNumberId,
        );
    }

    public function addInventoryForExternalPurchaseOrder(
        GoodsReceivedNoteProduct $goodsReceivedNoteProduct,
        int $locationId,
        int $productId,
        ?int $batchId,
        int $purchaseAmountId,
        int $userId,
        string $userType,
        string $happenedAt,
        int $itemId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryService = resolve(InventoryService::class);

        /** @var Carbon $receivedDate */
        $receivedDate = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $inventoryUpdates = $inventoryUpdateQueries->getRecordsAfterDateByLocationAndProduct(
            $receivedDate->format('Y-m-d'),
            $locationId,
            $productId
        );

        $goodsReceivedNoteProductQuantity = (float) $goodsReceivedNoteProduct->quantity;

        if ($inventoryUpdates->isNotEmpty()) {
            $latestInventoryUpdate = $inventoryUpdateQueries->getLatestClosingStockBy(
                $receivedDate->format('Y-m-d'),
                $locationId,
                $productId
            );

            $latesInventoryUpdateClosingStock = 0;

            if ($latestInventoryUpdate) {
                $latesInventoryUpdateClosingStock = (float) $latestInventoryUpdate->closing_stock;
            }

            $inventoryStock = $latesInventoryUpdateClosingStock + $goodsReceivedNoteProductQuantity;

            $inventoryUpdateQueries->addNewForExternalPurchaseOrder(
                $productId,
                $goodsReceivedNoteProductQuantity,
                $locationId,
                $goodsReceivedNoteProduct,
                $userId,
                $userType,
                $inventoryStock,
                $batchId,
                $purchaseAmountId,
                CommonFunctions::addEndTime($receivedDate->format('Y-m-d')),
                null,
                null,
            );

            $closingStock = $inventoryStock;

            foreach ($inventoryUpdates as $inventoryUpdate) {
                $closingStock = $inventoryUpdateQueries->updateClosingStockOfPreviousRecordForPurchasePlan(
                    $inventoryUpdate,
                    (float) $closingStock,
                    $itemId,
                );
            }

            $inventoryId = $inventoryQueries->updateStockBy($locationId, $productId, (float) $closingStock);

            $inventoryService->updateInventoryUnit(
                $goodsReceivedNoteProductQuantity,
                $inventoryId,
                $purchaseAmountId,
                $batchId,
            );

            return;
        }

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $goodsReceivedNoteProductQuantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService->updateInventoryUnit(
            $goodsReceivedNoteProductQuantity,
            $inventory->id,
            $purchaseAmountId,
            $batchId,
            null,
        );

        $inventoryUpdateQueries->addNewForExternalPurchaseOrder(
            $productId,
            $goodsReceivedNoteProductQuantity,
            $locationId,
            $goodsReceivedNoteProduct,
            $userId,
            $userType,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
            CommonFunctions::addEndTime($receivedDate->format('Y-m-d')),
            null,
            null,
        );
    }

    public function rollbackInventoryForGRNCancellation(
        GoodsReceivedNoteProduct $goodsReceivedNoteProduct,
        User $user,
        int $locationId,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventory = $inventoryQueries->getInventoryBy($locationId, $goodsReceivedNoteProduct->product_id);

        $goodsReceivedNoteProductQuantity = (float) $goodsReceivedNoteProduct->quantity;

        $inventoryStock = $inventoryQueries->decreaseStock($inventory, $goodsReceivedNoteProductQuantity);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit(
            (float) ('-' . $goodsReceivedNoteProductQuantity),
            $inventory->id,
            $goodsReceivedNoteProduct->purchase_amount_id,
            $goodsReceivedNoteProduct->batch_id
        );

        $notes = 'cancelled by ' . ModelMapping::getCaseName($user::class) . ' and user id is: ' . $user->id;

        $inventoryUpdateQueries->addNew(
            $goodsReceivedNoteProduct->product_id,
            (float) ('-' . $goodsReceivedNoteProductQuantity),
            $locationId,
            $goodsReceivedNoteProduct,
            $user,
            $inventoryStock,
            $goodsReceivedNoteProduct->batch_id,
            $goodsReceivedNoteProduct->purchase_amount_id,
            now()->format('Y-m-d H:i:s'),
            $notes,
        );
    }
}
