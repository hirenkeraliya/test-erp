<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Listeners;

use App\Domains\Attribute\Events\AttributeDeleteEvent;
use App\Domains\Attribute\Services\AttributeRetailPlanningIntegrationService;
use App\Domains\Attribute\Services\AttributeService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Attribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttributeDeleteListener
{
    public function handle(AttributeDeleteEvent $AttributeCreateEvent): void
    {
        $attribute = $AttributeCreateEvent->attribute;
        $attribute->refresh();

        $this->retailPlanningAttributeSync($attribute);

        Log::channel('e_commerce')->info('Start Delete attribute in eCommerce', [
            'Start time for attribute delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::ATTRIBUTE_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $attribute->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('Delete attribute : sale channels is empty', [
                'Start time for attribute delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'attribute id: ' . $attribute->getKey(),
            ]);

            return;
        }

        try {
            $attributeService = resolve(AttributeService::class);
            foreach ($saleChannels as $saleChannel) {
                $attributeService->deleteDetails($attribute, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook attribute delete details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End Delete attribute in eCommerce', [
            'End time for attribute delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);
    }

    public function retailPlanningAttributeSync(Attribute $attribute): void
    {
        /** @var AttributeRetailPlanningIntegrationService $attributeRetailPlanningIntegrationService */
        $attributeRetailPlanningIntegrationService = resolve(AttributeRetailPlanningIntegrationService::class);
        $attributeRetailPlanningIntegrationService->deleteAttribute(
            $attribute,
            IntegrationWebhookUrls::ATTRIBUTE_DELETE->value
        );
    }
}
