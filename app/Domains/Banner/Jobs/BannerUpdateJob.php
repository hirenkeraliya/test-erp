<?php

declare(strict_types=1);

namespace App\Domains\Banner\Jobs;

use App\Domains\Banner\Enums\ActionTypes;
use App\Domains\Common\Enums\Statuses;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BannerUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Banner $banner,
        protected bool $oldStatus
    ) {
    }

    public function handle(): void
    {
        if ($this->oldStatus) {
            return;
        }

        $banner = $this->banner;

        $banner->refresh();

        /** @var Carbon $updatedAt */
        $updatedAt = $banner->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $banner->created_at;

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::BANNER_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $banner->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook banner status update started', [
            'start time of the webhook call for the  banner status update' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                        'id' => $banner->id,
                        'status' => Statuses::getFormattedCaseName(Statuses::INACTIVE->value),
                        'name' => $banner->name,
                        'description' => $banner->description,
                        'custom_url' => $banner->custom_url,
                        'image' => $banner->getDiskBasedFirstMediaUrl('banner'),
                        'action_type' => ActionTypes::getCaseNameByValue((int) $banner->action_type_id),
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook  banner status update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook  banner status update ended', [
            'end time of the webhook call for the  banner status update' => Carbon::now()->format('Y-m-d H:i:s'),
            'banner id: ' . $banner->getKey(),
        ]);
    }
}
