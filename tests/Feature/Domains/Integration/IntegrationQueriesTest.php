<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\DataObjects\IntegrationData;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->integrationQueries = new IntegrationQueries();
});

test('An integration can be added', function (): void {
    $integration = [
        'name' => 'Integration ABC',
        'company_id' => $this->companyA->id,
        'url' => 'https://example.com',
        'connection_type' => IntegrationConnections::NETSUITE->value,
        'secret' => 'secret123',
        'webhook_urls' => [],
    ];
    $token = $this->integrationQueries->addNew(new IntegrationData(...$integration));
    unset($integration['webhook_urls']);
    $this->assertDatabaseHas('integrations', $integration);
    $this->assertNotEmpty($token);
});

test('integrations list can be fetched', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration ABCD',
    ]);

    $response = $this->integrationQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 10,
    ]);

    expect($response->first())
        ->toHaveKey('id', $integration->id)
        ->toHaveKey('name', $integration->name);
});

test('An integration fetched by Id', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
    ]);
    $response = $this->integrationQueries->getById($integration->id);
    expect($response->toArray())
        ->toHaveKey('name', $integration->name);
});

test('An integration can be updated', function (): void {
    $newIntegration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
    ]);

    $integration = [
        'name' => 'Integration ABC',
        'company_id' => $this->companyA->id,
        'connection_type' => IntegrationConnections::NETSUITE->value,
        'url' => 'https://newexample.com',
        'secret' => 'newsecret123',
        'webhook_urls' => [],
    ];

    $this->integrationQueries->update(new IntegrationData(...$integration), $newIntegration);

    $this->assertDatabaseHas('integrations', [
        'name' => $integration['name'],
        'company_id' => $this->companyA->id,
    ]);
});

test('An integration token can be refreshed', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
    ]);

    $newToken = $this->integrationQueries->refreshToken($integration->id);

    $this->assertNotEmpty($newToken);
});

test('An integration status can be updated', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
        'status' => false,
    ]);

    $this->integrationQueries->updateStatus($integration->id, true);

    $this->assertDatabaseHas('integrations', [
        'id' => $integration->id,
        'status' => '1',
    ]);
});

test('Integrations can be fetched by webhook URL', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    IntegrationWebhookUrl::factory()->create([
        'integration_id' => $integration->id,
        'webhook_url_type_id' => IntegrationWebhookUrls::COMPANY_CREATE->value,
    ]);

    $response = $this->integrationQueries->getIntegrationsByWebhookUrl(
        IntegrationWebhookUrls::COMPANY_CREATE->value,
        IntegrationConnections::RETAIL_PLANNING->value
    );

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first())
        ->toHaveKey('id', $integration->id)
        ->toHaveKey('name', $integration->name);
});

test('it can fetch integrations by connection id and active status', function (): void {
    $integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Integration Test',
        'connection_type' => IntegrationConnections::ONE_ERP->value,
        'status' => true,
    ]);

    $response = $this->integrationQueries->getIntegrationsByConnectionId(IntegrationConnections::ONE_ERP);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first())
        ->toHaveKey('id', $integration->id)
        ->toHaveKey('name', $integration->name);
});
