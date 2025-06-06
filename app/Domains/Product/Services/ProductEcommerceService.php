<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\AttributeChannelReference\AttributeChannelReferenceQueries;
use App\Domains\BrandChannelReference\BrandChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\MasterProductChannelReference\MasterProductChannelReferenceQueries;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\AttributeChannelReference;
use App\Models\MasterProductChannelReference;
use App\Models\Product;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use App\Models\SaleChannelWebhookUrl;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductEcommerceService
{
    public function createProduct(Product $product): void
    {
        Log::channel('e_commerce')->info('Start creating the product options in eCommerce.', [
            'Start time for product creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductByIdWithRelationsForEcommerce($product->id);

        if ($product->status === Statuses::DRAFT->value) {
            Log::channel('e_commerce')->info('creating product : return when status is draft.', [
                'Start time for product creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
                'product status: ' . $product->status,
            ]);

            return;
        }

        if (! $product->is_available_in_ecommerce) {
            Log::channel('e_commerce')->info('creating product : return when is not available in ecommerce.', [
                'Start time for product creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
                'product Is Available In Ecommerce: ' . $product->is_available_in_ecommerce,
            ]);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
            $webhookUrls,
            $product->company_id,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating product : return when sale channels is empty.', [
                'Start time for product creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                    continue;
                }

                if ($productQueries->validateProductSaleChannelMatch($product, $saleChannel)) {
                    $this->addProduct($saleChannel, $product);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the product creation process in eCommerce.', [
            'End time for product creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    public function updateProduct(Product $product): void
    {
        Log::channel('e_commerce')->info('Start updating products in eCommerce', [
            'Start time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductByIdWithRelationsForEcommerce($product->id);

        if ($product->status === Statuses::DRAFT->value) {
            Log::channel('e_commerce')->info('updating products : return when status is draft.', [
                'Start time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
                'product status: ' . $product->status,
            ]);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
            $webhookUrls,
            $product->company_id,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating products : return when sale channels is empty.', [
                'Start time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                    continue;
                }

                Log::channel('e_commerce')->info('Update Product Details.', [
                    'Start time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
                    'product id: ' . $product->getKey(),
                ]);

                $productSaleChannelMatch = $productQueries->validateProductSaleChannelMatch($product, $saleChannel);

                if ($productSaleChannelMatch) {
                    $this->updateProductDetails($saleChannel, $product);
                }

                if (! $productSaleChannelMatch) {
                    $this->unAvailableProductInCommerce($product);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End product update in eCommerce', [
            'Completion time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    public function deleteProduct(Product $product): void
    {
        Log::channel('e_commerce')->info('Start Delete product in eCommerce', [
            'Start time for product delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $product->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('Delete product : sale channels is empty', [
                'Start time for product delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        Log::channel('e_commerce')->info('Start delete the product in eCommerce.', [
            'Start time for product delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);

        try {
            foreach ($saleChannels as $saleChannel) {
                foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                        $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelIdForEcommerce(
                            $product->id,
                            $saleChannel->id
                        );

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $saleChannel->secret,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->timeout(config('services.http_time_out'))->post($url, [
                            'existing_id' => $productChannelReference?->external_variant_id,
                        ]);

                        if ($response->successful()) {
                            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                            Log::channel('e_commerce')->info('Response: product in E-Commerce', [
                                'response' => $responseData,
                            ]);
                        } else {
                            Log::channel('e_commerce')->info('Response: Error on product in E-Commerce', [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => [
                                    'product_id' => $product->getKey(),
                                    'saleChannel_id' => $saleChannel->getKey(),
                                ],
                            ]);
                        }
                    }
                }

                Log::channel('e_commerce')->info('End delete the product in eCommerce.', [
                    'End time for product delete' => Carbon::now()->format('Y-m-d H:i:s'),
                    'product id: ' . $product->getKey(),
                ]);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product delete details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End Delete product in eCommerce', [
            'End time for product delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    public function mergeProduct(
        int $oldProductId,
        int $newProductId,
        int $companyId,
        Collection $oldProductChannelReferences,
        Collection $newProductChannelReferences,
    ): void {
        Log::channel('e_commerce')->info('Start merge product in eCommerce', [
            'Start time for product merge' => Carbon::now()->format('Y-m-d H:i:s'),
            'old product id: ' . $oldProductId,
            'new product id: ' . $newProductId,
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_MERGE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
            $webhookUrls,
            $companyId,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('merging product : sale channels is empty', [
                'old product id: ' . $oldProductId,
                'new product id: ' . $newProductId,
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $oldProductChannelReference = $oldProductChannelReferences
                    ->where('product_id', $oldProductId)
                    ->firstWhere('sale_channel_id', $saleChannel->id);

                $newProductChannelReference = $newProductChannelReferences
                    ->where('product_id', $newProductId)
                    ->firstWhere('sale_channel_id', $saleChannel->id);

                if (! ($oldProductChannelReference && $newProductChannelReference)) {
                    Log::channel('e_commerce')->info('merging product : external product not found', [
                        'old product id: ' . $oldProductId,
                        'new product id: ' . $newProductId,
                    ]);

                    continue;
                }

                if ($oldProductChannelReference instanceof ProductChannelReference) {
                    $payLoad = [
                        'old_product_id' => $oldProductChannelReference->external_variant_id,
                        'new_product_id' => $newProductChannelReference->external_variant_id,
                    ];

                    foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $saleChannel->secret,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, $payLoad);

                        if ($response->successful()) {
                            $productChannelReferenceQueries->deleteOldProductForMerge($oldProductId);
                            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                            Log::channel('e_commerce')->info('Response: merge old product id: ' . $oldProductId, [
                                'response' => $responseData,
                            ]);
                        }

                        if ($response->failed()) {
                            Log::channel('e_commerce')->info('Response: Error on product merge in E-Commerce', [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => $payLoad,
                            ]);
                        }
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product merge details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    public function addProduct(SaleChannel $saleChannel, Product $product): void
    {
        Log::channel('e_commerce')->info('Start adding products in eCommerce', [
            'Start time for product addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelId(
            $product->id,
            $saleChannel->id
        );

        if ($productChannelReference && $productChannelReference->external_variant_id) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);
            $this->updateProductDetails($saleChannel, $product);

            Log::channel('e_commerce')->info('adding products : Call Update Product', [
                'Start time for product update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        if (! $productChannelReference && $product->article_number) {
            $productChannelReference = $productChannelReferenceQueries->getByArticleNumberAndSaleChannelId(
                $product->article_number,
                $saleChannel->id
            );
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::PRODUCT_CREATE->value);

        $masterProductChannelReference = $this->getMasterProductChannelReference($saleChannel, $product);

        if (! $masterProductChannelReference instanceof MasterProductChannelReference) {
            Log::channel('e_commerce')->info('adding products : master product external id not found.', [
                'Start time for product addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        $productData = [
            'existing_id' => null,
            'master_product_external_id' => $masterProductChannelReference->external_master_product_id,
            'upc' => $product->upc,
            'name' => $product->name,
            'compound_product_name' => $product->compound_product_name,
            'price' => $product->online_price,
            'brand_id' => $this->getExternalBrandId($saleChannel, $product),
            'description' => $product->description ?: $product->name,
            'height' => $product->height,
            'width' => $product->width,
            'weight' => $product->weight,
            'status' => $this->getStatus($product),
            'attribute_ids' => $this->getExternalAttributeIdsAndAttributeValue($saleChannel, $product),
            'images' => array_column($product->getDiskBasedMediaUrls('images'), 'url'),
            'videos' => array_column($product->getDiskBasedMediaUrls('videos'), 'url'),
            'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'stock' => $this->getStockBySaleChannelLocationAndProduct(
                $product->id,
                $saleChannel->default_location_id
            ),
        ];

        if (! $saleChannelWebhookUrl instanceof SaleChannelWebhookUrl) {
            return;
        }

        $url = $saleChannelWebhookUrl->url;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($url, $productData);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('e_commerce')->info('Response: Add New Product Variant in E-Commerce', [
                'response' => $responseData,
            ]);

            if (
                array_key_exists('product_id', $responseData) && array_key_exists('variant_id', $responseData)
            ) {
                $productChannelReferenceQueries->addNew([
                    'sale_channel_id' => $saleChannel->getKey(),
                    'product_id' => $product->id,
                    'external_product_id' => $responseData['product_id'],
                    'external_variant_id' => $responseData['variant_id'],
                ]);
            }
        } else {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('e_commerce')->info('Response: Error in Add New Product Variant in E-Commerce', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_data' => [
                    'product_id' => $product->getKey(),
                    'saleChannel_id' => $saleChannel->getKey(),
                ],
            ]);
        }

        Log::channel('e_commerce')->info('End product addition in eCommerce', [
            'Completion time for product addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    public function unAvailableProductInCommerce(Product $product): void
    {
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReference = $productChannelReferenceQueries->getProductChannelReferenceByProductId(
            $product->getKey()
        );

        if (! $productChannelReference instanceof ProductChannelReference) {
            Log::channel('product_channel_reference')->info('unavailable product : create product call', [
                'start time of the webhook call for the product unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            $this->createProduct($product);

            return;
        }

        $webhookUrls = [WebhookUrls::PRODUCT_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $product->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('product_channel_reference')->info(
                'Unavailable product : return when sale channels is empty',
                [
                    'start time of the webhook call for the product unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'product id: ' . $product->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook product unavailable details started', [
            'start time of the webhook call for the product unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::PRODUCT_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $productChannelReference->external_variant_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on Product in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'Product is unavailable in E-Commerce',
                    'request_data' => [
                        'product_id' => $product->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Product in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'product_id' => $product->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook product unavailable details ended', [
            'end time of the webhook call for the product unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    private function getStockBySaleChannelLocationAndProduct(int $productId, int $locationId): float
    {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryStock($productId, $locationId);

        return $inventory ? (float) $inventory->stock : 0.0;
    }

    private function updateProductDetails(SaleChannel $saleChannel, Product $product): void
    {
        Log::channel('e_commerce')->info('Start updating product details in eCommerce.', [
            'Start time for updating product details' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelIdForEcommerce(
            $product->id,
            $saleChannel->id
        );

        if (! $productChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            $this->addProduct($saleChannel, $product);

            Log::channel('e_commerce')->info('updating product : call add Product.', [
                'Start time for updating product details' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
            'webhook_url_type_id',
            WebhookUrls::PRODUCT_UPDATES->value
        );

        $masterProductChannelReference = $this->getMasterProductChannelReference($saleChannel, $product);

        if (! $masterProductChannelReference instanceof MasterProductChannelReference) {
            Log::channel('e_commerce')->info('adding products : master product external id not found.', [
                'Start time for product addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        if ($saleChannelWebhookUrl) {
            $productData = [
                'existing_id' => $productChannelReference->external_variant_id,
                'master_product_external_id' => $masterProductChannelReference->external_master_product_id,
                'upc' => $product->upc,
                'article_number' => $product->article_number,
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
                'price' => $product->online_price,
                'brand_id' => $this->getExternalBrandId($saleChannel, $product),
                'description' => $product->description ?: $product->name,
                'height' => $product->height,
                'width' => $product->width,
                'weight' => $product->weight,
                'status' => $this->getStatus($product),
                'attribute_ids' => $this->getExternalAttributeIdsAndAttributeValue($saleChannel, $product),
                'images' => array_column($product->getDiskBasedMediaUrls('images'), 'url'),
                'videos' => array_column($product->getDiskBasedMediaUrls('videos'), 'url'),
                'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
                'stock' => $this->getStockBySaleChannelLocationAndProduct(
                    $product->id,
                    $saleChannel->default_location_id
                ),
            ];

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, $productData);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Update Existing Product in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error in Update Existing Product in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'product_id' => $product->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating product : webhook url not found.', [
                'Start time for updating product details' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating product details in eCommerce', [
            'Completion time for updating product details' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    private function getStatus(Product $product): int
    {
        if ($product->status !== Statuses::ACTIVE->value) {
            return 0;
        }

        if ($product->deleted_at) {
            return 0;
        }

        return (int) $product->is_available_in_ecommerce;
    }

    private function getMasterProductChannelReference(
        SaleChannel $saleChannel,
        Product $product
    ): ?MasterProductChannelReference {
        $masterProductChannelReferenceQueries = resolve(MasterProductChannelReferenceQueries::class);

        return $masterProductChannelReferenceQueries->getByMasterProductIdAndSaleChannelId(
            (int) $product->master_product_id,
            $saleChannel->id
        );
    }

    private function getExternalAttributeIdsAndAttributeValue(SaleChannel $saleChannel, Product $product): array
    {
        $productVariantValues = $product->productVariantValues;

        $externalAttributeIds = [];
        $attributeChannelReferenceQueries = resolve(AttributeChannelReferenceQueries::class);

        if (count($productVariantValues) > 0) {
            $attributeIds = $productVariantValues->pluck('attribute_id')->toArray();
            $attributeChannelReferences = $attributeChannelReferenceQueries->getByAttributeIdAndSaleChannelIds(
                $attributeIds,
                $saleChannel->id
            );

            foreach ($productVariantValues as $productVariantValue) {
                $attributeChannelReference = $attributeChannelReferences->firstWhere(
                    'attribute_id',
                    $productVariantValue->attribute_id
                );

                if ($attributeChannelReference instanceof AttributeChannelReference) {
                    $externalAttributeIds[] = [
                        'attribute_id' => $attributeChannelReference->external_attribute_id,
                        'variant_value' => $productVariantValue->value,
                    ];
                }
            }
        }

        return $externalAttributeIds;
    }

    private function getExternalBrandId(SaleChannel $saleChannel, Product $product): ?int
    {
        Log::channel('e_commerce')->info('Start time for Get External Brand Id.', [
            'Start time for Get External Brand Id' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);
        $brandChannelReference = $brandChannelReferenceQueries->getByBrandIdAndSaleChannelId(
            $product->brand_id,
            $saleChannel->id
        );

        Log::channel('e_commerce')->info('End time for Get External Brand Id.', [
            'End time for Get External Brand Id' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        return $brandChannelReference?->external_brand_id;
    }
}
