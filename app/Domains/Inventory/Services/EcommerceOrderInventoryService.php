<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Member;
use App\Models\Model;
use App\Models\Order;
use App\Models\Product;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Log;

class EcommerceOrderInventoryService
{
    public function deductInventory(Order $order, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start deduct inventory in eCommerce.', [
            'Start time for deduct inventory' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);

        $inventoryQueries = resolve(InventoryQueries::class);
        $productQueries = resolve(ProductQueries::class);

        foreach ($order->orderItems as $orderItem) {
            $inventory = $inventoryQueries->fetchOrCreate($order->location_id, $orderItem->product_id);

            $product = $productQueries->getById($orderItem->product_id, $saleChannel->getCompanyId());

            if (! $product->has_batch) {
                Log::channel('e_commerce')->info('deduct inventory : call update inventory units.', [
                    'Start time for deduct inventory' => Carbon::now()->format('Y-m-d H:i:s'),
                    'order id: ' . $order->getKey(),
                    'product id: ' . $product->getKey(),
                ]);

                $this->updateInventoryUnits(
                    $inventory,
                    $product,
                    $order->location_id,
                    $orderItem,
                    $saleChannel,
                    (float) $orderItem->quantity,
                    Carbon::now()->format('Y-m-d H:i:s'),
                    null
                );
            }

            Log::channel('e_commerce')->info('deduct inventory : call decrease stock.', [
                'Start time for deduct inventory' => Carbon::now()->format('Y-m-d H:i:s'),
                'order id: ' . $order->getKey(),
                'product id: ' . $product->getKey(),
            ]);

            $inventoryQueries->decreaseStock($inventory, $orderItem->quantity);
        }

        Log::channel('e_commerce')->info('End deduct inventory in eCommerce.', [
            'End time for deduct inventory' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);
    }

    public function rollBackInventory(Order $order, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start roll back inventory in eCommerce.', [
            'Start time for roll back inventory' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);

        foreach ($order->getOrderItems() as $orderItem) {
            foreach ($orderItem->getOrderItemUnits() as $orderItemUnit) {
                $this->increaseInventory(
                    $order,
                    $saleChannel,
                    (float) $orderItemUnit->quantity,
                    $order->location_id,
                    $orderItem->product_id,
                    $orderItemUnit->purchase_amount_id,
                    $orderItemUnit->batch_id,
                );
            }
        }

        Log::channel('e_commerce')->info('End roll back inventory in eCommerce.', [
            'End time for roll back inventory' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);
    }

    public function checkAndRevertLoyaltyPoints(Order $order): void
    {
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoints = $loyaltyPointQueries->getLoyaltyPointForGivenOrder($order->id);

        if ($loyaltyPoints->isEmpty()) {
            return;
        }

        if (! $order->member) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $order->member,
            $loyaltyPoints->sum('points'),
            LoyaltyPointUpdateTypes::REVERT->value,
            $order->id,
            ModelMapping::ORDER->name,
            now()->format('Y-m-d H:i:s')
        );
    }

    public function revertUsedLoyaltyPoints(Order $order): void
    {
        if (! $order->member) {
            return;
        }

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdates = $loyaltyPointUpdateQueries->getUsedLoyaltyPoint(
            $order->id,
            ModelMapping::ORDER->name,
            LoyaltyPointUpdateTypes::USED->value
        );

        if ($loyaltyPointUpdates->isEmpty()) {
            return;
        }

        foreach ($loyaltyPointUpdates as $loyaltyPointUpdate) {
            $expiryDate = null;
            if ($loyaltyPointUpdate->loyaltyPoint) {
                $expiryDate = $loyaltyPointUpdate->loyaltyPoint->expiry_date;
            }

            /** @var Member $member */
            $member = $order->member;

            $revertLoyaltyPointService = resolve(RevertLoyaltyPointService::class);
            $revertLoyaltyPointService->increaseLoyaltyPoints(
                $member,
                $order,
                (int) abs($loyaltyPointUpdate->points),
                now()->format('Y-m-d H:i:s'),
                $expiryDate
            );
        }
    }

    public function checkAndRevertVouchersGenerated(int $orderId, int $locationId): void
    {
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

        $vouchers = $voucherQueries->getVouchersByOrderId($orderId);

        if ($vouchers->isEmpty()) {
            return;
        }

        foreach ($vouchers as $voucher) {
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CANCELLED->value,
                now()->format('Y-m-d H:i:s'),
                null,
                $locationId,
                $orderId
            );

            $voucherQueries->updateCancelledAt($voucher);
        }
    }

    public function checkAndRevertUsedVoucher(int $orderId, int $locationId): void
    {
        $orderDiscountQueries = resolve(OrderDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

        $voucherId = $orderDiscountQueries->getVoucherIdByOrder($orderId);

        if (null === $voucherId) {
            return;
        }

        $voucher = $voucherQueries->getById($voucherId);

        $voucherTransactionQueries->addNew(
            $voucher->id,
            VoucherTransactionActionTypes::RESET->value,
            now()->format('Y-m-d H:i:s'),
            null,
            $locationId,
            $orderId
        );

        $voucherQueries->resetUsedAt($voucher);
    }

    private function increaseInventory(
        Order $order,
        SaleChannel $saleChannel,
        float $quantity,
        int $locationId,
        int $productId,
        int $purchaseAmountId,
        ?int $batchId,
    ): void {
        Log::channel('e_commerce')->info(
            'Start increase inventory',
            [
                'Start time for increase inventory' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $productId,
            ]
        );

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $quantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit($quantity, $inventory->id, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $order,
            $saleChannel,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );

        Log::channel('e_commerce')->info(
            'End increase inventory',
            [
                'End time for increase inventory' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function updateInventoryUnits(
        Inventory $inventory,
        Product $product,
        int $locationId,
        Model $orderItem,
        User $user,
        float $quantity,
        string $happenedAt,
        ?int $batchId,
    ): void {
        Log::channel('e_commerce')->info(
            'Start update inventory units',
            [
                'Start time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                'order item id: ' . $orderItem->getKey(),
                'product id: ' . $product->getKey(),
            ]
        );

        $inventoryService = resolve(InventoryService::class);

        $totalQuantity = abs($quantity);
        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventory->id, $batchId);
        $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                Log::channel('e_commerce')->info(
                    'update inventory units : return when total quantity is equal or less than zero.',
                    [
                        'Start time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                        'order item id: ' . $orderItem->getKey(),
                        'product id: ' . $product->getKey(),
                    ]
                );

                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryStock -= $totalQuantity;

                Log::channel('e_commerce')->info(
                    'update inventory units : call Update Inventory Units For Order Item',
                    [
                        'Start time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                        'order item id: ' . $orderItem->getKey(),
                        'product id: ' . $product->getKey(),
                    ]
                );

                $this->updateInventoryUnitsForOrderItem(
                    $inventory,
                    $inventoryUnit,
                    $orderItem,
                    $user,
                    $product->id,
                    $locationId,
                    $totalQuantity,
                    $inventoryStock,
                    $happenedAt
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= (float) $inventoryUnit->quantity;

            Log::channel('e_commerce')->info(
                'update inventory units : call Update Inventory Units For Order Item when negative inventory',
                [
                    'Start time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                    'order item id: ' . $orderItem->getKey(),
                    'product id: ' . $product->getKey(),
                ]
            );

            $this->updateInventoryUnitsForOrderItem(
                $inventory,
                $inventoryUnit,
                $orderItem,
                $user,
                $product->id,
                $locationId,
                (float) $inventoryUnit->quantity,
                $inventoryStock,
                $happenedAt
            );
        }

        if ($totalQuantity > 0.0) {
            Log::channel('e_commerce')->info(
                'update inventory units : call Update Negative Inventory Units For Order Item',
                [
                    'Start time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                    'order item id: ' . $orderItem->getKey(),
                    'product id: ' . $product->getKey(),
                ]
            );

            $this->updateNegativeInventoryUnitsForOrderItem(
                $inventory,
                $orderItem,
                $user,
                $product->id,
                $locationId,
                $totalQuantity,
                (float) $inventoryStock,
                $happenedAt,
                $batchId
            );
        }

        Log::channel('e_commerce')->info(
            'End update inventory units',
            [
                'End time for update inventory unit' => Carbon::now()->format('Y-m-d H:i:s'),
                'order item id: ' . $orderItem->getKey(),
                'product id: ' . $product->getKey(),
            ]
        );
    }

    private function updateInventoryUnitsForOrderItem(
        Inventory $inventory,
        InventoryUnit $inventoryUnit,
        Model $orderItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt
    ): void {
        Log::channel('e_commerce')->info('Start Update Inventory Units For Order Item.', [
            'Start time for Update Inventory Units For Order Item' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $orderItem->getKey(),
        ]);

        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $orderItemUnitQueries->addNew($orderItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $orderItem,
            $user,
            $inventoryStock,
            $inventoryUnit->batch_id,
            $inventoryUnit->purchase_amount_id,
            $happenedAt
        );

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);

        Log::channel('e_commerce')->info('End Update Inventory Units For Order Item.', [
            'End time for Update Inventory Units For Order Item' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $orderItem->getKey(),
        ]);
    }

    private function updateNegativeInventoryUnitsForOrderItem(
        Inventory $inventory,
        Model $orderItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt,
        ?int $batchId,
    ): void {
        Log::channel('e_commerce')->info('Start Update Negative Inventory Units For Order Item.', [
            'Start time for Update Negative Inventory Units For Order Item' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $orderItem->getKey(),
        ]);

        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId($inventory->id, $purchaseAmountId, $batchId);

        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $orderItemUnitQueries->addNew($orderItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $orderItem,
            $user,
            $inventoryStock -= $quantity,
            $batchId,
            $purchaseAmountId,
            $happenedAt
        );

        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);

        Log::channel('e_commerce')->info('End Update Negative Inventory Units For Order Item.', [
            'End time for Update Negative Inventory Units For Order Item' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $orderItem->getKey(),
        ]);
    }
}
