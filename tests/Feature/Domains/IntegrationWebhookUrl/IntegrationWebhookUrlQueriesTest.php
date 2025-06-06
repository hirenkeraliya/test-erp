<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\IntegrationWebhookUrl\IntegrationWebhookUrlQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->integration = Integration::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->webhookUrl = IntegrationWebhookUrl::factory()->create([
        'integration_id' => $this->integration->id,
        'url' => 'https://example.com/webhook',
    ]);

    $this->queries = new IntegrationWebhookUrlQueries();
});

test('addNew creates new webhook URL', function (): void {
    $admin = Admin::factory()->create();

    $webhookData = [
        'url' => $url = 'https://newexample.com/webhook',
        'integration_id' => $this->integration->id,
        'webhook_url_type_id' => IntegrationWebhookUrls::COMPANY_CREATE->value,
    ];

    $this->queries->addNew($webhookData, $this->company->id, $admin);

    $this->assertDatabaseHas('integration_webhook_urls', [
        'url' => $url,
        'webhook_url_type_id' => IntegrationWebhookUrls::COMPANY_CREATE->value,
    ]);
});

test('delete removes webhook URL', function (): void {
    $this->queries->deleteIntegrationWebhookUrl($this->integration);

    $this->assertDatabaseMissing('integration_webhook_urls', [
        'id' => $this->webhookUrl->id,
    ]);
});
