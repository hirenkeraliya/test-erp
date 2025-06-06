<?php

declare(strict_types=1);

namespace App\Domains\Brand\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\BrandChannelReference\BrandChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Brand;
use App\Models\BrandChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrandSaleChannelService
{
    public function createBrand(Brand $brand): void
    {
        Log::channel('e_commerce')->info('Start creating the brand options in eCommerce.', [
            'Start time for brand creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);
        $brandQueries = resolve(BrandQueries::class);
        $brand = $brandQueries->getById($brand->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::BRAND_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByWebhookUrls($webhookUrls);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating brand : return when sale channels empty.', [
                'Start time for brand creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'brand id: ' . $brand->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addBrand($saleChannel, $brand);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook brand create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the brand creation process in eCommerce.', [
            'End time for brand creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);
    }

    public function addBrand(SaleChannel $saleChannel, Brand $brand): void
    {
        Log::channel('e_commerce')->info('Start adding brands in eCommerce', [
            'Start time for brand addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);

        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::BRAND_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $brandChannelReference = $brandChannelReferenceQueries->getByBrandIdAndSaleChannelId(
                $brand->id,
                $saleChannel->id
            );

            if ($brandChannelReference instanceof BrandChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info('add brand : call update brand details .', [
                    'Start time for updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
                    'brand id: ' . $brand->getKey(),
                ]);

                $this->updateBrandDetails($saleChannel, $brand);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'id' => $brand->id,
                    'existing_id' => null,
                    'name' => $brand->name,
                    'code' => $brand->code,
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'existing_id' => null,
                    'name' => $brand->name,
                    'code' => $brand->code,
                ]);
            }

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Banner in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('brand_id', $responseData)) {
                    $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);
                    $brandChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'brand_id' => $brand->id,
                        'external_brand_id' => $responseData['brand_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Banner in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'brand_id' => $brand->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('add brand : webhook url not found .', [
                'Start time for updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
                'brand id: ' . $brand->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End brand addition in eCommerce', [
            'Completion time for brand addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);
    }

    public function updateBrand(Brand $brand): void
    {
        Log::channel('e_commerce')->info('Start updating brands in eCommerce', [
            'Start time for brand update' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);

        $brandQueries = resolve(BrandQueries::class);
        $brand = $brandQueries->getById($brand->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::BRAND_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByWebhookUrls($webhookUrls);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating brands : return when sale channels is empty', [
                'Start time for brand update' => Carbon::now()->format('Y-m-d H:i:s'),
                'brand id: ' . $brand->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateBrandDetails($saleChannel, $brand);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook brand update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End brand update in eCommerce', [
            'Completion time for brand update' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);
    }

    private function updateBrandDetails(SaleChannel $saleChannel, Brand $brand): void
    {
        Log::channel('e_commerce')->info('Start updating brand details in eCommerce.', [
            'Start time for updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);

        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $brandChannelReference = $brandChannelReferenceQueries->getByBrandIdAndSaleChannelId(
            $brand->id,
            $saleChannel->id
        );

        if (! $brandChannelReference instanceof BrandChannelReference) {
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating brand : call add brand.', [
                'Start time for updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
                'brand id: ' . $brand->getKey(),
            ]);

            $this->addBrand($saleChannel, $brand);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::BRAND_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'id' => $brand->id,
                    'existing_id' => $brandChannelReference->external_brand_id,
                    'name' => $brand->name,
                    'code' => $brand->code,
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'existing_id' => $brandChannelReference->external_brand_id,
                    'name' => $brand->name,
                    'code' => $brand->code,
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating brand : webhook url not found', [
                'updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
                'brand id: ' . $brand->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating brand details in eCommerce', [
            'Completion time for updating brand details' => Carbon::now()->format('Y-m-d H:i:s'),
            'brand id: ' . $brand->getKey(),
        ]);
    }
}
