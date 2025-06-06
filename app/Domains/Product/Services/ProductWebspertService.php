<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\Category\Services\CategoryEcommerceService;
use App\Domains\Category\Services\CategoryWebspertService;
use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryChannelReference;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use App\Models\Size;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductWebspertService
{
    public function createProductOnWebspert(Product $product): void
    {
        if ($product->status !== Statuses::ACTIVE->value && ! $product->is_available_in_ecommerce) {
            Log::channel('e_commerce')->info('product variant create : return when product status is not active', [
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        $this->mapExistingProductIfExists($product);

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductByIdWithRelations($product->id);

        $filteredSaleChannels = $product->saleChannels->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE);

        if ($filteredSaleChannels->isEmpty()) {
            return;
        }

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReferenceProducts = collect([]);

        if (null !== $product->article_number) {
            $productChannelReferenceProducts = $productChannelReferenceQueries->getProductForWebspert(
                $product->article_number,
                $product->color_id
            );
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [
            WebhookUrls::PRODUCT_CREATE->value,
            WebhookUrls::PRODUCT_VARIANCE_CREATE->value,
            WebhookUrls::UPLOAD_IMAGE->value,
        ];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $product->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('product variant create : return when sale channels is empty', [
                'start time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        if ($productChannelReferenceProducts->isNotEmpty()) {
            Log::channel('e_commerce')->info('e-commerce webhook product variant create started', [
                'start time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            /** @var ?Size $size */
            $size = $product->size;

            foreach ($productChannelReferenceProducts as $productChannelReferenceProduct) {
                $productData = [
                    'item_id' => $productChannelReferenceProduct->external_product_id,
                    'variations_option_name' => 'Size',
                    'variations' => $size instanceof Size ? [
                        [
                            'value_name' => $size->name,
                            'variation_sku' => $product->upc,
                            'variation_barcode' => $product->upc,
                            'price' => (float) $product->retail_price,
                            'stock' => 0,
                        ],
                    ] : [],
                ];

                Log::channel('e_commerce')->info('product variant create : call add product variant', [
                    'start time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
                    'product id: ' . $product->getKey(),
                ]);

                $this->addProductVariant($saleChannels, $productData, $product->getKey());
            }

            Log::channel('e_commerce')->info('e-commerce webhook product variant create ended', [
                'End time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook product create started', [
            'start time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        /** @var Brand $brand */
        $brand = $product->brand;

        /** @var ?Size $size */
        $size = $product->size;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Category $category */
        $category = $product->categories->first();

        $itemSku = $size instanceof Size ? '' : $product->article_number;
        $itemSku = '' === $itemSku ? $product->upc : $itemSku;

        $productData = [
            'category_id' => $category ? $this->getCategoryId($category) : null,
            'name' => $product->name,
            'item_sku' => $itemSku,
            'item_barcode' => $product->upc,
            'price' => (float) $product->retail_price,
            'stock' => 0,
            'images' => array_column($product->getDiskBasedMediaUrls('images'), 'url'),
            'video' => $product->getDiskBasedFirstMediaUrl('videos'),
            'brand' => $brand->name,
            'main_color' => $color?->name,
            'status' => 'NORMAL',
            'variations_option_name' => 'Size',
            'variations' => $size instanceof Size ? [
                [
                    'value_name' => $size->name,
                    'variation_sku' => $product->upc,
                    'variation_barcode' => $product->upc,
                    'price' => (float) $product->retail_price,
                    'stock' => 0,
                ],
            ] : [],
        ];

        Log::channel('e_commerce')->info('product variant create : call add product', [
            'start time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $this->addProduct($saleChannels, $productData, $product->getKey());

        Log::channel('e_commerce')->info('e-commerce webhook product create ended', [
            'end time of the webhook call for the product create' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    private function addProduct(Collection $saleChannels, array $productData, int $productId): void
    {
        Log::channel('e_commerce')->info('e-commerce webhook add Product started', [
            'start time of the webhook call for the add Product' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $productId,
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    continue;
                }

                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::PRODUCT_CREATE->value
                );

                $saleChannelUploadImageUrl = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::UPLOAD_IMAGE->value
                )->first()?->url;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    if (! $saleChannelUploadImageUrl) {
                        $productData['images'] = [];
                    }

                    if ($saleChannelUploadImageUrl && [] !== $productData['images']) {
                        $productData['images'] = $this->uploadProductImages(
                            $saleChannelUploadImageUrl,
                            $saleChannel->secret,
                            $productData['images']
                        );
                    }

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'secretkey' => $saleChannel->secret,
                        ...$productData,
                    ]);

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: Add New Product in Webspert', [
                            'response' => $responseData,
                            'productData' => $productData,
                        ]);

                        if (array_key_exists('data', $responseData) && array_key_exists(
                            'item_id',
                            $responseData['data']
                        )) {
                            $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
                            $productChannelReferenceQueries->addNew([
                                'sale_channel_id' => $saleChannel->getKey(),
                                'product_id' => $productId,
                                'external_product_id' => $responseData['data']['item_id'],
                                'external_variant_id' => null,
                            ]);
                        }
                    } else {
                        Log::channel('e_commerce')->info('Response: Error in Add New Product in Webspert', [
                            'status_code' => $response->status(),
                            'response_body' => $response->body() ?: 'No response body provided',
                            'request_data' => [
                                'product_id' => $productId,
                                'saleChannel_id' => $saleChannel->getKey(),
                            ],
                        ]);
                    }
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

        Log::channel('e_commerce')->info('e-commerce webhook add Product ended', [
            'End time of the webhook call for the add Product' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $productId,
        ]);
    }

    private function addProductVariant(Collection $saleChannels, array $productData, int $productId): void
    {
        Log::channel('e_commerce')->info('e-commerce webhook Add Product Variant started', [
            'Start time of the webhook call for the Add Product Variant' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $productId,
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    continue;
                }

                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::PRODUCT_VARIANCE_CREATE->value
                );

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'secretkey' => $saleChannel->secret,
                        ...$productData,
                    ]);

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: Add New Product Variant in Webspert', [
                            'response' => $responseData,
                            'productData' => $productData,
                        ]);

                        if (array_key_exists('data', $responseData) && array_key_exists(
                            'item_id',
                            $responseData['data'][0]
                        ) && array_key_exists('variation_id', $responseData['data'][0])) {
                            $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
                            $productChannelReferenceQueries->addNew([
                                'sale_channel_id' => $saleChannel->getKey(),
                                'product_id' => $productId,
                                'external_product_id' => $responseData['data'][0]['item_id'],
                                'external_variant_id' => $responseData['data'][0]['variation_id'] ?? null,
                            ]);
                        }
                    } else {
                        Log::channel('e_commerce')->info('Response: Error in Add New Product Variant in Webspert', [
                            'status_code' => $response->status(),
                            'response_body' => $response->body() ?: 'No response body provided',
                            'request_data' => [
                                'product_id' => $productId,
                                'saleChannel_id' => $saleChannel->getKey(),
                            ],
                        ]);
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product variant details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook Add Product Variant ended', [
            'End time of the webhook call for the Add Product Variant' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $productId,
        ]);
    }

    public function searchProduct(SaleChannel $saleChannel, string $webspertProductUpc): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannel->url . '/product/search_product_detail', [
                'secretkey' => $saleChannel->secret,
                'barcode' => $webspertProductUpc,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Product search in Webspert', [
                    'response' => $responseData,
                ]);

                if (! is_array($responseData)) {
                    return [];
                }

                if (! array_key_exists('data', $responseData)) {
                    return [];
                }

                if (! array_key_exists('products', $responseData['data'])) {
                    return [];
                }

                return $responseData['data']['products'];
            }

            Log::channel('e_commerce')->info('Response: Error on Product search in Webspert', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_data' => [
                    'productUpc' => $webspertProductUpc,
                    'saleChannel_id' => $saleChannel->getKey(),
                ],
            ]);

            return [];
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('search product went something wrong.', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return [];
    }

    public function getCategoryId(Category $category): ?int
    {
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);
        $categoryChannelReference = $categoryChannelReferenceQueries->getExternalCategoryIdFromCategoryId(
            $category->getKey()
        );

        if ($categoryChannelReference instanceof CategoryChannelReference) {
            return (int) $categoryChannelReference->external_category_id;
        }

        $categoryEcommerceService = resolve(CategoryEcommerceService::class);
        $categoryId = $categoryEcommerceService->createCategory($category);

        if (null === $categoryId) {
            $categoryWebspertService = resolve(CategoryWebspertService::class);
            $categoryId = $categoryWebspertService->createCategory($category);
        }

        return null !== $categoryId ? (int) $categoryId : null;
    }

    public function getProductDetails(Collection $saleChannels, int $externalProductId): array
    {
        foreach ($saleChannels as $saleChannel) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannel->url . '/product/get_product_detail', [
                'secretkey' => $saleChannel->secret,
                'item_id' => $externalProductId,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Get Product details in Webspert', [
                    'response' => $responseData,
                ]);

                return $responseData['data'];
            }

            Log::channel('e_commerce')->info('Response: Error in Get Product details in Webspert', [
                [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'externalProduct_id' => $externalProductId,
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ],
            ]);

            return [];
        }

        return [];
    }

    public function updateProductOnWebspert(Product $product, bool $isSizeIdChanged, bool $isColorIdChanged): void
    {
        if ($product->status !== Statuses::ACTIVE->value && ! $product->is_available_in_ecommerce) {
            Log::channel('e_commerce')->info('product variant update : return when product status is not active', [
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        $this->mapExistingProductIfExists($product);

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductByIdWithRelations($product->id);

        $filteredSaleChannels = $product->saleChannels->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE);

        if ($filteredSaleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook product update details started', [
            'start time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReferenceProduct = $productChannelReferenceQueries->getProductIdForWebspert(
            $product->getKey(),
        );

        if (! $productChannelReferenceProduct instanceof ProductChannelReference) {
            Log::channel('e_commerce')->info('product variant update : call Create Product On Webspert', [
                'start time of the webhook call for the product variant update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            $this->createProductOnWebspert($product);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [
            WebhookUrls::PRODUCT_UPDATES->value,
            WebhookUrls::PRODUCT_VARIANCE_UPDATE->value,
            WebhookUrls::PRODUCT_VARIANCE_DELETE->value,
            WebhookUrls::UPLOAD_IMAGE->value,
        ];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $product->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        if (null !== $productChannelReferenceProduct->external_variant_id) {
            Log::channel('e_commerce')->info('e-commerce webhook product variant update started', [
                'start time of the webhook call for the product variant update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            /** @var ?Size $size */
            $size = $product->size;

            $productData = [
                'item_id' => $productChannelReferenceProduct->external_product_id,
                'variations' => $size instanceof Size ? [
                    [
                        'variation_id' => $productChannelReferenceProduct->external_variant_id,
                        'value_name' => $size->name,
                        'variation_sku' => $product->upc,
                        'variation_barcode' => $product->upc,
                        'price' => (float) $product->retail_price,
                        'stock' => $product?->inventory?->stock ?? 0,
                    ],
                ] : [],
            ];

            $this->updateProductVariantDetails($saleChannels, $product, $productChannelReferenceProduct, $productData);

            Log::channel('e_commerce')->info('e-commerce webhook product variant update ended', [
                'end time of the webhook call for the product variant update' => Carbon::now()->format('Y-m-d H:i:s'),
                'product id: ' . $product->getKey(),
            ]);

            return;
        }

        if (null === $productChannelReferenceProduct->external_variant_id && $isSizeIdChanged) {
            /** @var ?Size $size */
            $size = $product->size;

            $webspertProduct = $this->getProductDetails(
                $saleChannels,
                (int) $productChannelReferenceProduct->external_product_id
            );

            if ([] !== $webspertProduct && array_key_exists(
                'variations',
                $webspertProduct
            ) && is_array($webspertProduct['variations']) && [] !== $webspertProduct['variations']) {
                $productData = [
                    'item_id' => $productChannelReferenceProduct->external_product_id,
                    'variations' => $size instanceof Size ? [
                        [
                            'variation_id' => $webspertProduct['variations'][0]['variation_id'],
                            'value_name' => $size->name,
                            'variation_sku' => $product->upc,
                            'variation_barcode' => $product->upc,
                            'price' => (float) $product->retail_price,
                            'stock' => $product?->inventory?->stock ?? 0,
                        ],
                    ] : [],
                ];

                $this->updateProductVariantDetails(
                    $saleChannels,
                    $product,
                    $productChannelReferenceProduct,
                    $productData
                );
            }

            if ([] !== $webspertProduct && array_key_exists(
                'variations',
                $webspertProduct
            ) && is_array($webspertProduct['variations']) && [] === $webspertProduct['variations']) {
                $this->deleteTheExternalProduct($productChannelReferenceProduct);
                $this->createProductOnWebspert($product);
            }
        }

        if (null === $productChannelReferenceProduct->external_variant_id && $isColorIdChanged) {
            /** @var ?Size $size */
            $size = $product->size;

            $webspertProduct = $this->getProductDetails(
                $saleChannels,
                (int) $productChannelReferenceProduct->external_product_id
            );

            if ([] !== $webspertProduct && array_key_exists(
                'variations',
                $webspertProduct
            ) && is_array($webspertProduct['variations']) && [] !== $webspertProduct['variations']) {
                $this->deleteTheExternalProduct($productChannelReferenceProduct);
                $this->createProductOnWebspert($product);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook product update started', [
            'start time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        /** @var Brand $brand */
        $brand = $product->brand;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Category $category */
        $category = $product->categories->first();

        $productData = [
            'item_id' => $productChannelReferenceProduct->external_product_id,
            'category_id' => $category ? $this->getCategoryId($category) : null,
            'name' => $product->name,
            'item_sku' => $product->article_number ?? $product->upc,
            'price' => (float) $product->retail_price,
            'stock' => $product?->inventory?->stock ?? 0,
            'images' => array_column($product->getDiskBasedMediaUrls('images'), 'url'),
            'video' => $product->getDiskBasedFirstMediaUrl('videos'),
            'brand' => $brand->name,
            'main_color' => $color?->name,
            'status' => 'NORMAL',
        ];

        $this->updateProductDetails($saleChannels, $productData);

        Log::channel('e_commerce')->info('e-commerce webhook product update details ended', [
            'end time of the webhook call for the product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }

    public function mapExistingProductIfExists(Product $product): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $productReferenceChannelQueries = resolve(ProductChannelReferenceQueries::class);

        $saleChannels = $saleChannelQueries->getAllByCompanyIdAndTypeId(
            $product->company_id,
            SaleChannelTypes::WEBSPERT_ECOMMERCE->value
        );

        foreach ($saleChannels as $saleChannel) {
            $searchedProducts = $this->searchProduct($saleChannel, $product->upc);
            foreach ($searchedProducts as $searchedProduct) {
                if (! is_array($searchedProduct)) {
                    Log::channel('e_commerce')->info('Search Product In Webspert', ['response is not array']);
                    continue;
                }

                if (! array_key_exists('item_id', $searchedProduct)) {
                    Log::channel('e_commerce')->info('Search Product In Webspert', ['item_id key not in response']);
                    continue;
                }

                $variation = current($searchedProduct['variations']);
                $externalVariantId = null;
                if (is_array($variation) && array_key_exists('variation_id', $variation)) {
                    $externalVariantId = $variation['variation_id'];
                }

                if (
                    is_array($variation)
                    && array_key_exists('variation_barcode', $variation)
                    && array_key_exists('item_barcode', $searchedProduct)
                    && (
                        $variation['variation_barcode'] === $searchedProduct['item_barcode'] ||
                        $variation['variation_sku'] === $searchedProduct['item_barcode']
                    )
                ) {
                    $externalVariantId = null;
                }

                $productReferenceChannelQueries->addNew([
                    'sale_channel_id' => $saleChannel->getKey(),
                    'product_id' => $product->getKey(),
                    'external_product_id' => $searchedProduct['item_id'],
                    'external_variant_id' => $externalVariantId,
                ]);
            }
        }
    }

    private function updateProductDetails(Collection $saleChannels, array $productData): void
    {
        Log::channel('e_commerce')->info('Update Product Details started', [
            'start time of the webhook call for the Update Product Details' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    continue;
                }

                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::PRODUCT_UPDATES->value
                );

                $saleChannelUploadImageUrl = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::UPLOAD_IMAGE->value
                )->first()?->url;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    if (! $saleChannelUploadImageUrl) {
                        $productData['images'] = [];
                    }

                    if ($saleChannelUploadImageUrl && [] !== $productData['images']) {
                        $productData['images'] = $this->uploadProductImages(
                            $saleChannelUploadImageUrl,
                            $saleChannel->secret,
                            $productData['images']
                        );
                    }

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'secretkey' => $saleChannel->secret,
                        ...$productData,
                    ]);

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: Update Product details in Webspert', [
                            'response' => $responseData,
                        ]);
                    } else {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: Error in Update Product details in Webspert', [
                            [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => [
                                    'productData' => $productData,
                                ],
                            ],
                        ]);
                    }
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

        Log::channel('e_commerce')->info('Update Product Details ended', [
            'End time of the webhook call for the Update Product Details' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function uploadProductImages(string $url, string $secret, array $productImages): array
    {
        Log::channel('e_commerce')->info('Upload Product Images started', [
            'start time of the webhook call for the Upload Product Images' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $ecommerceImageUrl = [];
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $secret,
                'images' => $productImages,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Upload Product images in Webspert', [
                    'response' => $responseData,
                ]);

                foreach ($responseData['data']['images'] as $image) {
                    if ('' === $image['ecommerce_image_url']) {
                        continue;
                    }

                    $ecommerceImageUrl[] = $image['ecommerce_image_url'];
                }

                Log::channel('e_commerce')->info('e-commerce webhook upload image success');
            } else {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                Log::channel('e_commerce')->info('Response: Error in Upload Product images in Webspert', [
                    [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                    ],
                ]);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook  upload image failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Upload Product Images ended', [
            'End time of the webhook call for the Upload Product Images' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        return $ecommerceImageUrl;
    }

    private function updateProductVariantDetails(
        Collection $saleChannels,
        Product $product,
        ProductChannelReference $productChannelReference,
        array $productData
    ): void {
        Log::channel('e_commerce')->info('Update Product Variant Details started', [
            'start time of the webhook call for the Update Product Variant Details' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'external product id: ' . $product->id,
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    continue;
                }

                $webspertProduct = current($this->searchProduct($saleChannel, $product->upc));

                if (
                    null !== $product->color_id &&
                    array_key_exists('main_color', $webspertProduct) &&
                    $product->color instanceof Color &&
                    $webspertProduct['main_color'] !== $product->color->name
                ) {
                    $this->deleteTheVariantId($saleChannel, $productChannelReference);
                    $this->createProductOnWebspert($product);

                    return;
                }

                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
                    'webhook_url_type_id',
                    WebhookUrls::PRODUCT_VARIANCE_UPDATE->value
                );

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'secretkey' => $saleChannel->secret,
                        ...$productData,
                    ]);

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: Update Product Variant details in Webspert', [
                            'response' => $responseData,
                        ]);
                    } else {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info(
                            'Response: Error In Update Product Variant details in Webspert',
                            [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => [
                                    'productChannelReference_id' => $productChannelReference->getKey(),
                                    'saleChannel_id' => $saleChannel->getKey(),
                                ],
                            ]
                        );
                    }
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

        Log::channel('e_commerce')->info('Update Product Variant Details ended', [
            'End time of the webhook call for the Update Product Variant Details' => Carbon::now()->format(
                'Y-m-d H:i:s'
            ),
            'external product id: ' . $product->id,
        ]);
    }

    private function deleteTheVariantId(
        SaleChannel $saleChannel,
        ProductChannelReference $productChannelReference
    ): void {
        try {
            Log::channel('e_commerce')->info('e-commerce webhook product variant delete ended', [
                'end time of the webhook call for the product variant delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'external product id: ' . $productChannelReference->external_variant_id,
            ]);

            $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
                'webhook_url_type_id',
                WebhookUrls::PRODUCT_VARIANCE_DELETE->value
            );

            foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                $url = $saleChannelWebhookUrl->url;

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'item_id' => $productChannelReference->external_product_id,
                    'variation_id' => $productChannelReference->external_variant_id,
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Delete Product Variant in Webspert', [
                        'response' => $responseData,
                    ]);

                    $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
                    $productChannelReferenceQueries->deleteExternalVariantId($productChannelReference);
                } else {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Error in Delete Product Variant in Webspert', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'productChannelReference_id' => $productChannelReference->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }

            Log::channel('e_commerce')->info('e-commerce webhook product variant delete ended', [
                'end time of the webhook call for the product variant delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'external product id: ' . $productChannelReference->external_variant_id,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product variant delete failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    public function deleteTheExternalProduct(ProductChannelReference $productChannelReference): void
    {
        // TODO: need to delete the external side product.

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productChannelReferenceQueries->deleteExternalProductId($productChannelReference);
    }
}
