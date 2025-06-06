<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus\Listeners;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\DynamicMenus\Events\DynamicMenuCreateOrUpdateEvent;
use App\Domains\DynamicMenus\Services\DynamicMenuECommerceService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DynamicMenuCreateOrUpdateListener
{
    public function handle(DynamicMenuCreateOrUpdateEvent $dynamicMenuCreateEvent): void
    {
        $dynamicMenu = $dynamicMenuCreateEvent->dynamicMenu;

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::DYNAMIC_MENU_CREATE->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook Dynamic Menu create started', [
            'start time of the webhook call for the Dynamic Menu create' => Carbon::now()->format('Y-m-d H:i:s'),
            'Dynamic Menu id: ' . $dynamicMenu->getKey(),
        ]);

        try {
            $dynamicMenuECommerceService = resolve(DynamicMenuECommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $dynamicMenuECommerceService->createOrUpdateDynamicMenu($dynamicMenu, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook Dynamic Menu create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook Dynamic Menu create ended', [
            'end time of the webhook call for the Dynamic Menu create' => Carbon::now()->format('Y-m-d H:i:s'),
            'Dynamic Menu id: ' . $dynamicMenu->getKey(),
        ]);
    }
}
