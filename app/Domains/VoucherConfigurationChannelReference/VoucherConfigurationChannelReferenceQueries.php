<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfigurationChannelReference;

use App\Models\VoucherConfigurationChannelReference;

class VoucherConfigurationChannelReferenceQueries
{
    public function addNew(array $voucherConfigurationRecords): VoucherConfigurationChannelReference
    {
        return VoucherConfigurationChannelReference::create($voucherConfigurationRecords);
    }

    public function getByVoucherConfigurationIdAndSaleChannelId(
        int $voucherConfigurationId,
        int $saleChannelId
    ): ?VoucherConfigurationChannelReference {
        return VoucherConfigurationChannelReference::select(
            'id',
            'sale_channel_id',
            'voucher_configuration_id',
            'external_voucher_configuration_id'
        )
            ->where('voucher_configuration_id', $voucherConfigurationId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByVoucherConfigurationId(
        int $voucherConfigurationId
    ): ?VoucherConfigurationChannelReference {
        return VoucherConfigurationChannelReference::select(
            'id',
            'sale_channel_id',
            'voucher_configuration_id',
            'external_voucher_configuration_id'
        )
            ->where('voucher_configuration_id', $voucherConfigurationId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,voucher_configuration_id,external_voucher_configuration_id';
    }

    public function getByExternalVoucherConfigurationIdAndSaleChannelId(
        int $voucherConfigurationId,
        int $saleChannelId
    ): ?VoucherConfigurationChannelReference {
        return VoucherConfigurationChannelReference::select(
            'id',
            'sale_channel_id',
            'voucher_configuration_id',
            'external_voucher_configuration_id'
        )
            ->where('external_voucher_configuration_id', $voucherConfigurationId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }
}
