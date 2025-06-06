<?php

declare(strict_types=1);

namespace App\Domains\City\Services;

use App\Domains\CityChannelReference\CityChannelReferenceQueries;
use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Domains\StateChannelReference\StateChannelReferenceQueries;
use App\Models\City;
use App\Models\CityChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CityEcommerceService
{
    public function addUpdateDetails(City $city, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the city in eCommerce.', [
            'Start time for city creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'city id: ' . $city->getKey(),
        ]);

        $cityChannelReferenceQueries = resolve(CityChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $cityChannelReference = $cityChannelReferenceQueries->getByCityIdAndSaleChannelId(
                $city->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'city' => $this->preparedRecords($city, $cityChannelReference, $saleChannel->id),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: city in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('city_id', $responseData) && ! $cityChannelReference) {
                    $cityChannelReferenceQueries = resolve(CityChannelReferenceQueries::class);
                    $cityChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'city_id' => $city->id,
                        'external_city_id' => $responseData['city_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on city Type in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'city_id' => $city->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the city in eCommerce.', [
            'End time for payment tType creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'city id: ' . $city->getKey(),
        ]);
    }

    private function preparedRecords(City $city, ?CityChannelReference $cityChannelReference, int $saleChannelId): array
    {
        $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);
        $stateChannelReferenceQueries = resolve(StateChannelReferenceQueries::class);

        return [
            'existing_id' => $cityChannelReference?->external_city_id,
            'name' => $city->name,
            'country_id' => $countryChannelReferenceQueries->getByExternalCountryId(
                $city->country_id,
                $saleChannelId
            ) ?? $city->country_id,
            'state_id' => $stateChannelReferenceQueries->getByExternalStateId(
                $city->state_id,
                $saleChannelId
            ) ?? $city->state_id,
            'country_code' => $city->country_code,
        ];
    }
}
