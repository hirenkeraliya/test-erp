<?php

declare(strict_types=1);

namespace App\Domains\Country\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Country\Events\CountryCreateEvent;
use App\Domains\Country\Services\CountryEcommerceService;
use App\Domains\Country\Services\CountryRetailPlanningIntegrationService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CountryCreateListener
{
    public function handle(CountryCreateEvent $countryCreateEvent): void
    {
        $country = $countryCreateEvent->country;

        /** @var CountryRetailPlanningIntegrationService $countryRetailPlanningIntegrationService */
        $countryRetailPlanningIntegrationService = resolve(CountryRetailPlanningIntegrationService::class);
        $countryRetailPlanningIntegrationService->manageCountry(
            $country,
            IntegrationWebhookUrls::COUNTRY_CREATE->value
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::COUNTRY_CREATE->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook country create started', [
            'start time of the webhook call for the country create' => Carbon::now()->format('Y-m-d H:i:s'),
            'country id: ' . $country->getKey(),
        ]);

        try {
            $countryEcommerceService = resolve(CountryEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $countryEcommerceService->addUpdateDetails($country, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook country create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook country create ended', [
            'end time of the webhook call for the country create' => Carbon::now()->format('Y-m-d H:i:s'),
            'country id: ' . $country->getKey(),
        ]);
    }
}
