<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\Style\Services\StyleRetailPlanningIntegrationService;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use App\Models\Style;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    $this->integration->integrationWebhookUrls = IntegrationWebhookUrl::factory(2)->make([
        'integration_id' => $this->integration->getKey(),
        'webhook_url_type_id' => IntegrationWebhookUrls::STYLE_CREATE->value,
    ]);

    $this->styleRetailPlanningIntegrationService = new StyleRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for style creation', function (): void {
    Http::fake();

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->styleRetailPlanningIntegrationService->manageStyle($style, IntegrationWebhookUrls::STYLE_CREATE->value);

    Http::assertSentCount(1);
});

test('it does not send a request to the retail planning API when no integrations are found', function (): void {
    Http::fake();

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->mock(IntegrationQueries::class, static function ($mock): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([]));
    });

    $this->styleRetailPlanningIntegrationService->manageStyle($style, IntegrationWebhookUrls::STYLE_CREATE->value);

    Http::assertSentCount(0);
});
