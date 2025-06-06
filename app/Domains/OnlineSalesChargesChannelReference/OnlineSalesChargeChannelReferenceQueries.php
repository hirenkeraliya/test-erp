<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesChargesChannelReference;

use App\Models\OnlineSalesChargeChannelReference;

class OnlineSalesChargeChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        OnlineSalesChargeChannelReference::create($record);
    }

    public function getByOnlineSalesChargeIdAndSaleChannelId(
        int $onlineSalesChargeId,
        int $saleChannelId
    ): ?OnlineSalesChargeChannelReference {
        return OnlineSalesChargeChannelReference::select(
            'id',
            'online_sales_charges_id',
            'external_online_sales_charges_id'
        )
            ->where('online_sales_charges_id', $onlineSalesChargeId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function deleteById(int $id, int $saleChannelId): void
    {
        $onlineSalesChargeChannelReference = OnlineSalesChargeChannelReference::select(
            'id',
            'online_sales_charges_id'
        )
        ->where('sale_channel_id', $saleChannelId)
        ->where('external_online_sales_charges_id', $id)
        ->first();

        if ($onlineSalesChargeChannelReference) {
            $onlineSalesChargeChannelReference->delete();
        }
    }

    public function getByOnlineSalesChargeId(int $onlineSalesChargeId): ?OnlineSalesChargeChannelReference
    {
        return OnlineSalesChargeChannelReference::query()
            ->select('id', 'external_online_sales_charges_id')
            ->where('online_sales_charges_id', $onlineSalesChargeId)
            ->first();
    }
}
