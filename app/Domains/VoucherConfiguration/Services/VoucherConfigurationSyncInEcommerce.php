<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Services;

use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\MembershipChannelReference\MembershipChannelReferenceQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfigurationChannelReference\VoucherConfigurationChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoucherConfigurationSyncInEcommerce
{
    public function addVoucherConfigurationInEcommerce(
        SaleChannel $saleChannel,
        VoucherConfiguration $voucherConfiguration
    ): void {
        Log::channel('e_commerce')->info('Start adding voucher configuration in eCommerce', [
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
                    'adding voucher configuration : call update voucher configuration details',
                    [
                        'Start time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
                        'voucher configuration id: ' . $voucherConfiguration->getKey(),
                    ]
                );

                $this->updateVoucherConfigurationDetailsInEcommerce($saleChannel, $voucherConfiguration);

                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                'voucher_configuration' => $this->prepareDataForEcommerce($voucherConfiguration, $saleChannel->id),
                'existing_id' => null,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: VoucherConfiguration in E-Commerce', [
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
                Log::channel('e_commerce')->info('Response: Error on Voucher Configuration in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher_configuration_id' => $voucherConfiguration->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('adding voucher configuration : webhook url not found', [
                'Start time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End voucher configuration addition in eCommerce', [
            'Completion time for voucher configuration addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    public function updateVoucherConfigurationDetailsInEcommerce(
        SaleChannel $saleChannel,
        VoucherConfiguration $voucherConfiguration
    ): void {
        Log::channel('e_commerce')->info('Start updating voucher configuration details in eCommerce.', [
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

            $this->addVoucherConfigurationInEcommerce($saleChannel, $voucherConfiguration);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::VOUCHER_CONFIGURATION_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                'existing_id' => $voucherConfigurationChannelReference->external_voucher_configuration_id,
                'voucher_configuration' => $this->prepareDataForEcommerce($voucherConfiguration, $saleChannel->id),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update Voucher Configuration in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error in update Voucher Configuration in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'voucher_configuration_id' => $voucherConfiguration->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating voucher configuration : web hook url not found.', [
                'Start time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher configuration id: ' . $voucherConfiguration->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating voucher configuration details in eCommerce', [
            'Completion time for updating voucher configuration details' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher configuration id: ' . $voucherConfiguration->getKey(),
        ]);
    }

    private function prepareDataForEcommerce(VoucherConfiguration $voucherConfiguration, int $saleChannelId): array
    {
        $productIds = $voucherConfiguration->products->pluck('id')->toArray();
        $externalProductIds = [];
        if (count($productIds) > 0) {
            $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
            $externalProductIds = $productChannelReferenceQueries->getByProductIdAndSaleChannelIds(
                $productIds,
                $saleChannelId
            )->pluck('external_variant_id')->toArray();
        }

        $categoryIds = $voucherConfiguration->categories->pluck('id')->toArray();
        $externalCategoryIds = [];
        if (count($categoryIds) > 0) {
            $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);
            $externalCategoryIds = $categoryChannelReferenceQueries->getBySaleChannelIdCategoryIds(
                $categoryIds,
                $saleChannelId
            )->pluck('external_category_id')->toArray();
        }

        $externalMembershipIds = [];
        if ($voucherConfiguration->voucher_type === VoucherTypes::LOYALTY_POINT->value) {
            $membershipIds = $voucherConfiguration->memberships->pluck('id')->toArray();

            if (count($membershipIds) > 0) {
                $membershipChannelReferenceQueries = resolve(MembershipChannelReferenceQueries::class);
                $externalMembershipIds = $membershipChannelReferenceQueries->getBySaleChannelIdMembershipIds(
                    $membershipIds,
                    $saleChannelId
                )->pluck('external_membership_id')->toArray();
            }
        }

        return [
            'id' => $voucherConfiguration->id,
            'restricted_by_type' => $voucherConfiguration->restricted_by_type,
            'voucher_type' => $voucherConfiguration->voucher_type,
            'exclude_by_type' => $voucherConfiguration->exclude_by_type,
            'products' => $externalProductIds,
            'categories' => $externalCategoryIds,
            'memberships' => $externalMembershipIds,
            'issue_minimum_spend_amount' => (float) $voucherConfiguration->issue_minimum_spend_amount,
            'use_minimum_spend_amount' => (float) $voucherConfiguration->use_minimum_spend_amount,
            'validity_days' => $voucherConfiguration->validity_days,
            'discount_type' => $voucherConfiguration->discount_type,
            'get_value' => (float) $voucherConfiguration->get_value,
            'promotion_tiers' => $voucherConfiguration->voucherConfigurationTiers->map(
                fn ($voucherConfigurationTier): array => [
                    'minimum_spend_amount' => (float) $voucherConfigurationTier->minimum_spend_amount,
                    'maximum_spend_amount' => (float) $voucherConfigurationTier->maximum_spend_amount,
                    'get_value' => (float) $voucherConfigurationTier->get_value,
                ]
            ),
            'start_date' => $voucherConfiguration->start_date,
            'end_date' => $voucherConfiguration->end_date,
            'status' => (int) $voucherConfiguration->status,
            'dream_price_applicable' => $voucherConfiguration->dream_price_applicable,
            'cart_wide_promotion_applicable' => $voucherConfiguration->cart_wide_promotion_applicable,
            'redemption_foot_note' => $voucherConfiguration->redemption_foot_note,
            'handover_foot_note' => $voucherConfiguration->handover_foot_note,
            'title' => $voucherConfiguration->title,
            'description' => $voucherConfiguration->description,
            'terms_and_conditions' => $voucherConfiguration->terms_and_conditions,
            'image' => $voucherConfiguration->getDiskBasedFirstMediaUrl('image'),
            'thumbnail' => $voucherConfiguration->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
