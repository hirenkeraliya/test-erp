<?php

declare(strict_types=1);

namespace App\Domains\IntegrationWebhookUrl;

use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;

class IntegrationWebhookUrlQueries
{
    public function addNew(array $webhookData): IntegrationWebhookUrl
    {
        return IntegrationWebhookUrl::create($webhookData);
    }

    public function deleteIntegrationWebhookUrl(Integration $integration): void
    {
        $integration->integrationWebhookUrls()->delete();
    }

    public function getBasicColumns(): array
    {
        return ['id', 'integration_id', 'webhook_url_type_id', 'url'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }
}
