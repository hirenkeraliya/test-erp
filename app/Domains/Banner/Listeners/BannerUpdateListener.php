<?php

declare(strict_types=1);

namespace App\Domains\Banner\Listeners;

use App\Domains\Banner\Events\BannerUpdateEvent;
use App\Domains\Banner\Services\BannerService;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class BannerUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(BannerUpdateEvent $bannerUpdateEvent): void
    {
        $banner = $bannerUpdateEvent->banner;
        $banner->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::BANNER_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $banner->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook banner update started', [
            'start time of the webhook call for the banner update' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);

        try {
            $bannerService = resolve(BannerService::class);
            foreach ($saleChannels as $saleChannel) {
                $bannerService->addUpdateDetails($banner, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook banner update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook banner update ended', [
            'end time of the webhook call for banner update' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);
    }
}
