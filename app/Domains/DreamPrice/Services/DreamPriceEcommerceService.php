<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPriceChannelReference\DreamPriceChannelReferenceQueries;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\DreamPrice;
use App\Models\DreamPriceChannelReference;
use App\Models\DreamPriceProduct;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceEcommerceService
{
    public function addUpdateDreamPrice(DreamPrice $dreamPrice, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the dream price in eCommerce.', [
            'Start time for dream price creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);

        $dreamPriceChannelReferenceQueries = resolve(DreamPriceChannelReferenceQueries::class);
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $locationAndSaleChannelMatch = $dreamPriceQueries->validateLocationAndSaleChannelMatch(
                $dreamPrice,
                $saleChannel
            );

            if ($locationAndSaleChannelMatch) {
                $dreamPriceChannelReference = $dreamPriceChannelReferenceQueries->getByDreamPriceIdAndSaleChannelId(
                    $dreamPrice->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'dream_price' => $this->preparedRecords($dreamPrice, $dreamPriceChannelReference, $saleChannel),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: dream price in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('dream_price_id', $responseData) && ! $dreamPriceChannelReference) {
                        $dreamPriceChannelReferenceQueries = resolve(DreamPriceChannelReferenceQueries::class);
                        $dreamPriceChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->id,
                            'dream_price_id' => $dreamPrice->id,
                            'external_dream_price_id' => $responseData['dream_price_id'],
                        ]);
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on dream price in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'dream_price_id' => $dreamPrice->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }

            if (! $locationAndSaleChannelMatch) {
                $this->unAvailableDreamPriceInCommerce($dreamPrice->id);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the dream price in eCommerce.', [
            'End time for dream price creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);
    }

    public function updateProductDreamPrice(
        string $startDate,
        string $endDate,
        int $saleChannelId,
        DreamPriceProduct $dreamPriceProduct
    ): void {
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelId(
            $dreamPriceProduct->product_id,
            $saleChannelId
        );

        if (! $productChannelReference) {
            Log::channel('e_commerce')->info(
                'product channel reference : return when product channel reference not found',
                [
                    'start time of the webhook call for the product dream price update' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'product id: ' . $dreamPriceProduct->product_id,
                ]
            );

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $saleChannel = $saleChannelQueries->getByIdAndStatus($saleChannelId);
        $saleChannels = collect();

        if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
            $saleChannels = collect([$saleChannel]);
        }

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('product channel reference : return when sales channels is empty', [
                'start time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $dreamPriceProduct->product_id,
            ]);

            return;
        }

        Log::channel('e_commerce')->info('product channel reference webhook product update started', [
            'start time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $dreamPriceProduct->product_id,
        ]);

        $productUpdateData = [
            'external_product_id' => $productChannelReference->external_product_id,
            'special_price' => $dreamPriceProduct->price,
            'special_price_from' => $startDate,
            'special_price_to' => $endDate,
        ];

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                    ->firstWhere('webhook_url_type_id', WebhookUrls::DREAM_PRICE_UPDATES->value);

                $url = $saleChannelWebhookUrl->url;

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    ...$productUpdateData,
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: update Dream price in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    return;
                }

                Log::channel('e_commerce')->info('Response: Error on update Dream price in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'dreamPriceProduct_id' => $dreamPriceProduct->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error(
                'product channel reference webhook product update failed',
                [
                    'Error message' => $throwable->getMessage(),
                    'Error code' => $throwable->getCode(),
                    'File' => $throwable->getFile(),
                    'Line' => $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]
            );
        }

        Log::channel('e_commerce')->info('product channel reference webhook product update ended', [
            'end time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $dreamPriceProduct->product_id,
        ]);
    }

    public function unAvailableDreamPriceInCommerce(int $dreamPriceId): void
    {
        $dreamPrice = $this->fetchDreamPriceChannelReferenceRecords($dreamPriceId);
        $dreamPriceChannelReference = $this->fetchDreamPriceChannelReference($dreamPrice->getKey());

        if (! $dreamPriceChannelReference instanceof DreamPriceChannelReference) {
            Log::channel('dream_price_channel_reference')->info(
                'unavailable dream price : create dream price call',
                [
                    'start time of the webhook call for the dream price unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'dream price id: ' . $dreamPrice->getKey(),
                ]
            );
        }

        $webhookUrls = [WebhookUrls::DREAM_PRICE_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $dreamPrice->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('product_collection_channel_reference')->info(
                'Unavailable dream price : return when sale channels is empty',
                [
                    'start time of the webhook call for the dream price unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'dream price id: ' . $dreamPrice->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook dream price unavailable details started', [
            'start time of the webhook call for the dream price unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::DREAM_PRICE_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $dreamPriceChannelReference?->external_dream_price_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on dream price in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'dream price is unavailable in E-Commerce',
                    'request_data' => [
                        'product_collection_id' => $dreamPrice->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on dream price in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'product_collection_id' => $dreamPrice->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook dream price unavailable details ended', [
            'end time of the webhook call for the dream price unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);
    }

    private function fetchDreamPriceChannelReferenceRecords(int $dreamPriceId): DreamPrice
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        return $dreamPriceQueries->getDreamPriceByIdForEcommerce($dreamPriceId);
    }

    private function fetchDreamPriceChannelReference(int $dreamPriceId): ?DreamPriceChannelReference
    {
        $dreamPriceChannelReferenceQueries = resolve(DreamPriceChannelReferenceQueries::class);

        return $dreamPriceChannelReferenceQueries->getDreamPriceIdIdForEcommerce($dreamPriceId);
    }

    private function getDreamPriceProducts(DreamPrice $dreamPrice, SaleChannel $saleChannel): array
    {
        $products = [];

        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrice = $dreamPriceQueries->getDreamPriceById($dreamPrice->id);

        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $dreamPriceProducts = $dreamPriceProductQueries->getByDreamPriceId($dreamPrice->id);

        foreach ($dreamPriceProducts as $dreamPriceProduct) {
            $productChannelReference = resolve(ProductChannelReferenceQueries::class)
                ->getByProductIdAndSaleChannelId($dreamPriceProduct->product_id, $saleChannel->id);

            if ($productChannelReference) {
                $products[] = [
                    'external_product_id' => $productChannelReference->external_variant_id,
                    'price' => $dreamPriceProduct->price,
                ];
            }
        }

        return $products;
    }

    private function preparedRecords(
        DreamPrice $dreamPrice,
        ?DreamPriceChannelReference $dreamPriceChannelReference,
        SaleChannel $saleChannel
    ): array {
        $products = $this->getDreamPriceProducts($dreamPrice, $saleChannel);

        return [
            'existing_id' => $dreamPriceChannelReference?->external_dream_price_id,
            'name' => $dreamPrice->name,
            'start_date' => $dreamPrice->start_date,
            'end_date' => $dreamPrice->end_date,
            'status' => $dreamPrice->status,
            'products' => $products,
        ];
    }
}
