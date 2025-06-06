<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\OnlineSalesChargesChannelReference\OnlineSalesChargeChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\Services\SyncShippingZoneInEcommerceService;
use App\Domains\ShippingZoneChannelReference\ShippingZoneChannelReferenceQueries;
use App\Models\OnlineSalesChargeChannelReference;
use App\Models\OnlineSalesCharges;
use App\Models\SaleChannel;
use App\Models\ShippingZoneChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnlineSalesChargeService
{
    public function addUpdateDetails(OnlineSalesCharges $onlineSalesCharge, SaleChannel $saleChannel, string $url): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the online sales charge in eCommerce.', [
            'Start time for $onlineSalesCharge creation or update' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sales charge id: ' . $onlineSalesCharge->getKey(),
        ]);

        $onlineSalesChargeChannelReferenceQueries = resolve(OnlineSalesChargeChannelReferenceQueries::class);

        $onlineSalesChargeChannelReference = $onlineSalesChargeChannelReferenceQueries->getByOnlineSalesChargeIdAndSaleChannelId(
            $onlineSalesCharge->id,
            $saleChannel->id
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($url, [
            'online_sales_charge' => $this->preparedRecords(
                $onlineSalesCharge,
                $onlineSalesChargeChannelReference,
                $saleChannel
            ),
        ]);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('e_commerce')->info('Response: Online sales charges in E-Commerce', [
                'response' => $responseData,
            ]);

            if (array_key_exists('online_sales_charge_id', $responseData) && ! $onlineSalesChargeChannelReference) {
                $onlineSalesChargeChannelReferenceQueries = resolve(
                    OnlineSalesChargeChannelReferenceQueries::class
                );
                $onlineSalesChargeChannelReferenceQueries->addNew([
                    'sale_channel_id' => $saleChannel->id,
                    'online_sales_charges_id' => $onlineSalesCharge->id,
                    'external_online_sales_charges_id' => $responseData['online_sales_charge_id'],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('Response: Error on online sales charges in E-Commerce', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_data' => [
                    'online_sales_charges_id' => $onlineSalesCharge->getKey(),
                    'saleChannel_id' => $saleChannel->getKey(),
                ],
            ]);
        }

        Log::channel('e_commerce')->info('End creating or updating the online sales charges in eCommerce.', [
            'End time for online sales charges creation or update' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sales charges id: ' . $onlineSalesCharge->getKey(),
        ]);
    }

    public function onlineSalesChargesDelete(OnlineSalesCharges $onlineSalesCharge, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start delete the online sales charge in eCommerce.', [
            'Start time for online sales charge delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sales charge id: ' . $onlineSalesCharge->getKey(),
        ]);

        $onlineSalesChargeChannelReferenceQueries = resolve(OnlineSalesChargeChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $onlineSalesChargeChannelReference = $onlineSalesChargeChannelReferenceQueries->getByOnlineSalesChargeIdAndSaleChannelId(
                    $onlineSalesCharge->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'external_online_sales_charges_id' => $onlineSalesChargeChannelReference?->external_online_sales_charges_id,
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: online sales charge in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists(
                        'online_sales_charge_id',
                        $responseData
                    ) && $onlineSalesChargeChannelReference) {
                        $onlineSalesChargeChannelReferenceQueries = resolve(
                            OnlineSalesChargeChannelReferenceQueries::class
                        );
                        $onlineSalesChargeChannelReferenceQueries->deleteById(
                            $onlineSalesChargeChannelReference->id,
                            $saleChannel->id
                        );
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on online sales charge in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'online_sales_charge_id' => $onlineSalesCharge->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        }

        Log::channel('e_commerce')->info('End delete the online sales charge in eCommerce.', [
            'End time for online sales charge delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'online sales charge id: ' . $onlineSalesCharge->getKey(),
        ]);
    }

    public function unAvailableOnlineSaleChargesInCommerce(int $onlineSaleChargeId): void
    {
        $onlineSaleCharge = $this->fetchOnlineSaleChargeIdRecords($onlineSaleChargeId);

        $onlineSaleChargeChannelReference = $this->fetchOnlineSaleChargeChannelReference($onlineSaleCharge->getKey());

        $webhookUrls = [WebhookUrls::ONLINE_SALES_CHARGES_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $onlineSaleCharge->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('online_sale_charge_channel_reference')->info(
                'Unavailable online sale charge : return when sale channels is empty',
                [
                    'start time of the webhook call for the online sale charge unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'online sale charge id: ' . $onlineSaleCharge->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook online sale charge unavailable details started', [
            'start time of the webhook call for the online sale charge unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'online sale charge id: ' . $onlineSaleCharge->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::ONLINE_SALES_CHARGES_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $onlineSaleChargeChannelReference?->external_online_sales_charges_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on online sale charge in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'Online sale charge is unavailable in E-Commerce',
                    'request_data' => [
                        'online_sales_charges_id' => $onlineSaleCharge->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on online sale charge in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'online_sales_charges_id' => $onlineSaleCharge->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook online sale charge unavailable details ended', [
            'end time of the webhook call for the online sale charge unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'online sale charge id: ' . $onlineSaleCharge->getKey(),
        ]);
    }

    private function fetchOnlineSaleChargeIdRecords(int $onlineSaleChargeId): OnlineSalesCharges
    {
        $onlineSalesChargesQueries = resolve(OnlineSalesChargesQueries::class);

        return $onlineSalesChargesQueries->getOnlineSalesChargesByIdForEcommerce($onlineSaleChargeId);
    }

    private function fetchOnlineSaleChargeChannelReference(int $onlineSaleChargeId): ?OnlineSalesChargeChannelReference
    {
        $onlineSalesChargeChannelReferenceQueries = resolve(OnlineSalesChargeChannelReferenceQueries::class);

        return $onlineSalesChargeChannelReferenceQueries->getByOnlineSalesChargeId($onlineSaleChargeId);
    }

    private function preparedRecords(
        OnlineSalesCharges $onlineSalesCharge,
        ?OnlineSalesChargeChannelReference $onlineSalesChargeChannelReference,
        SaleChannel $saleChannel,
    ): array {
        $shippingZoneChannelReferenceQueries = resolve(ShippingZoneChannelReferenceQueries::class);
        $shippingZoneChannelReference = $shippingZoneChannelReferenceQueries->getByShippingZoneIdAndSaleChannelId(
            $onlineSalesCharge->shipping_zone_id,
            $saleChannel->id
        );

        if (! $shippingZoneChannelReference instanceof ShippingZoneChannelReference) {
            $syncShippingZoneInEcommerceService = resolve(SyncShippingZoneInEcommerceService::class);
            $syncShippingZoneInEcommerceService->addUpdateDetails($onlineSalesCharge->shipping_zone_id, $saleChannel);
        }

        return [
            'existing_id' => $onlineSalesChargeChannelReference?->external_online_sales_charges_id,
            'shipping_zone_id' => $shippingZoneChannelReference?->external_shipping_zone_id,
            'shipping_charge_type_id' => $onlineSalesCharge->shipping_charge_type_id,
            'name' => $onlineSalesCharge->name,
            'minimum_value' => $onlineSalesCharge->minimum_value,
            'maximum_value' => $onlineSalesCharge->maximum_value,
            'amount' => $onlineSalesCharge->amount,
            'status' => (int) $onlineSalesCharge->status,
            'online_sales_charge_tiers' => $onlineSalesCharge->onlineSalesChargeTiers->toArray(),
        ];
    }
}
