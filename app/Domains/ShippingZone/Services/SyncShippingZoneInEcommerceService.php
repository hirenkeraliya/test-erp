<?php

declare(strict_types=1);

namespace App\Domains\ShippingZone\Services;

use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Domains\ShippingZoneChannelReference\ShippingZoneChannelReferenceQueries;
use App\Domains\StateChannelReference\StateChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\ShippingZone;
use App\Models\ShippingZoneChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncShippingZoneInEcommerceService
{
    public function addUpdateDetails(int $shippingZoneId, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the shipping zone eCommerce.', [
            'Start time for master product creation or updating' => Carbon::now()->format('Y-m-d H:i:s'),
            'shipping zone id: ' . $shippingZoneId,
        ]);
        $shippingZoneQueries = resolve(ShippingZoneQueries::class);
        $shippingZone = $shippingZoneQueries->getRelationRecordByIdAndCompanyId(
            $shippingZoneId,
            $saleChannel->company_id
        );

        $shippingZoneChannelReferenceQueries = resolve(ShippingZoneChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $shippingZoneChannelReference = $shippingZoneChannelReferenceQueries->getByShippingZoneIdAndSaleChannelId(
                $shippingZone->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'shipping_zone' => $this->preparedRecords(
                    $shippingZone,
                    $shippingZoneChannelReference,
                    $saleChannel->id
                ),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: shipping zone synchronized in E-Commerce');

                if (array_key_exists('shipping_zone_id', $responseData) && ! $shippingZoneChannelReference) {
                    $shippingZoneChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'shipping_zone_id' => $shippingZone->id,
                        'external_shipping_zone_id' => $responseData['shipping_zone_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on shipping zone in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'shipping_zone_id' => $shippingZone->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }
    }

    public function preparedRecords(
        ShippingZone $shippingZone,
        ?ShippingZoneChannelReference $shippingZoneChannelReference,
        int $saleChannelId
    ): array {
        $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);
        $stateChannelReferenceQueries = resolve(StateChannelReferenceQueries::class);
        $externalStateIds = $stateChannelReferenceQueries->getByExternalStateIds(
            $shippingZone->states->pluck('id')->toArray(),
            $saleChannelId
        );

        return [
            'existing_id' => $shippingZoneChannelReference?->external_shipping_zone_id,
            'name' => $shippingZone->name,
            'country_id' => $countryChannelReferenceQueries->getByExternalCountryId(
                $shippingZone->country_id,
                $saleChannelId
            ) ?? $shippingZone->country_id,
            'state_ids' => empty($externalStateIds) ? $shippingZone->states->pluck('id')->toArray() : $externalStateIds,
        ];
    }
}
