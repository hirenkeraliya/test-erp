<?php

declare(strict_types=1);

namespace App\Domains\StockTransferTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\StockTransferTransaction;
use Illuminate\Foundation\Auth\User;

class StockTransferTransactionQueries
{
    public function addNew(
        int $stockTransferId,
        int $oldStatus,
        int $newStatus,
        User $user,
        ?string $remarks = null
    ): void {
        StockTransferTransaction::create([
            'stock_transfer_id' => $stockTransferId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
            'remarks' => $remarks,
        ]);
    }

    public function getMorphUserColumns(): string
    {
        return 'id,stock_transfer_id,new_status,user_id,user_type';
    }

    public function getBasicColumns(): string
    {
        return 'id,stock_transfer_id,old_status,new_status,created_at,updated_at';
    }
}
