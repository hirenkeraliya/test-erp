<?php

declare(strict_types=1);

namespace App\Domains\State\Services;

use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Domains\StateChannelReference\StateChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\State;
use App\Models\StateChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StateEcommerceService
{
    public function addUpdateDetails(State $state, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the state in eCommerce.', [
            'Start time for state creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'state id: ' . $state->getKey(),
        ]);

        $stateChannelReferenceQueries = resolve(StateChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $stateChannelReference = $stateChannelReferenceQueries->getByStateIdAndSaleChannelId(
                $state->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'state' => $this->preparedRecords($state, $stateChannelReference, $saleChannel->id),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: state in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('state_id', $responseData) && ! $stateChannelReference) {
                    $stateChannelReferenceQueries = resolve(StateChannelReferenceQueries::class);
                    $stateChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'state_id' => $state->id,
                        'external_state_id' => $responseData['state_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on state Type in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'state_id' => $state->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the state in eCommerce.', [
            'End time for payment tType creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'state id: ' . $state->getKey(),
        ]);
    }

    private function preparedRecords(
        State $state,
        ?StateChannelReference $stateChannelReference,
        int $saleChannelId
    ): array {
        $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);

        return [
            'existing_id' => $stateChannelReference?->external_state_id,
            'country_id' => $countryChannelReferenceQueries->getByExternalCountryId($state->country_id, $saleChannelId),
            'name' => $state->name,
            'country_code' => $state->country_code,
        ];
    }
}
