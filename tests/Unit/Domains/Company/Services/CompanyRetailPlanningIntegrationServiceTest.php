<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Company\Services\CompanyRetailPlanningIntegrationService;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    $this->integration->integrationWebhookUrls = IntegrationWebhookUrl::factory(2)->make([
        'integration_id' => $this->integration->getKey(),
        'webhook_url_type_id' => IntegrationWebhookUrls::COMPANY_CREATE->value,
    ]);

    $this->companyRetailPlanningIntegrationService = new CompanyRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for company creation', function (): void {
    Http::fake();

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->companyRetailPlanningIntegrationService->manageCompany(
        $company,
        IntegrationWebhookUrls::COMPANY_CREATE->value
    );

    Http::assertSentCount(1);
});

test('it does not send a request to the retail planning API when no integrations are found', function (): void {
    Http::fake();

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([]));
    });

    $this->companyRetailPlanningIntegrationService->manageCompany(
        $company,
        IntegrationWebhookUrls::COMPANY_CREATE->value
    );

    Http::assertSentCount(0);
});
