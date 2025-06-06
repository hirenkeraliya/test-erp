<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\Season\Services\SeasonRetailPlanningIntegrationService;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use App\Models\Season;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    $this->integration->integrationWebhookUrls = IntegrationWebhookUrl::factory(2)->make([
        'integration_id' => $this->integration->getKey(),
        'webhook_url_type_id' => IntegrationWebhookUrls::SEASON_CREATE->value,
    ]);

    $this->seasonRetailPlanningIntegrationService = new SeasonRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for season creation', function (): void {
    Http::fake();

    $season = Season::factory()->make([
        'id' => 1,
        'name' => 'Test Vendor',
        'company_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->seasonRetailPlanningIntegrationService->manageSeason(
        $season,
        IntegrationWebhookUrls::SEASON_CREATE->value
    );

    Http::assertSentCount(1);
});
