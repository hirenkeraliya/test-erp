<?php

declare(strict_types=1);

namespace App\Domains\Banner\Services;

use App\Domains\Banner\Resources\BannerWebhookResource;
use App\Domains\BannerChannelReference\BannerChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\Banner;
use App\Models\BannerChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BannerService
{
    public function addUpdateDetails(Banner $banner, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the banner in eCommerce.', [
            'Start time for banner creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);

        $bannerChannelReferenceQueries = resolve(BannerChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;
            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'banner' => new BannerWebhookResource($banner),
                ]);

                Log::channel('e_commerce')->info('creating or updating the banner : inside webspert .', [
                    'Start time for banner creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
                    'banner id: ' . $banner->getKey(),
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $bannerChannelReference = $bannerChannelReferenceQueries->getByBannerIdAndSaleChannelId(
                    $banner->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'banner' => $this->preparedRecords($banner, $bannerChannelReference),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Banner in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('banner_id', $responseData) && ! $bannerChannelReference) {
                        $bannerChannelReferenceQueries = resolve(BannerChannelReferenceQueries::class);
                        $bannerChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->id,
                            'banner_id' => $banner->id,
                            'external_banner_id' => $responseData['banner_id'],
                        ]);
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on Banner in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'banner_id' => $banner->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the banner in eCommerce.', [
            'End time for banner creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);
    }

    private function preparedRecords(Banner $banner, ?BannerChannelReference $bannerChannelReference): array
    {
        return [
            'existing_id' => $bannerChannelReference?->external_banner_id,
            'name' => $banner->name,
            'description' => $banner->description,
            'custom_url' => $banner->custom_url,
            'status' => (int) $banner->status,
            'image' => $banner->getDiskBasedFirstMediaUrl('banner'),
        ];
    }
}
