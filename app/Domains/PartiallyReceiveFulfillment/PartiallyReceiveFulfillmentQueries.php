<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillment;

use App\Domains\Employee\EmployeeQueries;
use App\Models\PartiallyReceiveFulfillment;
use Illuminate\Support\Collection;

class PartiallyReceiveFulfillmentQueries
{
    public function addNew(array $partiallyReceiveFulfillmentData): PartiallyReceiveFulfillment
    {
        return PartiallyReceiveFulfillment::create($partiallyReceiveFulfillmentData);
    }

    public function getPartiallyReceiveFulfillments(int $purchaseOrderFulfillmentId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return PartiallyReceiveFulfillment::query()
            ->select('id', 'purchase_order_fulfillment_id', 'received_by_user_id', 'received_by_user_type', 'status')
            ->with([
                'receivedByUser:id,employee_id',
                'receivedByUser.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
            ])
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->orderByDesc('id')
            ->get();
    }

    public function getBasicColumns(): string
    {
        return 'id,purchase_order_fulfillment_id,received_by_user_id,received_by_user_type,status,partially_receive_number';
    }

    public function getById(int $partiallyReceiveFulfillmentId): PartiallyReceiveFulfillment
    {
        return PartiallyReceiveFulfillment::query()
            ->select('id', 'status', 'purchase_order_fulfillment_id')
            ->findOrFail($partiallyReceiveFulfillmentId);
    }

    public function updateStatus(int $partiallyReceiveFulfillmentId, int $status): void
    {
        $partiallyReceiveFulfillment = $this->getById($partiallyReceiveFulfillmentId);
        $partiallyReceiveFulfillment->status = $status;
        $partiallyReceiveFulfillment->save();
    }

    public function updateStatusWithRecord(int $partiallyReceiveFulfillmentId, int $status): PartiallyReceiveFulfillment
    {
        $partiallyReceiveFulfillment = $this->getById($partiallyReceiveFulfillmentId);
        $partiallyReceiveFulfillment->status = $status;
        $partiallyReceiveFulfillment->save();

        return $partiallyReceiveFulfillment;
    }
}
