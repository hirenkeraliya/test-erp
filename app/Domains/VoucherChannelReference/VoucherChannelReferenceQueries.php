<?php

declare(strict_types=1);

namespace App\Domains\VoucherChannelReference;

use App\Models\VoucherChannelReference;

class VoucherChannelReferenceQueries
{
    public function addNew(array $voucherExternalIdRecords): VoucherChannelReference
    {
        return VoucherChannelReference::create($voucherExternalIdRecords);
    }

    public function getByVoucherIdAndSaleChannelId(int $voucherId, int $saleChannelId): ?VoucherChannelReference
    {
        return VoucherChannelReference::select('id', 'sale_channel_id', 'voucher_id', 'external_voucher_id')
            ->where('voucher_id', $voucherId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,voucher_id,external_voucher_id';
    }
}
