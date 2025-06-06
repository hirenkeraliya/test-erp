<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Models\Admin;
use App\Models\ExternalPurchaseOrderTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;

class ExternalPurchaseOrderTransactionQueries
{
    public function addNew(
        int $externalPurchaseOrderId,
        ?int $oldStatus,
        int $newStatus,
        Admin|WarehouseManager|StoreManager|null $user,
    ): void {
        ExternalPurchaseOrderTransaction::create([
            'external_purchase_order_id' => $externalPurchaseOrderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'user_type' => $user ? ModelMapping::getCaseName($user::class) : null,
        ]);
    }

    public function getBasicColumns(): string
    {
        return 'id,external_purchase_order_id,old_status,new_status,user_id,user_type,created_at';
    }

    public function getByExternalPurchaseOrderIdAndNewStatus(
        int $externalPurchaseOrderId,
        int $status
    ): ?ExternalPurchaseOrderTransaction {
        $employeeQueries = resolve(EmployeeQueries::class);

        return ExternalPurchaseOrderTransaction::query()
                ->select('id', 'user_id', 'user_type')
                ->with(['user:id,employee_id', 'user.employee:' . $employeeQueries->getNameAndStaffIdColumns()])
                ->where('external_purchase_order_id', $externalPurchaseOrderId)
                ->where('new_status', $status)
                ->first();
    }
}
