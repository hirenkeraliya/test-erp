<?php

namespace App\Domains\LoyaltyPoint\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\LoyaltyPoint\Events\LoyaltyPointUpdateEvent;
use App\Domains\LoyaltyPoint\Services\SyncLoyaltyPointInEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoyaltyPointUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(LoyaltyPointUpdateEvent $loyaltyPointUpdateEvent): void
    {
        $loyaltyPoint = $loyaltyPointUpdateEvent->loyaltyPoint;

        if (! $loyaltyPoint->order_id) {
            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::LOYALTY_POINT_UPDATE->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook sync loyalty point update started', [
            'start time of the webhook call for the sync loyalty point update' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty point id: ' . $loyaltyPoint->getKey(),
        ]);

        try {
            $syncLoyaltyPointInEcommerceService = resolve(SyncLoyaltyPointInEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    $syncLoyaltyPointInEcommerceService->addUpdateDetails($loyaltyPoint, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook sync loyalty point update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook sync loyalty point update ended', [
            'end time of the webhook call for sync loyalty point update' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty point id: ' . $loyaltyPoint->getKey(),
        ]);
    }
}
