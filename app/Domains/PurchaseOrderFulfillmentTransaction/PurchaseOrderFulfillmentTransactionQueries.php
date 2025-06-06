<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Models\Admin;
use App\Models\PurchaseOrderFulfillmentTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Foundation\Auth\User;

class PurchaseOrderFulfillmentTransactionQueries
{
    public function addNew(
        int $purchaseOrderFulfillmentId,
        ?int $oldStatus,
        int $newStatus,
        Admin|WarehouseManager|StoreManager|null $user,
        ?string $externalUsername = null
    ): void {
        PurchaseOrderFulfillmentTransaction::create([
            'purchase_order_fulfillment_id' => $purchaseOrderFulfillmentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'user_type' => $user instanceof User ? ModelMapping::getCaseName($user::class) : null,
            'external_username' => $externalUsername,
        ]);
    }

    public function getByPurchaseOrderFulfillmentId(
        int $purchaseOrderFulfillmentId,
        int $newStatue
    ): PurchaseOrderFulfillmentTransaction {
        return PurchaseOrderFulfillmentTransaction::query()
           ->select('id', 'user_id', 'user_type')
           ->with('user:id')
           ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
           ->where('new_status', $newStatue)
           ->firstOrFail();
    }

    public function getByPurchaseOrderFulfillmentIdAndNewStatus(
        int $purchaseOrderFulfillmentId,
        int $status
    ): ?PurchaseOrderFulfillmentTransaction {
        $employeeQueries = resolve(EmployeeQueries::class);

        return PurchaseOrderFulfillmentTransaction::query()
                ->select('id', 'user_id', 'user_type', 'external_username')
                ->with(['user:id,employee_id', 'user.employee:' . $employeeQueries->getNameAndStaffIdColumns()])
                ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
                ->where('new_status', $status)
                ->first();
    }

    public function getByPurchaseOrderFulfillmentIdAndNewStatuses(
        int $purchaseOrderFulfillmentId,
        array $statuses
    ): PurchaseOrderFulfillmentTransaction {
        $employeeQueries = resolve(EmployeeQueries::class);

        return PurchaseOrderFulfillmentTransaction::query()
                ->select('id', 'user_id', 'user_type', 'external_username')
                ->with(['user:id,employee_id', 'user.employee:' . $employeeQueries->getNameAndStaffIdColumns()])
                ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
                ->whereIn('new_status', $statuses)
                ->firstOrFail();
    }

    public function getNameColumnNames(): string
    {
        return 'id,purchase_order_fulfillment_id,old_status,new_status,user_id,user_type,created_at';
    }
}
