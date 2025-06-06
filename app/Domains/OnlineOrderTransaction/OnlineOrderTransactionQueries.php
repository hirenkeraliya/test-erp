<?php

declare(strict_types=1);

namespace App\Domains\OnlineOrderTransaction;

use App\Models\OnlineOrderTransaction;

class OnlineOrderTransactionQueries
{
    public function addNew(int $orderId, int $oldStatus, int $newStatus, ?array $response = []): void
    {
        OnlineOrderTransaction::create([
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'response' => json_encode($response),
        ]);
    }
}
