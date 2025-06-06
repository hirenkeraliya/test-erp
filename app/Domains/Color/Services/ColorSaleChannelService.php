<?php

declare(strict_types=1);

namespace App\Domains\Color\Services;

use App\Domains\Color\ColorQueries;
use App\Domains\ColorChannelReference\ColorChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Color;
use App\Models\ColorChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ColorSaleChannelService
{
    public function createColor(Color $color): void
    {
        Log::channel('e_commerce')->info('Start creating the color options in eCommerce.', [
            'Start time for color creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);

        $colorQueries = resolve(ColorQueries::class);
        $color = $colorQueries->getByOnlyId($color->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::COLOR_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $color->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating color : return when sale channels is empty', [
                'Start time for color creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'color id: ' . $color->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addColor($saleChannel, $color);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook color create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the color creation process in eCommerce.', [
            'End time for color creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);
    }

    public function addColor(SaleChannel $saleChannel, Color $color): void
    {
        Log::channel('e_commerce')->info('Start adding colors in eCommerce', [
            'Start time for color addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);

        $colorChannelReferenceQueries = resolve(ColorChannelReferenceQueries::class);
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::COLOR_CREATE->value);

        if ($saleChannelWebhookUrl) {
            $colorChannelReference = $colorChannelReferenceQueries->getByColorIdAndSaleChannelId(
                $color->id,
                $saleChannel->id
            );

            if ($colorChannelReference instanceof ColorChannelReference) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);
                $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

                Log::channel('e_commerce')->info('adding colors : call update color details', [
                    'Start time for color addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'color id: ' . $color->getKey(),
                ]);

                $this->updateColorDetails($saleChannel, $color);

                return;
            }

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $color->id,
                'existing_id' => null,
                'name' => $color->name,
                'code' => $color->code,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Color in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('color_id', $responseData)) {
                    $colorChannelReferenceQueries = resolve(ColorChannelReferenceQueries::class);
                    $colorChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'color_id' => $color->id,
                        'external_color_id' => $responseData['color_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Color in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'color_id' => $color->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('adding colors : webhook url not found', [
                'Start time for color addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'color id: ' . $color->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End color addition in eCommerce', [
            'Completion time for color addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);
    }

    public function updateColor(Color $color): void
    {
        Log::channel('e_commerce')->info('Start updating colors in eCommerce', [
            'Start time for color update' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);

        $colorQueries = resolve(ColorQueries::class);
        $color = $colorQueries->getByOnlyId($color->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::COLOR_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $color->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating colors : return when sale channels is empty', [
                'Start time for color update' => Carbon::now()->format('Y-m-d H:i:s'),
                'color id: ' . $color->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateColorDetails($saleChannel, $color);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook color update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End color update in eCommerce', [
            'Completion time for color update' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);
    }

    private function updateColorDetails(SaleChannel $saleChannel, Color $color): void
    {
        Log::channel('e_commerce')->info('Start updating color details in eCommerce.', [
            'Start time for updating color details' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);

        $colorChannelReferenceQueries = resolve(ColorChannelReferenceQueries::class);

        $colorChannelReference = $colorChannelReferenceQueries->getByColorIdAndSaleChannelId(
            $color->id,
            $saleChannel->id
        );

        if (! $colorChannelReference instanceof ColorChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating color : call add color.', [
                'Start time for updating color details' => Carbon::now()->format('Y-m-d H:i:s'),
                'color id: ' . $color->getKey(),
            ]);

            $this->addColor($saleChannel, $color);

            return;
        }

        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::COLOR_UPDATE->value);

        if ($saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $color->id,
                'existing_id' => $colorChannelReference->external_color_id,
                'name' => $color->name,
                'code' => $color->code,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update Color in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error in update Color in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'color_id' => $color->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } else {
            Log::channel('e_commerce')->info('updating color : web hook url not found.', [
                'Start time for updating color details' => Carbon::now()->format('Y-m-d H:i:s'),
                'color id: ' . $color->getKey(),
            ]);
        }

        Log::channel('e_commerce')->info('End updating color details in eCommerce', [
            'Completion time for updating color details' => Carbon::now()->format('Y-m-d H:i:s'),
            'color id: ' . $color->getKey(),
        ]);
    }
}
