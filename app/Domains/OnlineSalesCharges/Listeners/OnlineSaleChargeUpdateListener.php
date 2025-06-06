<?php

namespace App\Domains\OnlineSalesCharges\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeUpdateEvent;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\OnlineSalesCharges\Resources\OnlineSalesChargeWebhookResource;
use App\Domains\OnlineSalesCharges\Services\OnlineSalesChargeService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OnlineSaleChargeUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(OnlineSaleChargeUpdateEvent $onlineSaleChargeUpdateEvent): void
    {
        $onlineSalesCharges = $onlineSaleChargeUpdateEvent->onlineSalesCharges;

        $onlineSaleChargeQueries = resolve(OnlineSalesChargesQueries::class);
        $onlineSalesCharges = $onlineSaleChargeQueries->getById(
            $onlineSalesCharges->id,
            $onlineSalesCharges->company_id
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::ONLINE_SALE_CHARGE_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $onlineSalesCharges->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('online sale charge webhook online sale charge update started', [
            'start time of the webhook call for the online sale charge update' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharges->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    $onlineSalesChargeService = resolve(OnlineSalesChargeService::class);

                    $onlineSalesChargeSaleChannelMatch = $onlineSaleChargeQueries->validateOnlineSalesChargeSaleChannelMatch(
                        $onlineSalesCharges,
                        $saleChannel
                    );

                    if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id && $onlineSalesChargeSaleChannelMatch) {
                        $onlineSalesChargeService->addUpdateDetails($onlineSalesCharges, $saleChannel, $url);
                        continue;
                    }

                    if (! $onlineSalesChargeSaleChannelMatch) {
                        $onlineSalesChargeService->unAvailableOnlineSaleChargesInCommerce($onlineSalesCharges->id);
                    }

                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'online_sale_charge' => new OnlineSalesChargeWebhookResource($onlineSalesCharges),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook online sale charge update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook online sale charge update ended', [
            'end time of the webhook call for online sale charge update' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharges->getKey(),
        ]);
    }
}
