<?php

namespace App\Domains\Attribute\Listeners;

use App\Domains\Attribute\Events\AttributeUpdateEvent;
use App\Domains\Attribute\Services\AttributeRetailPlanningIntegrationService;
use App\Domains\Attribute\Services\AttributeService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Attribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttributeUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(AttributeUpdateEvent $attributeUpdateEvent): void
    {
        $attribute = $attributeUpdateEvent->attribute;
        $attribute->refresh();

        $this->retailPlanningAttributeSync($attribute);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::ATTRIBUTE_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $attribute->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('sale channel webhook attribute update started', [
            'start time of the webhook call for the attribute update' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);

        try {
            $attributeService = resolve(AttributeService::class);
            foreach ($saleChannels as $saleChannel) {
                $attributeService->addUpdateDetails($attribute, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('sale channel  webhook attribute update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('sale channel webhook attribute update ended', [
            'end time of the webhook call for attribute update' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);
    }

    public function retailPlanningAttributeSync(Attribute $attribute): void
    {
        /** @var AttributeRetailPlanningIntegrationService $attributeRetailPlanningIntegrationService */
        $attributeRetailPlanningIntegrationService = resolve(AttributeRetailPlanningIntegrationService::class);
        $attributeRetailPlanningIntegrationService->manageAttribute(
            $attribute,
            IntegrationWebhookUrls::ATTRIBUTE_UPDATES->value
        );
    }
}
