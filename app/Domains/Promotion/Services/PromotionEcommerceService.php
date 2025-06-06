<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\PromotionChannelReference\PromotionChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Promotion;
use App\Models\PromotionChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromotionEcommerceService
{
    public function addUpdateDetails(Promotion $promotion, SaleChannel $saleChannel, string $url): void
    {
        $promotionQueries = resolve(PromotionQueries::class);

        $locationAndSaleChannelMatch = $promotionQueries->validateLocationAndSaleChannelMatch($promotion, $saleChannel);

        if (
            $promotion->promotion_applicable_type_id === PromotionApplicableTypes::CART_WIDE->value &&
            $promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_AMOUNT->value
            && $locationAndSaleChannelMatch
        ) {
            Log::channel('e_commerce')->info('Start creating or updating the promotion in eCommerce.', [
                'Start time for promotion creation or updating' => Carbon::now()->format('Y-m-d H:i:s'),
                'promotion id: ' . $promotion->getKey(),
                'sale channel location id: ' . $saleChannel->default_location_id,
            ]);

            $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);

            $promotionChannelReference = $promotionChannelReferenceQueries->getByPromotionIdAndSaleChannelId(
                $promotion->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'promotion' => $this->preparedRecords($promotion, $promotionChannelReference),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: promotion in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('promotion_id', $responseData) && ! $promotionChannelReference) {
                    $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);
                    $promotionChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'promotion_id' => $promotion->id,
                        'external_promotion_id' => $responseData['promotion_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on promotion in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'promotion_id' => $promotion->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }

            Log::channel('e_commerce')->info('End creating or updating the promotion in eCommerce.', [
                'End time for promotion creation or update' => Carbon::now()->format('Y-m-d H:i:s'),
                'promotion id: ' . $promotion->getKey(),
            ]);
        } else {
            Log::channel('e_commerce')->info('Skipping promotion sync - Invalid location or promotion type', [
                'promotion_id' => $promotion->getKey(),
                'sale_channel_id' => $saleChannel->getKey(),
                'promotion_locations' => $promotion->locations->pluck('id'),
                'sale_channel_location' => $saleChannel->default_location_id,
            ]);
        }

        if (! $locationAndSaleChannelMatch) {
            $this->unAvailablePromotionInCommerce($promotion->id);
        }
    }

    public function unAvailablePromotionInCommerce(int $promotionId): void
    {
        $promotion = $this->fetchPromotionIdRecords($promotionId);

        $promotionChannelReference = $this->fetchPromotionIdChannelReference($promotion->getKey());

        $webhookUrls = [WebhookUrls::PROMOTION_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $promotion->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('promotion_channel_reference')->info(
                'Unavailable promotion : return when sale channels is empty',
                [
                    'start time of the webhook call for the promotion unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'promotion id: ' . $promotion->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook promotion unavailable details started', [
            'start time of the webhook call for the promotion unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'promotion id: ' . $promotion->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::PROMOTION_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $promotionChannelReference?->external_promotion_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on promotion in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'promotion is unavailable in E-Commerce',
                    'request_data' => [
                        'promotion_id' => $promotion->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on promotion in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'promotion_id' => $promotion->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook promotion unavailable details ended', [
            'end time of the webhook call for the promotion unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'promotion id: ' . $promotion->getKey(),
        ]);
    }

    private function fetchPromotionIdRecords(int $promotionId): Promotion
    {
        $promotionQueries = resolve(PromotionQueries::class);

        return $promotionQueries->getPromotionByIdForEcommerce($promotionId);
    }

    private function fetchPromotionIdChannelReference(int $promotionId): ?PromotionChannelReference
    {
        $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);

        return $promotionChannelReferenceQueries->getByPromotionId($promotionId);
    }

    private function preparedRecords(
        Promotion $promotion,
        ?PromotionChannelReference $promotionChannelReference
    ): array {
        return [
            'existing_id' => $promotionChannelReference?->external_promotion_id,
            'name' => $promotion->name,
            'percentage' => $promotion->percentage,
            'flat_amount' => $promotion->flat_amount,
            'start_date' => $promotion->start_date,
            'end_date' => $promotion->end_date,
            'start_time' => $promotion->start_time,
            'end_time' => $promotion->end_time,
            'timeframe_type_id' => $promotion->timeframe_type_id,
            'discount_type_id' => $promotion->discount_type_id,
            'month_dates' => $promotion->monthly->pluck('month_date'),
            'week_days' => $promotion->weekly->pluck('week_day'),
            'promotion_tiers' => $promotion->promotionTiers,
            'status' => (int) $promotion->status,
        ];
    }
}
