<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\Listeners;

use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignUpdateEvent;
use App\Domains\LoyaltyCampaign\Services\LoyaltyCampaignSaleChannelService;

class LoyaltyCampaignUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(LoyaltyCampaignUpdateEvent $loyaltyCampaignUpdateEvent): void
    {
        $loyaltyCampaign = $loyaltyCampaignUpdateEvent->loyaltyCampaign;

        $loyaltyCampaignSaleChannelService = resolve(LoyaltyCampaignSaleChannelService::class);
        $loyaltyCampaignSaleChannelService->updateLoyaltyCampaign($loyaltyCampaign);
    }
}
