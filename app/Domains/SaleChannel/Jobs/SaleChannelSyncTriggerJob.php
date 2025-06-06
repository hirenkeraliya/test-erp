<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel\Jobs;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SaleChannelSyncTriggerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected SaleChannel $saleChannel,
    ) {
    }

    public function handle(): void
    {
        if ($this->saleChannel->getType()->value !== SaleChannelTypes::ECOMMERCE->value) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook trigger sale channel sync', [
            'start time of the webhook call for trigger sale channel sync' => Carbon::now()->format('Y-m-d H:i:s'),
            'sale channel id: ' . $this->saleChannel->getKey(),
        ]);

        try {
            $webhookUrl = $this->getWebhookUrls($this->saleChannel);

            if (! $webhookUrl) {
                return;
            }

            Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($webhookUrl);
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook trigger sale channel sync failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook trigger sale channel sync ended', [
            'end time of the webhook call for the order status' => Carbon::now()->format('Y-m-d H:i:s'),
            'sale channel id: ' . $this->saleChannel->getKey(),
        ]);
    }

    public function getWebhookUrls(SaleChannel $saleChannel): ?string
    {
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
            'webhook_url_type_id',
            WebhookUrls::TRIGGER_SALE_CHANNEL_SYNC->value
        );

        return $saleChannelWebhookUrl?->url;
    }
}
