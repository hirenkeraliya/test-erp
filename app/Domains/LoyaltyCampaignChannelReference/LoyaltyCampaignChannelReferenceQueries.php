<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignChannelReference;

use App\Models\LoyaltyCampaignChannelReference;

class LoyaltyCampaignChannelReferenceQueries
{
    public function addNew(array $loyaltyCampaignExternalIdRecords): LoyaltyCampaignChannelReference
    {
        return LoyaltyCampaignChannelReference::create($loyaltyCampaignExternalIdRecords);
    }

    public function getByLoyaltyCampaignIdAndSaleChannelId(
        int $loyaltyCampaignId,
        int $saleChannelId
    ): ?LoyaltyCampaignChannelReference {
        return LoyaltyCampaignChannelReference::select(
            'id',
            'sale_channel_id',
            'loyalty_campaign_id',
            'external_loyalty_campaign_id'
        )
            ->where('loyalty_campaign_id', $loyaltyCampaignId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,loyalty_campaign_id,external_loyalty_campaign_id';
    }
}
