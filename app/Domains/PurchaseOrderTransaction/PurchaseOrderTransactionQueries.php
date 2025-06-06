<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Models\Admin;
use App\Models\PurchaseOrderTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;

class PurchaseOrderTransactionQueries
{
    public function addNew(
        int $purchaseOrderId,
        ?int $oldStatus,
        int $newStatus,
        Admin|WarehouseManager|StoreManager|null $user,
        ?string $externalUsername = null
    ): void {
        PurchaseOrderTransaction::create([
            'purchase_order_id' => $purchaseOrderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'user_type' => $user ? ModelMapping::getCaseName($user::class) : null,
            'external_username' => $externalUsername,
        ]);
    }

    public function getByPurchaseOrderIdAndNewStatus(int $purchaseOrderId, int $status): ?PurchaseOrderTransaction
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return PurchaseOrderTransaction::query()
                ->select('id', 'user_id', 'user_type', 'external_username')
                ->with(['user:id,employee_id', 'user.employee:' . $employeeQueries->getNameAndStaffIdColumns()])
                ->where('purchase_order_id', $purchaseOrderId)
                ->where('new_status', $status)
                ->first();
    }

    public function getBasicColumns(): string
    {
        return 'id,purchase_order_id,old_status,new_status,user_id,user_type,created_at';
    }
}
