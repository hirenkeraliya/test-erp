<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherChannelReference\VoucherChannelReferenceQueries;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\SaleChannel;
use App\Models\Voucher;
use App\Models\VoucherChannelReference;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherSaleChannelService
{
    public function createVoucher(Voucher $voucher): void
    {
        Log::channel('e_commerce')->info('Start creating the voucher in eCommerce.', [
            'Start time for voucher creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);

        $voucherQueries = resolve(VoucherQueries::class);
        $voucher = $voucherQueries->getByOnlyId($voucher->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::VOUCHER_CREATE->value];

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany(
            $webhookUrls,
            $voucherConfiguration->company_id
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating voucher : return when sale channels is empty', [
                'Start time for voucher creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
            ]);

            return;
        }

        try {
            $voucherSyncInEcommerce = resolve(VoucherSyncInEcommerce::class);

            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;
                    foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                        $voucherSyncInEcommerce->addOrUpdateVoucherInEcommerce(
                            $saleChannel,
                            $voucher,
                            $saleChannelWebhookUrl->url
                        );
                    }
                }

                if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                    $this->addVoucher($saleChannel, $voucher);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook voucher create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the voucher creation process in eCommerce.', [
            'End time for voucher creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);
    }

    public function addVoucher(SaleChannel $saleChannel, Voucher $voucher): void
    {
        Log::channel('e_commerce')->info('Start adding vouchers in webSpert eCommerce', [
            'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);

        $voucherChannelReferenceQueries = resolve(VoucherChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::VOUCHER_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $voucherChannelReference = $voucherChannelReferenceQueries->getByVoucherIdAndSaleChannelId(
                $voucher->id,
                $saleChannel->id
            );

            if ($voucherChannelReference instanceof VoucherChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info('adding vouchers : call update voucher details', [
                    'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'voucher id: ' . $voucher->getKey(),
                ]);

                $this->updateVoucherDetails($saleChannel, $voucher);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'voucher' => $this->preparedRecords($voucher, $voucherChannelReference),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response:voucher in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('voucher_id', $responseData)) {
                    $voucherChannelReferenceQueries = resolve(VoucherChannelReferenceQueries::class);
                    $voucherChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'voucher_id' => $voucher->id,
                        'external_voucher_id' => $responseData['voucher_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on voucher in webSpert E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher_id' => $voucher->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('adding vouchers : webhook url not found', [
                'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End voucher addition in webSpert eCommerce', [
            'Completion time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);
    }

    public function updateVoucher(Voucher $voucher): void
    {
        Log::channel('e_commerce')->info('Start updating vouchers in eCommerce', [
            'Start time for voucher update' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);

        $voucherQueries = resolve(VoucherQueries::class);
        $voucher = $voucherQueries->getByOnlyId($voucher->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::VOUCHER_UPDATE->value];

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany(
            $webhookUrls,
            $voucherConfiguration->company_id
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating vouchers : return when sale channels is empty', [
                'Start time for voucher update' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
            ]);

            return;
        }

        try {
            $voucherSyncInEcommerce = resolve(VoucherSyncInEcommerce::class);

            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;
                    foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                        $voucherSyncInEcommerce->addOrUpdateVoucherInEcommerce(
                            $saleChannel,
                            $voucher,
                            $saleChannelWebhookUrl->url
                        );
                    }
                }

                if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                    $this->updateVoucherDetails($saleChannel, $voucher);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook voucher update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End voucher update in eCommerce', [
            'Completion time for voucher update' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);
    }

    private function updateVoucherDetails(SaleChannel $saleChannel, Voucher $voucher): void
    {
        Log::channel('e_commerce')->info('Start updating voucher details in webSpert eCommerce.', [
            'Start time for updating voucher details' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);

        $voucherChannelReferenceQueries = resolve(VoucherChannelReferenceQueries::class);

        $voucherChannelReference = $voucherChannelReferenceQueries->getByVoucherIdAndSaleChannelId(
            $voucher->id,
            $saleChannel->id
        );

        if (! $voucherChannelReference instanceof VoucherChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating voucher : call add voucher.', [
                'Start time for updating voucher details' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
            ]);

            $this->addVoucher($saleChannel, $voucher);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::VOUCHER_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'voucher' => $this->preparedRecords($voucher, $voucherChannelReference),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update voucher in webSpert E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error in update voucher in webSpert E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher_id' => $voucher->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating voucher : web hook url not found.', [
                'Start time for updating voucher details' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating voucher details in webSpert eCommerce', [
            'Completion time for updating voucher details' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);
    }

    private function preparedRecords(Voucher $voucher, ?VoucherChannelReference $voucherChannelReference): array
    {
        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        return [
            'existing_id' => $voucherChannelReference?->external_voucher_id,
            'created_by_store_id' => $voucher->created_by_location_id,
            'created_by_location_id' => $voucher->created_by_location_id,
            'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
            'voucher_type' => VoucherConfigurationService::getVoucherType(
                $voucherConfiguration->restricted_by_type,
                $voucherConfiguration->voucher_type,
                $voucherConfiguration->discount_type
            ),
            'number' => $voucher->number,
            'name' => $voucherConfiguration->title,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'percentage' => $voucher->percentage,
            'flat_amount' => $voucher->flat_amount,
            'expiry_date' => $voucher->expiry_date,
            'dream_price_applicable' => $voucher->dream_price_applicable,
            'item_wise_promotion_applicable' => $voucher->item_wise_promotion_applicable,
            'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
        ];
    }
}
