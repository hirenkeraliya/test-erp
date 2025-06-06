<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Inventory\Events\InventoryCreateEvent;
use App\Domains\Location\LocationQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Services\EcommerceIntegrationService;
use App\Services\WebspertIntegrationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class InventoryCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(InventoryCreateEvent $inventoryCreateEvent): void
    {
        $inventory = $inventoryCreateEvent->inventory;

        Log::channel('e_commerce')->info('sale channel webhook inventory create started', [
            'start time of the webhook call for the inventory create' => Carbon::now()->format('Y-m-d H:i:s'),
            'inventory id: ' . $inventory->getKey(),
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $webspertIntegrationService = resolve(WebspertIntegrationService::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);

        if (! $locationQueries->doesStoreExist($inventory->location_id)) {
            return;
        }

        $webhookUrls = [WebhookUrls::INVENTORY_CREATE_VARIANCE->value, WebhookUrls::INVENTORY_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannels($webhookUrls, $inventory->location_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        foreach ($saleChannels as $saleChannel) {
            $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;
            $productChannelReference = $productChannelReferenceQueries->getByProductIdAndSaleChannelIdForEcommerce(
                $inventory->product_id,
                $saleChannel->id
            );
            if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value && $productChannelReference) {
                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    try {
                        $inventoryData = [
                            'product_id' => $productChannelReference->external_variant_id,
                            'quantity' => $inventory->stock,
                        ];

                        $ecommerceIntegrationService = resolve(EcommerceIntegrationService::class);
                        $ecommerceIntegrationService->updateProductStockForEcommerce(
                            $inventoryData,
                            $saleChannelWebhookUrl->url,
                            $saleChannel
                        );
                    } catch (Throwable $throwable) {
                        Log::channel('e_commerce')->error('e-commerce webhook inventory create failed', [
                            'Error message' => $throwable->getMessage(),
                            'Error code' => $throwable->getCode(),
                            'File' => $throwable->getFile(),
                            'Line' => $throwable->getLine(),
                            'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                            'Full error' => [$throwable],
                        ]);
                    }
                }

                continue;
            }

            $productChannelReference = $productChannelReferenceQueries->getProductChannelReferenceByProductId(
                $inventory->product_id
            );

            if (null === $productChannelReference) {
                return;
            }

            foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                try {
                    $webspertIntegrationService->updateExternalProductStock(
                        $saleChannel,
                        $inventory,
                        $productChannelReference,
                        $saleChannelWebhookUrl->url
                    );
                } catch (Throwable $throwable) {
                    Log::channel('e_commerce')->error('sale channel webhook inventory update failed', [
                        'Error message' => $throwable->getMessage(),
                        'Error code' => $throwable->getCode(),
                        'File' => $throwable->getFile(),
                        'Line' => $throwable->getLine(),
                        'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                        'Full error' => [$throwable],
                    ]);
                }
            }
        }

        Log::channel('e_commerce')->info('sale channel webhook inventory create ended', [
            'end time of the webhook call for the inventory create' => Carbon::now()->format('Y-m-d H:i:s'),
            'inventory id: ' . $inventory->getKey(),
        ]);
    }
}
