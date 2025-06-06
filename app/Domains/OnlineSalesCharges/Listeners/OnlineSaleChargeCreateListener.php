<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeCreateEvent;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\OnlineSalesCharges\Resources\OnlineSalesChargeWebhookResource;
use App\Domains\OnlineSalesCharges\Services\OnlineSalesChargeService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OnlineSaleChargeCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(OnlineSaleChargeCreateEvent $onlineSaleChargeCreateEvent): void
    {
        $onlineSalesCharge = $onlineSaleChargeCreateEvent->onlineSalesCharges;

        $onlineSaleChargeQueries = resolve(OnlineSalesChargesQueries::class);
        $onlineSalesCharge = $onlineSaleChargeQueries->getById($onlineSalesCharge->id, $onlineSalesCharge->company_id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::ONLINE_SALE_CHARGE_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $onlineSalesCharge->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook online sale charge create started', [
            'start time of the webhook call for the online sale charge create' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharge->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;
                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id && $onlineSaleChargeQueries->validateOnlineSalesChargeSaleChannelMatch(
                        $onlineSalesCharge,
                        $saleChannel
                    )) {
                        $onlineSalesChargeService = resolve(OnlineSalesChargeService::class);
                        $onlineSalesChargeService->addUpdateDetails($onlineSalesCharge, $saleChannel, $url);
                        continue;
                    }

                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'online_sale_charge' => new OnlineSalesChargeWebhookResource($onlineSalesCharge),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook online sale charge create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook online sale charge create ended', [
            'end time of the webhook call for the online sale charge create' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sale charge id: ' . $onlineSalesCharge->getKey(),
        ]);
    }
}
