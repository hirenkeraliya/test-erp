<?php

namespace App\Domains\ProductCollection\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\ProductCollection\Events\ProductCollectionUpdateEvent;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\ProductCollectionWebhookResource;
use App\Domains\ProductCollection\Services\ProductCollectionEcommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCollectionUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(ProductCollectionUpdateEvent $productCollectionUpdateEvent): void
    {
        $productCollection = $productCollectionUpdateEvent->productCollection;

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollection = $productCollectionQueries->getByIdWithMedia($productCollection->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_COLLECTION_UPDATES->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $productCollection->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook product collection update started', [
            'start time of the webhook call for the product collection update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product collection id: ' . $productCollection->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $ProductCollectionEcommerceService = resolve(ProductCollectionEcommerceService::class);
                    $ProductCollectionEcommerceService->productCollectionImage($productCollection, $saleChannel);
                    continue;
                }

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $url = $saleChannelWebhookUrl->url;

                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($url, [
                        'product_collection' => new ProductCollectionWebhookResource($productCollection),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook product collection update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook product collection update ended', [
            'end time of the webhook call for product collection update' => Carbon::now()->format('Y-m-d H:i:s'),
            'product collection id: ' . $productCollection->getKey(),
        ]);
    }
}
