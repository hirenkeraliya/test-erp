<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Jobs;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\DreamPrice;
use App\Models\MemberGroup;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected DreamPrice $dreamPrice,
        protected bool $oldStatus
    ) {
    }

    public function handle(): void
    {
        if ($this->oldStatus) {
            return;
        }

        $dreamPrice = $this->dreamPrice;

        $dreamPrice->refresh();

        /** @var Collection $memberGroups */
        $memberGroups = $dreamPrice->memberGroups;

        /** @var Carbon $updatedAt */
        $updatedAt = $dreamPrice->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $dreamPrice->created_at;

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::DREAM_PRICE_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $dreamPrice->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook dream price status update started', [
            'start time of the webhook call for the  dream price status update' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                        'id' => $dreamPrice->id,
                        'name' => $dreamPrice->name,
                        'start_date' => $dreamPrice->start_date,
                        'end_date' => $dreamPrice->end_date,
                        'status' => Statuses::getFormattedCaseName(Statuses::INACTIVE->value),
                        'member_groups' => $memberGroups->map(function ($memberGroup): array {
                            /** @var MemberGroup $dreamPriceMemberGroup */
                            $dreamPriceMemberGroup = $memberGroup;

                            return [
                                'id' => $dreamPriceMemberGroup->id,
                                'name' => $dreamPriceMemberGroup->name,
                            ];
                        }),
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook dream price status update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook dream price status update ended', [
            'end time of the webhook call for the dream price status update' => Carbon::now()->format('Y-m-d H:i:s'),
            'dream price id: ' . $dreamPrice->getKey(),
        ]);
    }
}
