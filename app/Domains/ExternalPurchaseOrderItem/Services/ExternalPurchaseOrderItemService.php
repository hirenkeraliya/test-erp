<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Services;

use App\Domains\Batch\BatchQueries;
use App\Domains\ExternalPurchaseOrder\DataObjects\ExternalPurchaseOrderData;
use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchasePlanItem\PurchasePlanItemQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderItem;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderItemService
{
    public function addShippingDetails(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        ExternalPurchaseOrder $externalPurchaseOrder,
    ): void {
        $purchasePlanItemIds = collect($externalPurchaseOrderData->transfer_items)->pluck(
            'purchase_plan_item_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        $purchasePlanItems = $purchasePlanItemQueries->getByIds($purchasePlanItemIds);

        $totalCharges = $this->totalCharges($externalPurchaseOrderData);
        $totalPrice = $this->totalPrice($externalPurchaseOrderData);

        foreach ($externalPurchaseOrderData->transfer_items as $externalPurchaseOrderItemData) {
            if (! array_key_exists('received_quantity', $externalPurchaseOrderItemData)) {
                continue;
            }

            if ($externalPurchaseOrderItemData['received_quantity'] <= 0) {
                continue;
            }

            $chargePerUnit = $this->chargePerUnit($externalPurchaseOrderItemData, $totalCharges, $totalPrice);

            $externalPurchaseOrderItemQueries->addNew([
                'external_purchase_order_id' => $externalPurchaseOrder->id,
                'purchase_plan_item_id' => $externalPurchaseOrderItemData['purchase_plan_item_id'],
                'product_id' => $externalPurchaseOrderItemData['product_id'],
                'quantity' => $externalPurchaseOrderItemData['received_quantity'],
                'cost_price' => $externalPurchaseOrderItemData['cost_price'],
                'charge_per_unit' => $chargePerUnit,
                'total_price' => $externalPurchaseOrderItemData['cost_price'] * $externalPurchaseOrderItemData['received_quantity'],
                'remarks' => $externalPurchaseOrderItemData['remarks'],
                'unit_of_measure_derivative_id' => $externalPurchaseOrderItemData['unit_of_measure_derivative_id'],
            ]);

            $purchasePlanItem = $purchasePlanItems->firstWhere(
                'id',
                $externalPurchaseOrderItemData['purchase_plan_item_id']
            );

            $purchasePlanItemQueries->updateTransferredQuantity(
                $purchasePlanItem,
                (float) $externalPurchaseOrderItemData['received_quantity']
            );
        }
    }

    public function updateShippingDetails(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        ExternalPurchaseOrder $externalPurchaseOrder,
    ): void {
        $externalPurchaseOrderItems = $externalPurchaseOrder->items;
        $purchasePlanItemIds = collect($externalPurchaseOrderData->transfer_items)->pluck(
            'purchase_plan_item_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        $purchasePlanItems = $purchasePlanItemQueries->getByIds($purchasePlanItemIds);

        $totalCharges = $this->totalCharges($externalPurchaseOrderData);
        $totalPrice = $this->totalPrice($externalPurchaseOrderData);

        foreach ($externalPurchaseOrderData->transfer_items as $externalPurchaseOrderItemData) {
            if (! array_key_exists('received_quantity', $externalPurchaseOrderItemData)) {
                continue;
            }

            if ($externalPurchaseOrderItemData['received_quantity'] <= 0) {
                continue;
            }

            $purchasePlanItem = $purchasePlanItems->firstWhere(
                'id',
                $externalPurchaseOrderItemData['purchase_plan_item_id']
            );

            /** @var ExternalPurchaseOrderItem $externalPurchaseOrderItem */
            $externalPurchaseOrderItem = $externalPurchaseOrderItems->firstWhere(
                'id',
                $externalPurchaseOrderItemData['id']
            );

            $chargePerUnit = $this->chargePerUnit($externalPurchaseOrderItemData, $totalCharges, $totalPrice);

            if (array_key_exists(
                'id',
                $externalPurchaseOrderItemData
            ) && $externalPurchaseOrderItemData['id'] > 0) {
                $purchasePlanItemQueries->updateTransferredQuantity(
                    $purchasePlanItem,
                    (float) $externalPurchaseOrderItemData['received_quantity'] - $externalPurchaseOrderItem->quantity
                );

                $externalPurchaseOrderItemQueries->update(
                    $externalPurchaseOrderItem,
                    [
                        'external_purchase_order_id' => $externalPurchaseOrder->id,
                        'purchase_plan_item_id' => $externalPurchaseOrderItemData['purchase_plan_item_id'],
                        'product_id' => $externalPurchaseOrderItemData['product_id'],
                        'quantity' => $externalPurchaseOrderItemData['received_quantity'],
                        'cost_price' => $externalPurchaseOrderItemData['cost_price'],
                        'charge_per_unit' => $chargePerUnit,
                        'total_price' => $externalPurchaseOrderItemData['cost_price'] * $externalPurchaseOrderItemData['received_quantity'],
                        'remarks' => $externalPurchaseOrderItemData['remarks'],
                        'unit_of_measure_derivative_id' => $externalPurchaseOrderItemData['unit_of_measure_derivative_id'],
                    ]
                );

                if ((float) $externalPurchaseOrderItemData['received_quantity'] === 0.0) {
                    $this->removeExternalPurchaseOrderItem($externalPurchaseOrderItem);
                    continue;
                }

                continue;
            }

            $externalPurchaseOrderItem = $externalPurchaseOrderItemQueries->addNew([
                'external_purchase_order_id' => $externalPurchaseOrder->id,
                'purchase_plan_item_id' => $externalPurchaseOrderItemData['purchase_plan_item_id'],
                'product_id' => $externalPurchaseOrderItemData['product_id'],
                'quantity' => $externalPurchaseOrderItemData['received_quantity'],
                'cost_price' => $externalPurchaseOrderItemData['cost_price'],
                'charge_per_unit' => $chargePerUnit,
                'total_price' => $externalPurchaseOrderItemData['cost_price'] * $externalPurchaseOrderItemData['received_quantity'],
                'remarks' => $externalPurchaseOrderItemData['remarks'],
            ]);

            $purchasePlanItemQueries->updateTransferredQuantity(
                $purchasePlanItem,
                (float) $externalPurchaseOrderItemData['received_quantity']
            );
        }
    }

    public function removeExternalPurchaseOrderItem(ExternalPurchaseOrderItem $externalPurchaseOrderItem): void
    {
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $externalPurchaseOrderItemQueries->removeItemAndRelations($externalPurchaseOrderItem);
    }

    public function prepareActiveBatchesProducts(array $productIds, int $companyId): array
    {
        $products = $this->fetchProducts($productIds, $companyId);

        $batches = $this->fetchBatches($products, $companyId);

        return [$products, $batches];
    }

    public function fetchProducts(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getActiveInventoryProductsByIds($productIds, $companyId);
    }

    public function fetchBatches(Collection $products, int $companyId): Collection
    {
        $batches = collect([]);

        $batchProductIds = $products->where('has_batch', true)->pluck('id')->unique()->filter()->toArray();

        if ([] !== $batchProductIds) {
            $batchQueries = resolve(BatchQueries::class);
            $batches = $batchQueries->getByProductIds($batchProductIds, $companyId);
        }

        return $batches;
    }

    public function checkAllItemsReceived(Collection $purchaseOrderItems, string $routeUrl): void
    {
        $purchaseOrderItems = $purchaseOrderItems->filter(
            fn ($purchaseOrderItem): bool => ($purchaseOrderItem->quantity - $purchaseOrderItem->transferred_quantity) > 0
        );

        if ($purchaseOrderItems->isNotEmpty()) {
            return;
        }

        throw new RedirectWithErrorException(
            $routeUrl,
            'All items that were to be added to the External Purchase Order have already been included.'
        );
    }

    public function totalCharges(ExternalPurchaseOrderData $externalPurchaseOrderData): float
    {
        return $externalPurchaseOrderData->fob + $externalPurchaseOrderData->freight_charges + $externalPurchaseOrderData->insurance_charges + $externalPurchaseOrderData->duty + $externalPurchaseOrderData->sst + $externalPurchaseOrderData->handling_charges + $externalPurchaseOrderData->other_charges;
    }

    public function totalPrice(ExternalPurchaseOrderData $externalPurchaseOrderData): float
    {
        $transferItems = collect($externalPurchaseOrderData->transfer_items);

        return $transferItems->sum(
            fn ($transferItem): int|float => $transferItem['received_quantity'] * $transferItem['cost_price']
        );
    }

    public function chargePerUnit(array $externalPurchaseOrderItemData, float $totalCharges, float $totalPrice): float
    {
        $receivedQuantity = $externalPurchaseOrderItemData['received_quantity'] ?? 0;
        $costPrice = $externalPurchaseOrderItemData['cost_price'] ??= 0;

        $itemPrice = $receivedQuantity * $costPrice;

        if ($itemPrice <= 0) {
            return 0;
        }

        return ($itemPrice / $totalPrice) * $totalCharges;
    }
}
