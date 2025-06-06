<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Models\Admin;
use App\Models\PurchasePlanTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;

class PurchasePlanTransactionQueries
{
    public function addNew(
        int $purchasePlanId,
        ?int $oldStatus,
        int $newStatus,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        PurchasePlanTransaction::create([
            'purchase_plan_id' => $purchasePlanId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'user_type' => $user ? ModelMapping::getCaseName($user::class) : null,
        ]);
    }

    public function getBasicColumns(): string
    {
        return 'id,purchase_plan_id,old_status,new_status,user_id,user_type,created_at';
    }

    public function getByPurchasePlanIdAndNewStatus(int $purchasePlanId, int $status): ?PurchasePlanTransaction
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return PurchasePlanTransaction::query()
                ->select('id', 'user_id', 'user_type')
                ->with(['user:id,employee_id', 'user.employee:' . $employeeQueries->getNameAndStaffIdColumns()])
                ->where('purchase_plan_id', $purchasePlanId)
                ->where('new_status', $status)
                ->first();
    }
}
