<?php

namespace App\Domains\DreamPrice\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Events\DreamPriceCreateEvent;
use App\Domains\DreamPrice\Services\DreamPriceEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(DreamPriceCreateEvent $dreamPriceCreateEvent): void
    {
        $dreamPrice = $dreamPriceCreateEvent->dreamPrice;

        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrice = $dreamPriceQueries->getDreamPriceById($dreamPrice->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::DREAM_PRICE_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $dreamPrice->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook dream price create started', [
            'start time of the webhook call for the dream price create' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);

        try {
            $dreamPriceEcommerceService = resolve(DreamPriceEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $dreamPriceEcommerceService->addUpdateDreamPrice($dreamPrice, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook dream price create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook dream price create ended', [
            'end time of the webhook call for the dream price create' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);
    }
}
