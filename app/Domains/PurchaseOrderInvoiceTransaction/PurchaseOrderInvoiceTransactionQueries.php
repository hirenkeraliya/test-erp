<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoiceTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\PurchaseOrderInvoiceTransaction;
use Illuminate\Foundation\Auth\User;

class PurchaseOrderInvoiceTransactionQueries
{
    public function addNew(int $purchaseOrderInvoiceId, ?int $oldStatus, int $newStatus, ?User $user): void
    {
        PurchaseOrderInvoiceTransaction::create([
            'purchase_order_invoice_id' => $purchaseOrderInvoiceId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'user_type' => $user instanceof User ? ModelMapping::getCaseName($user::class) : null,
        ]);
    }

    public function getNameColumnNames(): string
    {
        return 'id,purchase_order_invoice_id,old_status,new_status,user_id,user_type,created_at';
    }
}
