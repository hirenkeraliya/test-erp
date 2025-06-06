<?php

declare(strict_types=1);

namespace App\Domains\SyncTransaction;

use App\Domains\SyncTransaction\Enums\SyncTransactionStatuses;
use App\Models\SyncTransaction;

class SyncTransactionQueries
{
    public function addNew(array $syncTransactionData): SyncTransaction
    {
        return SyncTransaction::create($syncTransactionData);
    }

    public function getBySaleChannelIdAndType(int $saleChannelId, int $typeId): ?SyncTransaction
    {
        return SyncTransaction::select('id')
            ->where('sale_channel_id', $saleChannelId)
            ->where('type_id', $typeId)
            ->first();
    }

    public function firstOrFailBySaleChannelAndType(int $saleChannelId, int $typeId): SyncTransaction
    {
        return SyncTransaction::select('id')
            ->where('sale_channel_id', $saleChannelId)
            ->where('type_id', $typeId)
            ->firstOrFail();
    }

    public function hasPendingSyncTransaction(int $typeId, ?int $companyId): bool
    {
        return SyncTransaction::select('id', 'sale_channel_id')
            ->where('type_id', $typeId)
            ->where('status', SyncTransactionStatuses::IN_PROGRESS->value)
            ->when($companyId, function ($query) use ($companyId): void {
                $query->whereHas('saleChannel', function ($query) use ($companyId): void {
                    $query->select('id')
                        ->where('company_id', $companyId);
                });
            })
            ->exists();
    }

    public function updateStatus(int $saleChannelId, int $typeId, int $status): void
    {
        $syncTransaction = $this->firstOrFailBySaleChannelAndType($saleChannelId, $typeId);

        $syncTransaction->status = $status;
        $syncTransaction->save();
    }
}
