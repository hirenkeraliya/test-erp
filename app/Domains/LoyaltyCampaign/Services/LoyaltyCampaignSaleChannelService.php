<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\Services;

use App\Domains\BrandChannelReference\BrandChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyCampaign\Resources\EcommerceLoyaltyCampaignListResource;
use App\Domains\LoyaltyCampaignChannelReference\LoyaltyCampaignChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoyaltyCampaignSaleChannelService
{
    public function createLoyaltyCampaign(LoyaltyCampaign $loyaltyCampaign): void
    {
        Log::channel('e_commerce')->info('Start creating the loyalty campaign options in eCommerce.', [
            'Start time for loyalty campaign creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);

        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaign = $loyaltyCampaignQueries->getByOnlyId($loyaltyCampaign->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::LOYALTY_CAMPAIGN_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $loyaltyCampaign->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating loyalty campaign : return when sale channels is empty', [
                'Start time for loyalty campaign creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addLoyaltyCampaign($saleChannel, $loyaltyCampaign);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook loyalty campaign create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the loyalty campaign creation process in eCommerce.', [
            'End time for loyalty campaign creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);
    }

    public function addLoyaltyCampaign(SaleChannel $saleChannel, LoyaltyCampaign $loyaltyCampaign): void
    {
        Log::channel('e_commerce')->info('Start adding loyalty campaigns in eCommerce', [
            'Start time for loyalty campaign addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);

        $loyaltyCampaignChannelReferenceQueries = resolve(LoyaltyCampaignChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::LOYALTY_CAMPAIGN_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $loyaltyCampaignChannelReference = $loyaltyCampaignChannelReferenceQueries->getByLoyaltyCampaignIdAndSaleChannelId(
                $loyaltyCampaign->id,
                $saleChannel->id
            );

            if ($loyaltyCampaignChannelReference instanceof LoyaltyCampaignChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info('adding loyalty campaigns : call update loyalty campaign details', [
                    'Start time for loyalty campaign addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
                ]);

                $this->updateLoyaltyCampaignDetails($saleChannel, $loyaltyCampaign);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'loyalty_campaign' => $this->preparedRecords($loyaltyCampaign, $saleChannel),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: loyalty campaign in E-Commerce', [
                        'response' => $responseData,
                    ]);
                } else {
                    Log::channel('e_commerce')->info('Response: Error on loyalty campaign in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'loyalty_campaign' => $loyaltyCampaign->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'existing_id' => null,
                    'loyalty_campaign' => new EcommerceLoyaltyCampaignListResource($loyaltyCampaign),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: loyalty campaign in webspert', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('loyalty_campaign_id', $responseData)) {
                        $loyaltyCampaignChannelReferenceQueries = resolve(
                            LoyaltyCampaignChannelReferenceQueries::class
                        );
                        $loyaltyCampaignChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->getKey(),
                            'loyalty_campaign_id' => $loyaltyCampaign->id,
                            'external_loyalty_campaign_id' => $responseData['loyalty_campaign_id'],
                        ]);
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on loyalty campaign in webspert', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'loyalty_campaign' => $loyaltyCampaign->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        } else {
            Log::channel('e_commerce')->info('adding loyalty campaigns : webhook url not found', [
                'Start time for loyalty campaign addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End loyalty campaign addition in eCommerce', [
            'Completion time for loyalty campaign addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);
    }

    public function updateLoyaltyCampaign(LoyaltyCampaign $loyaltyCampaign): void
    {
        Log::channel('e_commerce')->info('Start updating loyalty campaigns in eCommerce', [
            'Start time for loyalty campaign update' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);

        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaign = $loyaltyCampaignQueries->getByOnlyId($loyaltyCampaign->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::LOYALTY_CAMPAIGN_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $loyaltyCampaign->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating loyalty campaigns : return when sale channels is empty', [
                'Start time for loyalty campaign update' => Carbon::now()->format('Y-m-d H:i:s'),
                'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateLoyaltyCampaignDetails($saleChannel, $loyaltyCampaign);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook loyalty campaign update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End loyalty campaign update in eCommerce', [
            'Completion time for loyalty campaign update' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);
    }

    private function updateLoyaltyCampaignDetails(SaleChannel $saleChannel, LoyaltyCampaign $loyaltyCampaign): void
    {
        Log::channel('e_commerce')->info('Start updating loyalty campaign details in eCommerce.', [
            'Start time for updating loyalty campaign details' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);

        $loyaltyCampaignChannelReferenceQueries = resolve(LoyaltyCampaignChannelReferenceQueries::class);

        $loyaltyCampaignChannelReference = $loyaltyCampaignChannelReferenceQueries->getByLoyaltyCampaignIdAndSaleChannelId(
            $loyaltyCampaign->id,
            $saleChannel->id
        );

        if (! $loyaltyCampaignChannelReference instanceof LoyaltyCampaignChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating loyalty campaign : call add loyalty campaign.', [
                'Start time for updating loyalty campaign details' => Carbon::now()->format('Y-m-d H:i:s'),
                'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
            ]);

            $this->addLoyaltyCampaign($saleChannel, $loyaltyCampaign);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::LOYALTY_CAMPAIGN_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $text = 'E-commerce';
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'loyalty_campaign' => $this->preparedRecords($loyaltyCampaign, $saleChannel),
                ]);
            }

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $text = 'Websert';
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'existing_id' => $loyaltyCampaignChannelReference->external_loyalty_campaign_id,
                    'loyalty_campaign' => new EcommerceLoyaltyCampaignListResource($loyaltyCampaign),
                ]);
            }

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update loyalty campaign in'. $text, [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error in update loyalty campaign in'. $text, [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'loyalty_campaign_id' => $loyaltyCampaign->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating loyalty campaign : web hook url not found.', [
                'Start time for updating loyalty campaign details' => Carbon::now()->format('Y-m-d H:i:s'),
                'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating loyalty campaign details in eCommerce', [
            'Completion time for updating loyalty campaign details' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty campaign id: ' . $loyaltyCampaign->getKey(),
        ]);
    }

    private function getExternalBrandsIds(LoyaltyCampaign $loyaltyCampaign, int $saleChannelId): array
    {
        $excludedBrandIds = $loyaltyCampaign->excludedBrands()
            ->select('brands.id')
            ->pluck('id')
            ->toArray();

        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);

        return $brandChannelReferenceQueries->getByBrandIdsAndSaleChannelId($excludedBrandIds, $saleChannelId)
            ->pluck('external_brand_id')
            ->toArray();
    }

    private function preparedRecords(LoyaltyCampaign $loyaltyCampaign, SaleChannel $saleChannel): array
    {
        $externalBrandIds = $this->getExternalBrandsIds($loyaltyCampaign, $saleChannel->id);

        return [
            'external_id' => $loyaltyCampaign->getKey(),
            'name' => $loyaltyCampaign->name,
            'minimum_spend_amount' => $loyaltyCampaign->minimum_spend_amount,
            'loyalty_points' => $loyaltyCampaign->loyalty_points,
            'loyalty_point_expiration_days' => $loyaltyCampaign->loyalty_point_expiration_days,
            'start_date' => $loyaltyCampaign->start_date,
            'end_date' => $loyaltyCampaign->end_date,
            'brands' => $externalBrandIds,
        ];
    }
}
