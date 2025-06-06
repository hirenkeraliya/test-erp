<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Services;

use App\Domains\ExternalPurchaseOrder\Enums\Statuses as EnumsStatuses;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Domains\PurchasePlan\Exports\PurchasePlanExport;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlan\Resource\PurchasePlanListResource;
use App\Domains\PurchasePlanItem\Export\PurchasePlanItemExport;
use App\Domains\PurchasePlanItem\PurchasePlanItemQueries;
use App\Domains\PurchasePlanItem\Resource\PurchasePlanItemsResource;
use App\Domains\PurchasePlanTransaction\PurchasePlanTransactionQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Admin;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchasePlan;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchasePlanService
{
    /**
     * @return mixed[]
     */
    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoreWithBasicColumns($companyId);
        $warehouses = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return [$stores, $warehouses];
    }

    public function getProducts(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getBatchProductsByIds($productIds, $companyId);
    }

    public function savePurchasePlan(array $purchasePlanData, Collection $products, int $companyId): void
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        $purchasePlanItems = $purchasePlanData['transfer_items'];

        $sequenceQueries = resolve(SequenceQueries::class);

        $sequence = $sequenceQueries->addNew($purchasePlanData['location_id'], SequenceTypes::PP->value);

        $purchasePlanData['plan_number'] = $sequence->getCompleteNumber();

        unset($purchasePlanData['transfer_items']);

        $purchasePlanData['company_id'] = $companyId;

        $purchasePlan = $purchasePlanQueries->addNew($purchasePlanData);

        $totalAmount = 0.0;

        foreach ($purchasePlanItems as $purchasePlanItem) {
            /** @var Product $product */
            $product = $products->firstWhere('id', $purchasePlanItem['product_id']);

            $costPrice = $purchasePlanItem['is_product_purchase_cost'] ? (float) $product->purchase_cost : (float) $purchasePlanItem['purchase_cost'];

            $totalPrice = (float) $purchasePlanItem['quantity'] * $costPrice;

            if (
                array_key_exists('derivative', $purchasePlanItem)
                && $purchasePlanItem['derivative']
                && $purchasePlanItem['derivative']['ratio'] > 0
            ) {
                $costPrice = ((float) $purchasePlanItem['quantity'] / $purchasePlanItem['derivative']['ratio']);
                $derivativeQuantity = ((float) $purchasePlanItem['quantity'] / $purchasePlanItem['derivative']['ratio']);
                $totalPrice = (float) $purchasePlanItem['quantity'] * $derivativeQuantity;
            }

            $totalAmount += $totalPrice;

            $purchasePlanItemQueries->addNew([
                'purchase_plan_id' => $purchasePlan->id,
                'product_id' => $purchasePlanItem['product_id'],
                'quantity' => $purchasePlanItem['quantity'],
                'is_product_purchase_cost' => $purchasePlanItem['is_product_purchase_cost'],
                'unit_of_measure_derivative_id' => $purchasePlanItem['unit_of_measure_derivative_id'] ?? null,
                'cost_price' => $costPrice,
                'total_price' => $totalPrice,
            ]);
        }

        $purchasePlanQueries->updateTotalAmount($purchasePlan, $totalAmount);
    }

    public function getStocks(PurchasePlan $purchasePlan): Collection
    {
        $inventoryQueries = resolve(InventoryQueries::class);

        $purchasePlanItems = collect($purchasePlan->getItems());

        return $inventoryQueries->getInventoriesByProductIds(
            $purchasePlan->location_id,
            $purchasePlanItems->pluck('product_id')->toArray()
        );
    }

    public function fetchPurchasePlans(array $filterData, int $companyId): array
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $lengthAwarePaginator = $purchasePlanQueries->listQuery($filterData, $companyId);

        [$purchasePlanCounts] = $this->fetchStatusesCount($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PurchasePlanListResource::collection($lengthAwarePaginator->getCollection()),
            'purchasePlanStatusCounts' => $purchasePlanCounts,
        ];
    }

    public function fetchPurchasePlanItemByPurchasePlanId(int $purchasePlanId): array
    {
        $purchaseOrderItemQueries = resolve(PurchasePlanItemQueries::class);
        $purchasePlanItems = $purchaseOrderItemQueries->getByPurchasePlanId($purchasePlanId);

        return [
            'purchase_plan_items' => PurchasePlanItemsResource::collection($purchasePlanItems),
            'totals' => [
                'quantity' => $purchasePlanItems->sum('quantity'),
                'transferred_quantity' => $purchasePlanItems->sum('transferred_quantity'),
            ],
        ];
    }

    public function exportPurchasePlanItems(int $purchasePlanId, string $fileName): BinaryFileResponse
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $purchasePlanItems = $purchasePlanItemQueries->getByPurchasePlanId($purchasePlanId);

        return Excel::download(new PurchasePlanItemExport($purchasePlanItems), $fileName);
    }

    public function exportPurchasePlans(array $filterData, string $fileName, int $companyId): BinaryFileResponse
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlans = $purchasePlanQueries->exportPurchasePlan($filterData, $companyId);

        return Excel::download(new PurchasePlanExport($purchasePlans), $fileName);
    }

    public function getLocationStock(array $productIds, int $locationId): array
    {
        if (count($productIds) <= 0) {
            return [];
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getInventoriesWithProductByProductIds($locationId, $productIds);

        $productsWithoutInventories = [];

        if ($inventories->pluck('product_id')->isNotEmpty()) {
            $productsWithoutInventories = array_diff(
                $productIds,
                $inventories->pluck('product_id')->filter()->toArray()
            );
        }

        foreach ($productsWithoutInventories as $productWithoutInventory) {
            $inventories->push($inventoryQueries->fetchOrCreate($locationId, (int) $productWithoutInventory));
        }

        return $inventories->transform(function (Inventory $inventory): array {
            $product = $inventory->product;
            if (! $product) {
                $productQueries = resolve(ProductQueries::class);
                $product = $productQueries->getByIdWithUpc($inventory->product_id);
            }

            return [
                'product_id' => $inventory['product_id'],
                'stock' => $inventory['stock'],
                'reserved_stock' => $inventory['reserved_stock'],
            ];
        })->toArray();
    }

    public function purchasePlanMarkAsCanceled(
        PurchasePlan $purchasePlan,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        $purchasePlanTransactionQueries = resolve(PurchasePlanTransactionQueries::class);
        $purchasePlanTransactionQueries->addNew(
            $purchasePlan->id,
            $purchasePlan->status,
            Statuses::CANCELLED->value,
            $user
        );

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlanQueries->updateStatus($purchasePlan, Statuses::CANCELLED->value);
    }

    public function purchasePlanMarkAsApprove(
        PurchasePlan $purchasePlan,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        $purchasePlanTransactionQueries = resolve(PurchasePlanTransactionQueries::class);
        $purchasePlanTransactionQueries->addNew(
            $purchasePlan->id,
            $purchasePlan->status,
            Statuses::APPROVED->value,
            $user
        );

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlanQueries->updateStatus($purchasePlan, Statuses::APPROVED->value);
    }

    public function fetchStatusesCount(array $filterData, int $companyId): array
    {
        $purchasePlanCounts = [];
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        $purchasePlanStatusCounts = $purchasePlanQueries->allRequestStatusCount($filterData, $companyId);
        foreach (Statuses::getList() as $status) {
            $statusCount = $purchasePlanStatusCounts->firstWhere('status', $status['id']);
            $statusName = Statuses::getFormattedCaseName($status['id']);
            $purchasePlanCounts[$statusName] = [
                'count' => (int) $statusCount?->count,
                'id' => $status['id'],
            ];
        }

        return [$purchasePlanCounts];
    }

    public function markAsCompletedPurchasePlan(
        PurchasePlan $purchasePlan,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        $purchasePlanItems = $purchasePlan->items->filter(
            fn ($item): bool => $item->quantity > $item->transferred_quantity
        );

        if ($purchasePlanItems->count() > 0) {
            return;
        }

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrder = $externalPurchaseOrderQueries->getByPurchasePlanId($purchasePlan->id);
        $externalPurchaseOrderCount = $externalPurchaseOrder->count();
        $externalPurchaseOrderCompleteStatusCount = $externalPurchaseOrder->where(
            'status',
            EnumsStatuses::COMPLETED->value
        )->count();

        if ($externalPurchaseOrderCount === $externalPurchaseOrderCompleteStatusCount) {
            $this->purchasePlanMarkAsCompleted($purchasePlan, $user);
        }
    }

    public function purchasePlanMarkAsCompleted(
        PurchasePlan $purchasePlan,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        $purchasePlanTransactionQueries = resolve(PurchasePlanTransactionQueries::class);
        $purchasePlanTransactionQueries->addNew(
            $purchasePlan->id,
            $purchasePlan->status,
            Statuses::COMPLETED->value,
            $user
        );

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlanQueries->updateStatus($purchasePlan, Statuses::COMPLETED->value);
    }
}
