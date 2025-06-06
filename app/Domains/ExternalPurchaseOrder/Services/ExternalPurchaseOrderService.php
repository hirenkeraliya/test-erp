<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder\Services;

use App\Domains\ExternalPurchaseOrder\DataObjects\ExternalPurchaseOrderData;
use App\Domains\ExternalPurchaseOrder\Enums\Statuses;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\ExternalPurchaseOrder\Resource\ExternalPurchaseOrderListResource;
use App\Domains\ExternalPurchaseOrderItem\Services\ExternalPurchaseOrderItemService;
use App\Domains\ExternalPurchaseOrderReceive\Enums\Statuses as EnumsStatuses;
use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Domains\ExternalPurchaseOrderTransaction\ExternalPurchaseOrderTransactionQueries;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlan\Services\PurchasePlanService;
use App\Domains\PurchasePlanItem\PurchasePlanItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Admin;
use App\Models\ExternalPurchaseOrder;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\StoreManager;
use App\Models\WarehouseManager;

class ExternalPurchaseOrderService
{
    public function fetchExternalPurchaseOrders(array $filterData): array
    {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $lengthAwarePaginator = $externalPurchaseOrderQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ExternalPurchaseOrderListResource::collection($lengthAwarePaginator->getCollection()),
            'statusCounts' => $this->getExternalPurchaseOrderStatusCounts($filterData),
        ];
    }

    public function saveExternalPurchaseOrder(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        PurchasePlan $purchasePlan
    ): ExternalPurchaseOrder {
        $sequenceQueries = resolve(SequenceQueries::class);
        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);
        $totalCharges = $externalPurchaseOrderItemService->totalCharges($externalPurchaseOrderData);
        $totalPrice = $externalPurchaseOrderItemService->totalPrice($externalPurchaseOrderData);

        $sequence = $sequenceQueries->addNew($purchasePlan->location_id, SequenceTypes::EPO->value);

        $data = $externalPurchaseOrderData->all();

        unset($data['transfer_items']);
        $data['order_number'] = $sequence->getCompleteNumber();
        $data['total_amount'] = $totalCharges + $totalPrice;
        $data['date'] = now()->format('Y-m-d');
        $data['purchase_plan_id'] = $purchasePlan->id;
        $data['status'] = Statuses::PENDING->value;
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);

        return $externalPurchaseOrderQueries->addNew($data);
    }

    public function updateExternalPurchaseOrder(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        int $externalPurchaseOrderId
    ): void {
        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);
        $totalCharges = $externalPurchaseOrderItemService->totalCharges($externalPurchaseOrderData);
        $totalPrice = $externalPurchaseOrderItemService->totalPrice($externalPurchaseOrderData);

        $data = $externalPurchaseOrderData->all();

        unset($data['transfer_items']);
        $data['total_amount'] = $totalCharges + $totalPrice;
        $data['date'] = now()->format('Y-m-d');
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);

        $externalPurchaseOrderQueries->update($data, $externalPurchaseOrderId);
    }

    public function markAsCancel(
        ExternalPurchaseOrder $externalPurchaseOrder,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        $externalPurchaseOrderItems = $externalPurchaseOrder->getItems();

        foreach ($externalPurchaseOrderItems as $externalPurchaseOrderItem) {
            /** @var PurchasePlanItem $purchasePlanItem */
            $purchasePlanItem = $externalPurchaseOrderItem->purchasePlanItem;

            $purchasePlanItemQueries->decreaseTransferredQuantity(
                $purchasePlanItem,
                (float) $externalPurchaseOrderItem->quantity
            );
        }

        $externalPurchaseOrderTransactionQueries = resolve(ExternalPurchaseOrderTransactionQueries::class);
        $externalPurchaseOrderTransactionQueries->addNew(
            $externalPurchaseOrder->id,
            $externalPurchaseOrder->status,
            Statuses::CANCELLED->value,
            $user
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderQueries->updateStatus($externalPurchaseOrder, Statuses::CANCELLED->value);
    }

    public function markAsApprove(
        ExternalPurchaseOrder $externalPurchaseOrder,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $externalPurchaseOrderTransactionQueries = resolve(ExternalPurchaseOrderTransactionQueries::class);
        $externalPurchaseOrderTransactionQueries->addNew(
            $externalPurchaseOrder->id,
            $externalPurchaseOrder->status,
            Statuses::APPROVED->value,
            $user
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderQueries->updateStatus($externalPurchaseOrder, Statuses::APPROVED->value);
    }

    public function markAsPartial(
        ExternalPurchaseOrder $externalPurchaseOrder,
        Admin|StoreManager|WarehouseManager $user
    ): void {
        $externalPurchaseOrderTransactionQueries = resolve(ExternalPurchaseOrderTransactionQueries::class);
        $externalPurchaseOrderTransactionQueries->addNew(
            $externalPurchaseOrder->id,
            $externalPurchaseOrder->status,
            Statuses::PARTIAL->value,
            $user
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderQueries->updateStatus($externalPurchaseOrder, Statuses::PARTIAL->value);
    }

    public function hasPurchasePlanItems(PurchasePlan $purchasePlan): bool
    {
        return $purchasePlan->items->filter(
            fn ($purchasePlanItem): bool => ($purchasePlanItem->quantity - $purchasePlanItem->transferred_quantity) > 0
        )->isEmpty();
    }

    public function markAsCompletedExternalPurchaseOrder(
        ExternalPurchaseOrder $externalPurchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user,
        int $companyId
    ): void {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $externalPurchaseOrder = $externalPurchaseOrderQueries->loadRelations($externalPurchaseOrder);

        $purchaseOrderItems = $externalPurchaseOrder->items->filter(
            fn ($item): bool => $item->quantity > $item->received_quantity
        );

        if ($purchaseOrderItems->count() > 0) {
            return;
        }

        $externalPurchaseOrderReceive = $externalPurchaseOrderReceiveQueries->getByExternalPurchaseOrderId(
            $externalPurchaseOrder->id
        );

        $externalPurchaseOrderReceiveCount = $externalPurchaseOrderReceive->count();

        $externalPurchaseOrderReceiveCompleteStatusCount = $externalPurchaseOrderReceive->where(
            'status',
            EnumsStatuses::COMPLETED->value
        )->count();
        if ($externalPurchaseOrderReceiveCount === $externalPurchaseOrderReceiveCompleteStatusCount) {
            $this->markAsCompleted($externalPurchaseOrder, $user);

            $purchasePlanQueries = resolve(PurchasePlanQueries::class);
            $purchasePlanService = resolve(PurchasePlanService::class);
            $purchasePlan = $purchasePlanQueries->getById($externalPurchaseOrder->purchase_plan_id, $companyId);
            $purchasePlanService->markAsCompletedPurchasePlan($purchasePlan, $user);
        }
    }

    public function markAsCompleted(
        ExternalPurchaseOrder $externalPurchaseOrder,
        Admin|StoreManager|WarehouseManager|null $user
    ): void {
        $externalPurchaseOrderTransactionQueries = resolve(ExternalPurchaseOrderTransactionQueries::class);
        $externalPurchaseOrderTransactionQueries->addNew(
            $externalPurchaseOrder->id,
            $externalPurchaseOrder->status,
            Statuses::COMPLETED->value,
            $user
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderQueries->updateStatus($externalPurchaseOrder, Statuses::COMPLETED->value);
    }

    public function getExternalPurchaseOrderStatusCounts(array $filterData): array
    {
        $statusCounts = [];
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrdersStatusCounts = $externalPurchaseOrderQueries->getExternalPurchaseOrderStatusCount(
            $filterData
        );

        foreach (Statuses::getList() as $externalPurchaseOrderStatus) {
            $externalPurchaseOrdersStatusCount = $externalPurchaseOrdersStatusCounts->firstWhere(
                'status',
                (int) $externalPurchaseOrderStatus['id']
            );
            $statusName = Statuses::getFormattedCaseName($externalPurchaseOrderStatus['id']);
            $statusCounts[$statusName] = [
                'count' => (int) $externalPurchaseOrdersStatusCount?->count,
                'id' => $externalPurchaseOrderStatus['id'],
            ];
        }

        return $statusCounts;
    }
}
