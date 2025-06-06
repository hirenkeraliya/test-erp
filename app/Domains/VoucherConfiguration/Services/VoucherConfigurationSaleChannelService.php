<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\VoucherConfiguration\Resources\EcommerceVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherConfigurationChannelReference\VoucherConfigurationChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherConfigurationSaleChannelService
{
    public function createVoucherConfiguration(VoucherConfiguration $voucherConfiguration): void
    {
        Log::channel('e_commerce')->info('Start creating the voucher config options in eCommerce.', [
            'Start time for voucher configuration creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getByOnlyId($voucherConfiguration->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::VOUCHER_CONFIGURATION_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $voucherConfiguration->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating voucher configuration : return when sale channels is empty', [
                'Start time for voucher configuration creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);

            return;
        }

        try {
            $voucherConfigurationSyncInEcommerce = resolve(VoucherConfigurationSyncInEcommerce::class);

            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id &&
                    $voucherConfigurationQueries->validateVoucherConfigurationSaleChannelMatch(
                        $voucherConfiguration,
                        $saleChannel
                    )
                ) {
                    $voucherConfigurationSyncInEcommerce->addVoucherConfigurationInEcommerce(
                        $saleChannel,
                        $voucherConfiguration
                    );
                }

                if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                    $this->addVoucherConfiguration($saleChannel, $voucherConfiguration);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook voucher configuration create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the voucher configuration creation process in eCommerce.', [
            'End time for voucher configuration creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    public function updateVoucherConfiguration(VoucherConfiguration $voucherConfiguration): void
    {
        Log::channel('e_commerce')->info('Start updating voucher configuration in eCommerce', [
            'Start time for voucher configuration update' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        $voucherConfiguration = $voucherConfigurationQueries->getByOnlyId($voucherConfiguration->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::VOUCHER_CONFIGURATION_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $voucherConfiguration->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating voucher configuration : return when sale channels is empty', [
                'Start time for voucher configuration update' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);

            return;
        }

        try {
            $voucherConfigurationSyncInEcommerce = resolve(VoucherConfigurationSyncInEcommerce::class);
            foreach ($saleChannels as $saleChannel) {
                $voucherConfigurationSaleChannelMatch = $voucherConfigurationQueries->validateVoucherConfigurationSaleChannelMatch(
                    $voucherConfiguration,
                    $saleChannel
                );

                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id && $voucherConfigurationSaleChannelMatch) {
                    $voucherConfigurationSyncInEcommerce->updateVoucherConfigurationDetailsInEcommerce(
                        $saleChannel,
                        $voucherConfiguration
                    );
                }

                if (! $voucherConfigurationSaleChannelMatch) {
                    $this->unAvailableVoucherConfiguration($voucherConfiguration->id);
                }

                if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                    $this->updateVoucherConfigurationDetails($saleChannel, $voucherConfiguration);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook voucher configuration update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End voucher configuration update in eCommerce', [
            'Completion time for voucher configuration update' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    public function unAvailableVoucherConfiguration(int $voucherConfigurationId): void
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getByIdForEcommerce($voucherConfigurationId);

        $voucherConfigurationChannelReferenceQueries = resolve(VoucherConfigurationChannelReferenceQueries::class);
        $voucherConfigurationChannelReference = $voucherConfigurationChannelReferenceQueries->getByVoucherConfigurationId(
            $voucherConfiguration->id
        );

        $webhookUrls = [WebhookUrls::VOUCHER_CONFIGURATION_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $voucherConfiguration->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('unavailable voucher configuration : return when sale channels is empty', [
                'Start time for voucher configuration unavailability' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook voucher configuration unavailable details started', [
            'start time of the webhook call for the voucher configuration unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::VOUCHER_CONFIGURATION_UNAVAILABLE->value
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                'existing_id' => $voucherConfigurationChannelReference?->external_voucher_configuration_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info(
                    'Response: success on voucher configuration unavailable in E-Commerce',
                    [
                        'status_code' => $response->status(),
                        'response_body' => 'voucher configuration is unavailable in E-Commerce',
                        'request_data' => [
                            'voucher configuration id' => $voucherConfiguration->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]
                );
            } else {
                Log::channel('e_commerce')->info('Response: Error on voucher configuration unavailable in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher configuration id' => $voucherConfiguration->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook voucher configuration unavailable details ended', [
            'end time of the webhook call for the voucher configuration unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    private function addVoucherConfiguration(SaleChannel $saleChannel, VoucherConfiguration $voucherConfiguration): void
    {
        Log::channel('e_commerce')->info('Start adding voucher configuration in webSpert eCommerce', [
            'Start time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);

        $voucherConfigurationChannelReferenceQueries = resolve(VoucherConfigurationChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::VOUCHER_CONFIGURATION_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $voucherConfigurationChannelReference = $voucherConfigurationChannelReferenceQueries->getByVoucherConfigurationIdAndSaleChannelId(
                $voucherConfiguration->id,
                $saleChannel->id
            );

            if ($voucherConfigurationChannelReference instanceof VoucherConfigurationChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info(
                    'adding voucher configuration : call update voucher configuration details in webSpert eCommerce',
                    [
                        'Start time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
                        'voucher configuration id: ' . $voucherConfiguration->getKey(),
                    ]
                );

                $this->updateVoucherConfigurationDetails($saleChannel, $voucherConfiguration);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'voucher_configuration' => new EcommerceVoucherConfigurationListResource($voucherConfiguration),
                'existing_id' => null,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: VoucherConfiguration in webSpert E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('voucher_configuration_id', $responseData)) {
                    $voucherConfigurationChannelReferenceQueries = resolve(
                        VoucherConfigurationChannelReferenceQueries::class
                    );
                    $voucherConfigurationChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'voucher_configuration_id' => $voucherConfiguration->id,
                        'external_voucher_configuration_id' => $responseData['voucher_configuration_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Voucher Configuration in webSpert E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher_configuration_id' => $voucherConfiguration->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info(
                'adding voucher configuration : webhook url not found webSpert eCommerce',
                [
                    'Start time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'voucher configuration id: ' . $voucherConfiguration->getKey(),
                ]
            );
        }

        Log::channel('e_commerce')->info('End voucher configuration addition in webSpert eCommerce', [
            'Completion time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    private function updateVoucherConfigurationDetails(
        SaleChannel $saleChannel,
        VoucherConfiguration $voucherConfiguration
    ): void {
        Log::channel('e_commerce')->info('Start updating voucher configuration details in webSpert eCommerce.', [
            'Start time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);

        $voucherConfigurationChannelReferenceQueries = resolve(VoucherConfigurationChannelReferenceQueries::class);

        $voucherConfigurationChannelReference = $voucherConfigurationChannelReferenceQueries->getByVoucherConfigurationIdAndSaleChannelId(
            $voucherConfiguration->id,
            $saleChannel->id
        );

        if (! $voucherConfigurationChannelReference instanceof VoucherConfigurationChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating voucher configuration : call add voucher configuration.', [
                'Start time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);

            $this->addVoucherConfiguration($saleChannel, $voucherConfiguration);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::VOUCHER_CONFIGURATION_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'existing_id' => $voucherConfigurationChannelReference->external_voucher_configuration_id,
                'voucher_configuration' => new EcommerceVoucherConfigurationListResource($voucherConfiguration),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update Voucher Configuration in webSpert E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info(
                    'Response: Error in update Voucher Configuration in webSpert E-Commerce',
                    [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'voucher_configuration_id' => $voucherConfiguration->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]
                );
            }
        } else {
            Log::channel('e_commerce')->info('updating voucher configuration : web hook url not found.', [
                'Start time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating voucher configuration details in webSpert eCommerce', [
            'Completion time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }
}
