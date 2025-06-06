<?php

namespace App\Domains\OnlineSalesCharges\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeDeleteEvent;
use App\Domains\OnlineSalesCharges\Resources\OnlineSalesChargeDeleteWebhookResource;
use App\Domains\OnlineSalesCharges\Services\OnlineSalesChargeService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OnlineSaleChargeDeleteListener
{
    /**
     * Handle the event.
     */
    public function handle(OnlineSaleChargeDeleteEvent $onlineSaleChargeDeleteEvent): void
    {
        $onlineSalesCharges = $onlineSaleChargeDeleteEvent->onlineSalesCharges;
        $onlineSalesCharges->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::ONLINE_SALE_CHARGE_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $onlineSalesCharges->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('online sale charge webhook online sale charge delete started', [
            'start time of the webhook call for the online sale charge delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharges->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $onlineSalesChargeService = resolve(OnlineSalesChargeService::class);
                    $onlineSalesChargeService->onlineSalesChargesDelete($onlineSalesCharges, $saleChannel);
                    continue;
                }

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'online_sales_charge' => new OnlineSalesChargeDeleteWebhookResource($onlineSalesCharges),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook online sale charge delete failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook online sales charge delete ended', [
            'end time of the webhook call for online sales charge delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharges->getKey(),
        ]);
    }
}
