<?php

declare(strict_types=1);

namespace App\Domains\Country\Services;

use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Models\Country;
use App\Models\CountryChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CountryEcommerceService
{
    public function addUpdateDetails(Country $country, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the country in eCommerce.', [
            'Start time for country creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'country id: ' . $country->getKey(),
        ]);

        $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $countryChannelReference = $countryChannelReferenceQueries->getByCountryIdAndSaleChannelId(
                $country->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'country' => $this->preparedRecords($country, $countryChannelReference),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: country in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('country_id', $responseData) && ! $countryChannelReference) {
                    $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);
                    $countryChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'country_id' => $country->id,
                        'external_country_id' => $responseData['country_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on country Type in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'country_id' => $country->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the country in eCommerce.', [
            'End time for payment tType creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'country id: ' . $country->getKey(),
        ]);
    }

    private function preparedRecords(Country $country, ?CountryChannelReference $countryChannelReference): array
    {
        return [
            'existing_id' => $countryChannelReference?->external_country_id,
            'name' => $country->name,
            'iso2' => $country->iso2,
            'iso3' => $country->iso3,
            'phone_code' => $country->phone_code,
            'region' => $country->region,
            'subregion' => $country->subregion,
        ];
    }
}
