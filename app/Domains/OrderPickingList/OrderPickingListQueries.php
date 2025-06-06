<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingList;

use App\Domains\Company\CompanyQueries;
use App\Domains\Order\Enums\OrderPickingStatus;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\OrderPickingList;
use App\Models\OrderPickingListItem;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderPickingListQueries
{
    public function addNew(array $orderIds, int $companyId): void
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $orderPickingListPrefix = $companyQueries->getOrderPickingListPrefix($companyId);

        $sequence = $sequenceQueries->addNew(null, SequenceTypes::OP->value);
        $number = $orderPickingListPrefix . $sequence->getNumber();
        $orderPickingList = $this->createOrderPickingList($companyId, $number);

        foreach ($orderIds as $orderId) {
            $this->createOrderPickingListItem($orderPickingList->id, $orderId);
        }
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return OrderPickingList::query()
           ->select('id', 'company_id', 'number', 'status')
           ->where('company_id', $companyId)
           ->when($filterData['search_text'], function ($query) use ($filterData): void {
               $query->where(function ($query) use ($filterData): void {
                   $query->where('number', 'like', '%' . $filterData['search_text'] . '%');
               });
           })
           ->when($filterData['sort_by'], function ($query) use ($filterData): void {
               $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
           }, function ($query): void {
               $query->orderBy('id', 'desc');
           })
            ->paginate($filterData['per_page']);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,number';
    }

    public function getById(int $orderPickingListId): OrderPickingList
    {
        return OrderPickingList::select('id', 'number', 'status')
            ->findOrFail($orderPickingListId);
    }

    private function createOrderPickingList(int $companyId, string $number): OrderPickingList
    {
        return OrderPickingList::create([
            'company_id' => $companyId,
            'number' => $number,
            'status' => OrderPickingStatus::DRAFT->value,
        ]);
    }

    private function createOrderPickingListItem(int $orderPickingListId, int $orderId): void
    {
        OrderPickingListItem::create([
            'order_picking_list_id' => $orderPickingListId,
            'order_id' => $orderId,
        ]);
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('company_id', $companyId);
    }

    public function inprogress(int $orderPickingListId): void
    {
        $orderPickingList = $this->getById($orderPickingListId);
        $orderPickingList->status = OrderPickingStatus::IN_PROGRESS->value;
        $orderPickingList->save();
    }

    public function cancel(int $orderPickingListId): void
    {
        $orderPickingList = $this->getById($orderPickingListId);
        $orderPickingList->status = OrderPickingStatus::CANCELLED->value;
        $orderPickingList->save();
    }

    public function completed(int $orderPickingListId): void
    {
        $orderPickingList = $this->getById($orderPickingListId);
        $orderPickingList->status = OrderPickingStatus::COMPLETED->value;
        $orderPickingList->save();
    }
}
