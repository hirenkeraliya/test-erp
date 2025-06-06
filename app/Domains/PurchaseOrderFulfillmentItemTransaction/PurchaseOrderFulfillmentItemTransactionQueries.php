<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItemTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\PurchaseOrderFulfillmentItemTransaction;
use Illuminate\Foundation\Auth\User;

class PurchaseOrderFulfillmentItemTransactionQueries
{
    public function addNew(int $purchaseOrderFulfillmentItemId, ?string $remarks, int $status, User $user): void
    {
        PurchaseOrderFulfillmentItemTransaction::updateOrCreate([
            'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemId,
            'status' => $status,
            'remarks' => $remarks,
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
        ]);
    }

    public function getBasicColumns(): string
    {
        return 'id,purchase_order_fulfillment_item_id,status,remarks';
    }
}
