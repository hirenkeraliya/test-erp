<?php

declare(strict_types=1);

use App\Domains\City\Services\CityRetailPlanningIntegrationService;
use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Models\City;
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
        'webhook_url_type_id' => IntegrationWebhookUrls::CITY_CREATE->value,
    ]);

    $this->cityRetailPlanningIntegrationService = new CityRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for city creation', function (): void {
    Http::fake();

    $city = City::factory()->make([
        'id' => 1,
        'name' => 'Test City',
        'state_id' => 1,
        'country_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->cityRetailPlanningIntegrationService->manageCity($city, IntegrationWebhookUrls::CITY_CREATE->value);

    Http::assertSentCount(1);
});
