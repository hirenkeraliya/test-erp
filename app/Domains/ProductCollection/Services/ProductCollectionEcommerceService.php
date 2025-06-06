<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionChannelReference\ProductCollectionChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\ProductCollection;
use App\Models\ProductCollectionChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCollectionEcommerceService
{
    public function productCollectionCreateUpdateEcommerceService(
        ProductCollection $productCollection,
        array $productIds
    ): void {
        $productCollection->refresh();

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollection = $productCollectionQueries->getByIdWithMedia($productCollection->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_COLLECTION_CREATE_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $productCollection->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook product collection create/update started', [
            'start time of the webhook call for the product collection create/update' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'product collection id: ' . $productCollection->getKey(),
        ]);

        $productCollectionChannelReferenceQueries = resolve(ProductCollectionChannelReferenceQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productCollectionQueries = resolve(ProductCollectionQueries::class);

        try {
            foreach ($saleChannels as $saleChannel) {
                foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    $ProductCollectionSaleChannelMatch = $productCollectionQueries->validateProductCollectionSaleChannelMatch(
                        $productCollection,
                        $saleChannel
                    );

                    if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id && $ProductCollectionSaleChannelMatch) {
                        $productCollectionChannelReference = $productCollectionChannelReferenceQueries->getByProductCollectionIdAndSaleChannelId(
                            $productCollection->id,
                            $saleChannel->id
                        );

                        $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelIds(
                            $productIds,
                            $saleChannel->id
                        );

                        $externalProductIds = $productChannelReference->pluck('external_variant_id')->toArray();

                        if (count($productIds) !== count($externalProductIds)) {
                            Log::channel('e_commerce')->info('Product ID count mismatch for Sale Channel', [
                                'sale_channel_id' => $saleChannel->id,
                                'product_ids' => $productIds,
                                'external_product_ids' => $externalProductIds,
                            ]);
                        }

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $saleChannel->secret,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->timeout(config('services.http_time_out'))->post($url, [
                            'product_collection' => $this->preparedRecords(
                                $productCollection,
                                $productCollectionChannelReference,
                                $externalProductIds
                            ),
                        ]);

                        if ($response->successful()) {
                            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                            Log::channel('e_commerce')->info('Response: Product Collection in E-Commerce', [
                                'response' => $responseData,
                            ]);

                            if (array_key_exists('product_collection_id', $responseData)) {
                                $productCollectionChannelReferenceQueries = resolve(
                                    ProductCollectionChannelReferenceQueries::class
                                );
                                $productCollectionChannelReferenceQueries->addNew([
                                    'sale_channel_id' => $saleChannel->id,
                                    'product_collection_id' => $productCollection->id,
                                    'external_product_collection_id' => $responseData['product_collection_id'],
                                ]);
                            }
                        } else {
                            Log::channel('e_commerce')->info('Response: Error on product collection in E-Commerce', [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => [
                                    'product_collection_id' => $productCollection->getKey(),
                                    'saleChannel_id' => $saleChannel->getKey(),
                                ],
                            ]);
                        }
                    }

                    if (! $ProductCollectionSaleChannelMatch) {
                        $this->unAvailableProductCollectionInCommerce($productCollection->getKey());
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook product collection create/update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook product collection create/update ended', [
            'end time of the webhook call for the product collection create/update' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'product collection id: ' . $productCollection->getKey(),
        ]);
    }

    public function productCollectionImage(ProductCollection $productCollection, SaleChannel $saleChannel): void
    {
        $productCollectionChannelReferenceQueries = resolve(ProductCollectionChannelReferenceQueries::class);

        try {
            foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                $url = $saleChannelWebhookUrl->url;

                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $productCollectionChannelReference = $productCollectionChannelReferenceQueries->getByProductCollectionIdAndSaleChannelId(
                        $productCollection->id,
                        $saleChannel->id
                    );

                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $saleChannel->secret,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'product_collection_images' => [
                            'existing_id' => $productCollectionChannelReference?->external_product_collection_id,
                            'status' => (int) $productCollection->status,
                            'square_image' => $productCollection->getDiskBasedFirstMediaUrl('square_image'),
                            'landscape_image' => $productCollection->getDiskBasedFirstMediaUrl('landscape_images'),
                        ],
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook product collection create/update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    public function productCollectionDelete(
        ProductCollection $productCollection,
        SaleChannel $saleChannel,
        string $url
    ): void {
        Log::channel('e_commerce')->info('Start deleting the product collection in eCommerce.', [
            'Start time for product collection deleting' => Carbon::now()->format('Y-m-d H:i:s'),
            'product collection id: ' . $productCollection->getKey(),
        ]);

        $productCollectionChannelReferenceQueries = resolve(ProductCollectionChannelReferenceQueries::class);

        $productCollectionChannelReference = $productCollectionChannelReferenceQueries->getByProductCollectionIdAndSaleChannelId(
            $productCollection->id,
            $saleChannel->id
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($url, [
            'existing_id' => $productCollectionChannelReference?->external_product_collection_id,
        ]);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('e_commerce')->info('Response: Product Collection in E-Commerce', [
                'response' => $responseData,
            ]);

            if (array_key_exists('product_collection_id', $responseData)) {
                $productCollectionChannelReferenceQueries = resolve(
                    ProductCollectionChannelReferenceQueries::class
                );
                $productCollectionChannelReferenceQueries->deleteById(
                    $responseData['product_collection_id'],
                    $saleChannel->id
                );
            }
        } else {
            Log::channel('e_commerce')->info('Response: Error on product collection in E-Commerce', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_data' => [
                    'product_collection_id' => $productCollection->getKey(),
                    'saleChannel_id' => $saleChannel->getKey(),
                ],
            ]);
        }

        Log::channel('e_commerce')->info('End deleting the product collection in eCommerce.', [
            'End time for product collection deleting' => Carbon::now()->format('Y-m-d H:i:s'),
            'product collection id: ' . $productCollection->getKey(),
        ]);
    }

    public function unAvailableProductCollectionInCommerce(int $productCollectionId): void
    {
        $productCollection = $this->fetchProductCollectionRecords($productCollectionId);
        $productCollectionChannelReference = $this->fetchProductCollectionChannelReference(
            $productCollection->getKey(),
        );

        if (! $productCollectionChannelReference instanceof ProductCollectionChannelReference) {
            Log::channel('product_collection_channel_reference')->info(
                'unavailable product collection : create product collection call',
                [
                    'start time of the webhook call for the product collection unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'product collection id: ' . $productCollection->getKey(),
                ]
            );
        }

        $webhookUrls = [WebhookUrls::PRODUCT_COLLECTION_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $productCollection->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('product_collection_channel_reference')->info(
                'Unavailable product collection : return when sale channels is empty',
                [
                    'start time of the webhook call for the product collection unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'product collection id: ' . $productCollection->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook product collection unavailable details started', [
            'start time of the webhook call for the productCollection unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'product collection id: ' . $productCollection->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::PRODUCT_COLLECTION_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $productCollectionChannelReference?->external_product_collection_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on Product Collection in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'Product Collection is unavailable in E-Commerce',
                    'request_data' => [
                        'product_collection_id' => $productCollection->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Product Collection in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'product_collection_id' => $productCollection->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook Product Collection unavailable details ended', [
            'end time of the webhook call for the Product Collection unavailable' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'product collection id: ' . $productCollection->getKey(),
        ]);
    }

    private function fetchProductCollectionRecords(int $productCollectionId): ProductCollection
    {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);

        return $productCollectionQueries->getProductCollectionByIdForEcommerce($productCollectionId);
    }

    private function fetchProductCollectionChannelReference(
        int $productCollectionId
    ): ?ProductCollectionChannelReference {
        $productCollectionChannelReferenceQueries = resolve(ProductCollectionChannelReferenceQueries::class);

        return $productCollectionChannelReferenceQueries->getProductCollectionIdIdForEcommerce($productCollectionId);
    }

    private function preparedRecords(
        ProductCollection $productCollection,
        ?ProductCollectionChannelReference $productCollectionChannelReference,
        array $productIds
    ): array {
        return [
            'existing_id' => $productCollectionChannelReference?->external_product_collection_id,
            'name' => $productCollection->name,
            'status' => (int) $productCollection->status,
            'product_ids' => $productIds,
        ];
    }
}
