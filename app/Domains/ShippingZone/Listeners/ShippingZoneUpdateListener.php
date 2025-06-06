<?php

declare(strict_types=1);

namespace App\Domains\ShippingZone\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\Events\ShippingZoneUpdateEvent;
use App\Domains\ShippingZone\Services\SyncShippingZoneInEcommerceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShippingZoneUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(ShippingZoneUpdateEvent $shippingZoneUpdateEvent): void
    {
        $shippingZone = $shippingZoneUpdateEvent->shippingZone;

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::SHIPPING_ZONE_UPDATE->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook sync shipping zone create started', [
            'start time of the webhook call for the sync shipping zone create' => Carbon::now()->format('Y-m-d H:i:s'),
            'shipping zone id: ' . $shippingZone->getKey(),
        ]);

        try {
            $syncShippingZoneInEcommerceService = resolve(SyncShippingZoneInEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    $syncShippingZoneInEcommerceService->addUpdateDetails($shippingZone->id, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook sync shipping zone create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook sync shipping zone create ended', [
            'end time of the webhook call for the sync shipping zone create' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'shipping zone id: ' . $shippingZone->getKey(),
        ]);
    }
}
