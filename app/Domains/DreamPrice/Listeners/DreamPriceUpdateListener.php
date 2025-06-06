<?php

namespace App\Domains\DreamPrice\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Events\DreamPriceUpdateEvent;
use App\Domains\DreamPrice\Resources\DreamPriceWebhookResource;
use App\Domains\DreamPrice\Services\DreamPriceEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(DreamPriceUpdateEvent $dreamPriceEvent): void
    {
        $dreamPrice = $dreamPriceEvent->dreamPrice;

        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrice = $dreamPriceQueries->getDreamPriceById($dreamPrice->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::DREAM_PRICE_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $dreamPrice->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook dream price update started', [
            'start time of the webhook call for the dream price update' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);

        $dreamPriceEcommerceService = resolve(DreamPriceEcommerceService::class);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $dreamPriceEcommerceService->addUpdateDreamPrice($dreamPrice, $saleChannel);
                    continue;
                }

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'dream_price' => new DreamPriceWebhookResource($dreamPrice),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook dream price update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook dream price update ended', [
            'end time of the webhook call for dream price update' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);
    }
}
