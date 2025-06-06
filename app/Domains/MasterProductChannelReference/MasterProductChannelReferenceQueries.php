<?php

declare(strict_types=1);

namespace App\Domains\MasterProductChannelReference;

use App\Models\MasterProductChannelReference;

class MasterProductChannelReferenceQueries
{
    public function addNew(array $masterProductExternalRecords): MasterProductChannelReference
    {
        return MasterProductChannelReference::create($masterProductExternalRecords);
    }

    public function getByMasterProductIdAndSaleChannelId(
        int $masterProductId,
        int $saleChannelId
    ): ?MasterProductChannelReference {
        return MasterProductChannelReference::select(
            'id',
            'sale_channel_id',
            'master_product_id',
            'external_master_product_id'
        )
            ->where('master_product_id', $masterProductId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,master_product_id,external_master_product_id';
    }
}
