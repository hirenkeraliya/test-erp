<?php

namespace App\Domains\Promotion\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Promotion\Events\PromotionUpdateEvent;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Services\PromotionEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class PromotionUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(PromotionUpdateEvent $promotionsUpdateEvent): void
    {
        $promotion = $promotionsUpdateEvent->promotion;

        $promotionQueries = resolve(PromotionQueries::class);
        $promotion = $promotionQueries->getPromotionById($promotion->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PROMOTION_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $promotion->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('promotion webhook promotion update started', [
            'start time of the webhook call for the promotion update' => Carbon::now()->format('Y-m-d H:i:s'),
            'promotion id: ' . $promotion->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                        $promotionsEcommerceService = resolve(PromotionEcommerceService::class);
                        $promotionsEcommerceService->addUpdateDetails($promotion, $saleChannel, $url);
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook promotions update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook promotions update ended', [
            'end time of the webhook call for promotions update' => Carbon::now()->format('Y-m-d H:i:s'),
            'promotions id: ' . $promotion->getKey(),
        ]);
    }
}
