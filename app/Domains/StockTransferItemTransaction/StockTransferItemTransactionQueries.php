<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItemTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\StockTransferItemTransaction;
use Illuminate\Foundation\Auth\User;

class StockTransferItemTransactionQueries
{
    public function addNew(int $stockTransferItemId, ?string $remarks, int $status, User $user): void
    {
        StockTransferItemTransaction::updateOrCreate([
            'stock_transfer_item_id' => $stockTransferItemId,
            'status' => $status,
            'remarks' => $remarks,
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
        ]);
    }

    public function getBasicColumns(): string
    {
        return 'id,stock_transfer_item_id,status,remarks';
    }

    public function getRemarksColumn(): string
    {
        return 'id,stock_transfer_item_id,remarks';
    }

    public function getColumns(): string
    {
        return 'id,stock_transfer_item_id,status,remarks,user_id,user_type,created_at';
    }
}
