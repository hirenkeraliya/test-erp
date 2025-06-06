<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\LoyaltyPointUpdate\Events\LoyaltyPointUpdatesCreateEvent;
use App\Domains\LoyaltyPointUpdate\Services\SyncLoyaltyPointUpdateInEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoyaltyPointUpdatesCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(LoyaltyPointUpdatesCreateEvent $loyaltyPointUpdatesCreateEvent): void
    {
        $loyaltyPointUpdate = $loyaltyPointUpdatesCreateEvent->loyaltyPointUpdate;
        $loyaltyPointUpdate->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::LOYALTY_POINT_UPDATES_CREATE->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook sync loyalty point updates create started', [
            'start time of the webhook call for the sync loyalty point updates create' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'loyalty point updates id: ' . $loyaltyPointUpdate->getKey(),
        ]);

        try {
            $syncLoyaltyPointUpdateInEcommerceService = resolve(SyncLoyaltyPointUpdateInEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    $syncLoyaltyPointUpdateInEcommerceService->addUpdateDetails($loyaltyPointUpdate, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook sync loyalty point updates create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook sync loyalty point updates create ended', [
            'end time of the webhook call for the sync loyalty point updates create' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'loyalty point updates id: ' . $loyaltyPointUpdate->getKey(),
        ]);
    }
}
