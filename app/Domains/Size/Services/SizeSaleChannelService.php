<?php

declare(strict_types=1);

namespace App\Domains\Size\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\SizeChannelReference\SizeChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\Size;
use App\Models\SizeChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SizeSaleChannelService
{
    public function createSize(Size $size): void
    {
        Log::channel('e_commerce')->info('Start creating the size options in eCommerce.', [
            'Start time for size creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);

        $sizeQueries = resolve(SizeQueries::class);
        $size = $sizeQueries->getByOnlyId($size->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::SIZE_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $size->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating size : sale channels is empty.', [
                'Start time for size creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'size id: ' . $size->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addSize($saleChannel, $size);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook size create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the size creation process in eCommerce.', [
            'End time for size creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);
    }

    public function addSize(SaleChannel $saleChannel, Size $size): void
    {
        Log::channel('e_commerce')->info('Start adding sizes in eCommerce', [
            'Start time for size addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);

        $sizeChannelReferenceQueries = resolve(SizeChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::SIZE_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $sizeChannelReference = $sizeChannelReferenceQueries->getBySizeIdAndSaleChannelId(
                $size->id,
                $saleChannel->id
            );

            if ($sizeChannelReference instanceof SizeChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info('adding sizes : update sale details call', [
                    'Start time for size addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'size id: ' . $size->getKey(),
                ]);

                $this->updateSizeDetails($saleChannel, $size);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $size->id,
                'existing_id' => null,
                'name' => $size->name,
                'code' => $size->code,
                'sort_order' => $size->sort_order,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Add a new Size in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('size_id', $responseData)) {
                    $sizeChannelReferenceQueries = resolve(SizeChannelReferenceQueries::class);
                    $sizeChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'size_id' => $size->id,
                        'external_size_id' => $responseData['size_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error in Add a new Size in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'size_id' => $size->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('adding sizes : webhook url not found', [
                'Start time for size addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'size id: ' . $size->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End size addition in eCommerce', [
            'Completion time for size addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);
    }

    public function updateSize(Size $size): void
    {
        Log::channel('e_commerce')->info('Start updating sizes in eCommerce', [
            'Start time for size update' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);

        $sizeQueries = resolve(SizeQueries::class);
        $size = $sizeQueries->getByOnlyId($size->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::SIZE_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $size->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating sizes : sale channels is empty', [
                'Start time for size update' => Carbon::now()->format('Y-m-d H:i:s'),
                'size id: ' . $size->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateSizeDetails($saleChannel, $size);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook size update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End size update in eCommerce', [
            'Completion time for size update' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);
    }

    private function updateSizeDetails(SaleChannel $saleChannel, Size $size): void
    {
        Log::channel('e_commerce')->info('Start updating size details in eCommerce.', [
            'Start time for updating size details' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);

        $sizeChannelReferenceQueries = resolve(SizeChannelReferenceQueries::class);

        $sizeChannelReference = $sizeChannelReferenceQueries->getBySizeIdAndSaleChannelId(
            $size->id,
            $saleChannel->id
        );

        if (! $sizeChannelReference instanceof SizeChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating size : call add size.', [
                'Start time for updating size details' => Carbon::now()->format('Y-m-d H:i:s'),
                'size id: ' . $size->getKey(),
            ]);

            $this->addSize($saleChannel, $size);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::SIZE_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $size->id,
                'existing_id' => $sizeChannelReference->external_size_id,
                'name' => $size->name,
                'code' => $size->code,
                'sort_order' => $size->sort_order,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Add a new Size in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Add a new Size in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'size_id' => $size->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating size : webhook url not found.', [
                'Start time for updating size details' => Carbon::now()->format('Y-m-d H:i:s'),
                'size id: ' . $size->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating size details in eCommerce', [
            'Completion time for updating size details' => Carbon::now()->format('Y-m-d H:i:s'),
            'size id: ' . $size->getKey(),
        ]);
    }
}
