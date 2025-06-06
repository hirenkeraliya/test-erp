<?php

namespace App\Domains\MasterProduct\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\MasterProduct\Events\MasterProductUpdateEvent;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class MasterProductUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(MasterProductUpdateEvent $masterProductUpdateEvent): void
    {
        $masterProduct = $masterProductUpdateEvent->masterProduct;

        if (! $masterProduct->canSyncToEcommerce()) {
            return;
        }

        $masterProduct->refresh();

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MASTER_PRODUCT_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $masterProduct->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('master_product')->info('sale channel webhook master product update started', [
            'start time of the webhook call for the master product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'master product id: ' . $masterProduct->getKey(),
        ]);

        try {
            $masterProductService = resolve(MasterProductService::class);
            foreach ($saleChannels as $saleChannel) {
                $masterProductService->addUpdateDetails($masterProduct, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('master_product')->error('sale channel webhook master product update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('master_product')->info('sale channel webhook master product update ended', [
            'end time of the webhook call for master product update' => Carbon::now()->format('Y-m-d H:i:s'),
            'master product id: ' . $masterProduct->getKey(),
        ]);
    }
}
