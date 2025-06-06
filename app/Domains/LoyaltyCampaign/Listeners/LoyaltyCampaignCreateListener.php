<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\Listeners;

use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignCreateEvent;
use App\Domains\LoyaltyCampaign\Services\LoyaltyCampaignSaleChannelService;

class LoyaltyCampaignCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(LoyaltyCampaignCreateEvent $loyaltyCampaignCreateEvent): void
    {
        $loyaltyCampaign = $loyaltyCampaignCreateEvent->loyaltyCampaign;

        $loyaltyCampaignSaleChannelService = resolve(LoyaltyCampaignSaleChannelService::class);
        $loyaltyCampaignSaleChannelService->createLoyaltyCampaign($loyaltyCampaign);
    }
}
