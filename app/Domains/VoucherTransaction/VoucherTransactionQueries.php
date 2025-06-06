<?php

declare(strict_types=1);

namespace App\Domains\VoucherTransaction;

use App\Domains\Sale\SaleQueries;
use App\Models\VoucherTransaction;

class VoucherTransactionQueries
{
    public function addNew(
        int $voucherId,
        int $actionType,
        string $happenedAt,
        ?int $saleId,
        ?int $locationId,
        ?int $orderId = null,
    ): void {
        VoucherTransaction::create([
            'voucher_id' => $voucherId,
            'action_type_id' => $actionType,
            'sale_id' => $saleId,
            'order_id' => $orderId,
            'location_id' => $locationId,
            'happened_at' => $happenedAt,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,voucher_id,action_type_id,sale_id,location_id,created_at,happened_at,order_id';
    }

    public function getSaleByIdAndTypeFirst(int $voucherId, int $memberId, int $actionTypeId): ?VoucherTransaction
    {
        $saleQueries = resolve(SaleQueries::class);

        return VoucherTransaction::query()
            ->select('id', 'voucher_id', 'sale_id', 'action_type_id')
            ->with(['sale:' . $saleQueries->getBasicColumnNamesForUsedVoucher()])
            ->withWhereHas('voucher', function ($query) use ($memberId, $voucherId): void {
                $query->where('member_id', $memberId)
                    ->where('id', $voucherId);
            })
            ->where('voucher_id', $voucherId)
            ->where('action_type_id', $actionTypeId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getSaleByIdAndType(int $voucherId, int $memberId, int $actionTypeId): ?VoucherTransaction
    {
        $saleQueries = resolve(SaleQueries::class);

        return VoucherTransaction::query()
            ->select('id', 'voucher_id', 'sale_id', 'action_type_id')
            ->with(['sale:' . $saleQueries->getBasicColumnNamesForUsedVoucher()])
            ->withWhereHas('voucher', function ($query) use ($memberId, $voucherId): void {
                $query->where('member_id', $memberId)
                    ->where('id', $voucherId);
            })
            ->where('voucher_id', $voucherId)
            ->where('action_type_id', $actionTypeId)
            ->orderBy('id', 'desc')
            ->firstOrFail();
    }
}
