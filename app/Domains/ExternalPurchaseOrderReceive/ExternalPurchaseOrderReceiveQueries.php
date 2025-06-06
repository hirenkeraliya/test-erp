<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive;

use App\Domains\Color\ColorQueries;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\ExternalPurchaseOrderPartialReceiveItemQueries;
use App\Domains\ExternalPurchaseOrderPartialReceiveItemBatch\ExternalPurchaseOrderPartialReceiveItemBatchQueries;
use App\Domains\ExternalPurchaseOrderReceive\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\Size\SizeQueries;
use App\Models\ExternalPurchaseOrderPartialReceive;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderReceiveQueries
{
    public function listQuery(array $filterData, int $externalPurchaseOrderId): LengthAwarePaginator
    {
        return $this->getExternalPurchaseOrderReceives($filterData, $externalPurchaseOrderId)->paginate(
            $filterData['per_page']
        );
    }

    public function addNew(array $externalPurchaseOrderReceiveData): ExternalPurchaseOrderPartialReceive
    {
        return ExternalPurchaseOrderPartialReceive::create($externalPurchaseOrderReceiveData);
    }

    public function update(array $externalPurchaseOrderReceiveData, int $externalPurchaseOrderPartialReceive): void
    {
        $externalPurchaseOrderReceive = $this->getById($externalPurchaseOrderPartialReceive);
        $externalPurchaseOrderReceive->update($externalPurchaseOrderReceiveData);
    }

    private function getExternalPurchaseOrderReceives(array $filterData, int $externalPurchaseOrderId): Builder
    {
        return ExternalPurchaseOrderPartialReceive::query()
            ->select('id', 'external_purchase_order_id', 'status', 'notes', 'received_date', 'is_grn')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['received_date, notes'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where('external_purchase_order_id', $externalPurchaseOrderId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getByExternalPurchaseOrderId(int $externalPurchaseOrderId): Collection
    {
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );

        return ExternalPurchaseOrderPartialReceive::query()
            ->select('id', 'status')
            ->whereNot('status', Statuses::CANCELLED->value)
            ->where('external_purchase_order_id', $externalPurchaseOrderId)
            ->with('items:' . $externalPurchaseOrderPartialReceiveItemQueries->getBasicColumnNames())
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getById(int $externalPurchaseOrderPartialReceiveId): ExternalPurchaseOrderPartialReceive
    {
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );

        $externalPurchaseOrderPartialReceiveItemBatchQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemBatchQueries::class
        );

        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        return ExternalPurchaseOrderPartialReceive::query()
            ->select('id', 'external_purchase_order_id', 'notes', 'received_date')
            ->with([
                'items:' . $externalPurchaseOrderPartialReceiveItemQueries->getBasicColumnNames(),
                'items.itemBatches:' . $externalPurchaseOrderPartialReceiveItemBatchQueries->getBasicColumnNames(),
                'externalPurchaseOrder:' . $externalPurchaseOrderQueries->getBasicColumnNames(),
                'externalPurchaseOrder.purchasePlan:' . $purchasePlanQueries->getBasicColumnNames(),
                'items.externalPurchaseOrderItem:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
                'items.externalPurchaseOrderItem.product:' . $productQueries->getBasicColumnNames(),
                'items.externalPurchaseOrderItem.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.externalPurchaseOrderItem.product.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($externalPurchaseOrderPartialReceiveId);
    }

    public function updateIsGrn(ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive): void
    {
        $externalPurchaseOrderPartialReceive->is_grn = true;
        $externalPurchaseOrderPartialReceive->save();
    }

    public function addGoodsReceivedNoteId(int $externalPurchaseOrderPartialReceiveId, int $goodsReceivedNoteId): void
    {
        $externalPurchaseOrderPartialReceive = $this->getById($externalPurchaseOrderPartialReceiveId);
        $externalPurchaseOrderPartialReceive->goods_received_note_id = $goodsReceivedNoteId;
        $externalPurchaseOrderPartialReceive->save();
    }

    public function updateStatus(
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive,
        int $status
    ): void {
        $externalPurchaseOrderPartialReceive->status = $status;
        $externalPurchaseOrderPartialReceive->save();
    }

    public function getByIdWithRelationForEdit(int $externalPurchaseOrderId): ExternalPurchaseOrderPartialReceive
    {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderPartialReceiveItemBatchQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemBatchQueries::class
        );

        return ExternalPurchaseOrderPartialReceive::query()
        ->select('id', 'external_purchase_order_id', 'status', 'received_date', 'notes')
        ->with([
            'externalPurchaseOrder:' . $externalPurchaseOrderQueries->getBasicColumnNames(),
            'externalPurchaseOrder.items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
            'externalPurchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
            'externalPurchaseOrder.items.product.color:' . $colorQueries->getBasicColumnNames(),
            'externalPurchaseOrder.items.product.size:' . $sizeQueries->getBasicColumnNames(),
            'items:' . $externalPurchaseOrderPartialReceiveItemQueries->getBasicColumnNames(),
            'items.itemBatches:' . $externalPurchaseOrderPartialReceiveItemBatchQueries->getBasicColumnNames(),
        ])
        ->findOrFail($externalPurchaseOrderId);
    }
}
