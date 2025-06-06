<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebspertIntegrationService
{
    public function getEcommerceProducts(SaleChannel $saleChannel, string $staticPath): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannel->getUrl() . $staticPath, [
                'secretkey' => $saleChannel->getSecret(),
            ]);

            if ($response->successful()) {
                return json_decode((string) $response->body(), true, 512, JSON_THROW_ON_ERROR);
            }

            Log::channel('product_channel_reference')->error('Product Channel Reference Response', [
                'Product Channel Reference Response' => $response,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('product_channel_reference')->error('product_channel_reference', [
                'Product Channel Reference Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return [];
    }

    public function getEcommerceCategories(SaleChannel $saleChannel, string $staticPath): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannel->getUrl() . $staticPath, [
                'secretkey' => $saleChannel->getSecret(),
            ]);

            if ($response->successful()) {
                return json_decode((string) $response->body(), true, 512, JSON_THROW_ON_ERROR);
            }

            Log::channel('category_channel_reference')->error('Category Channel Reference Response', [
                'Category Channel Reference Response' => $response,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('category_channel_reference')->error('category_channel_reference', [
                'Category Channel Reference Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return [];
    }

    public function updateExternalProductStock(
        SaleChannel $saleChannel,
        Inventory $inventory,
        ProductChannelReference $productChannelReference,
        string $saleChannelWebhookUrl,
    ): void {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl, [
            'secretkey' => $saleChannel->getSecret(),
            'item_id' => $productChannelReference->external_product_id,
            'variation_id' => $productChannelReference->external_variant_id,
            'stock' => $inventory->stock,
        ]);
    }

    public function getEcommerceProductDetails(SaleChannel $saleChannel, string $staticPath, string $upc): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($saleChannel->getUrl() . $staticPath, [
                'secretkey' => $saleChannel->getSecret(),
                'barcode' => $upc,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('product_channel_reference')->info('Success: Webspert - Search Product Details', [
                    'Check Product Channel Reference Response' => $responseData,
                ]);

                return $responseData;
            }

            Log::channel('product_channel_reference')->error('Error: Webspert - Search Product Details', [
                'Check Product Channel Reference Response' => $response,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('product_channel_reference')->error('product_channel_reference', [
                'Check Product Channel Reference Job Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return [];
    }
}
