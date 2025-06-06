<?php

declare(strict_types=1);

namespace App\Domains\State\Listeners;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\State\Events\StateUpdateEvent;
use App\Domains\State\Services\StateEcommerceService;
use App\Domains\State\Services\StateRetailPlanningIntegrationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class StateUpdateListener
{
    public function handle(StateUpdateEvent $stateUpdateEvent): void
    {
        $state = $stateUpdateEvent->state;

        /** @var StateRetailPlanningIntegrationService $stateRetailPlanningIntegrationService */
        $stateRetailPlanningIntegrationService = resolve(StateRetailPlanningIntegrationService::class);
        $stateRetailPlanningIntegrationService->manageState($state, IntegrationWebhookUrls::STATE_UPDATES->value);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::STATE_UPDATES->value];

        $saleChannels = $saleChannelQueries->getEcommerceSaleChannelsByTypeIdAndWebhookUrls(
            $webhookUrls,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook state update started', [
            'start time of the webhook call for the state update' => Carbon::now()->format('Y-m-d H:i:s'),
            'state id: ' . $state->getKey(),
        ]);

        try {
            $stateEcommerceService = resolve(StateEcommerceService::class);
            foreach ($saleChannels as $saleChannel) {
                if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                    $stateEcommerceService->addUpdateDetails($state, $saleChannel);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel webhook state update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook state update ended', [
            'end time of the webhook call for the state update' => Carbon::now()->format('Y-m-d H:i:s'),
            'state id: ' . $state->getKey(),
        ]);
    }
}
