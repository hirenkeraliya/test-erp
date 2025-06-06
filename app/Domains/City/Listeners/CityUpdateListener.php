<?php

declare(strict_types=1);

namespace App\Domains\City\Listeners;

use App\Domains\City\Events\CityUpdateEvent;
use App\Domains\City\Services\CityEcommerceService;
use App\Domains\City\Services\CityRetailPlanningIntegrationService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CityUpdateListener
{
    public function handle(CityUpdateEvent $cityUpdateEvent): void
    {
        $city = $cityUpdateEvent->city;

        /** @var CityRetailPlanningIntegrationService $cityRetailPlanningIntegrationService */
        $cityRetailPlanningIntegrationService = resolve(CityRetailPlanningIntegrationService::class);
        $cityRetailPlanningIntegrationService->manageCity($city, IntegrationWebhookUrls::CITY_UPDATES->value);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::CITY_UPDATES->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook city update started', [
            'start time of the webhook call for the city update' => Carbon::now()->format('Y-m-d H:i:s'),
            'city id: ' . $city->getKey(),
        ]);

        try {
            $cityEcommerceService = resolve(CityEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $cityEcommerceService->addUpdateDetails($city, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook city update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook city update ended', [
            'end time of the webhook call for the city update' => Carbon::now()->format('Y-m-d H:i:s'),
            'city id: ' . $city->getKey(),
        ]);
    }
}
