<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\MasterProduct\Services\MasterProductRetailPlanningIntegrationService;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    $this->integration->integrationWebhookUrls = IntegrationWebhookUrl::factory(2)->make([
        'integration_id' => $this->integration->getKey(),
        'webhook_url_type_id' => IntegrationWebhookUrls::MASTER_PRODUCT_CREATE_OR_UPDATES->value,
    ]);

    $this->masterProductRetailPlanningIntegrationService = new MasterProductRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for master product creation', function (): void {
    Http::fake();

    $masterProduct = MasterProduct::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'brand_id' => 1,
        'vendor_id' => 1,
        'variant_template_id' => 1,
        'department_id' => 1,
        'unit_of_measure_id' => 1,
        'name' => 'Test Product',
        'original_created_at' => now(),
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->masterProductRetailPlanningIntegrationService->manageMasterProduct(
        $masterProduct,
        IntegrationWebhookUrls::MASTER_PRODUCT_CREATE_OR_UPDATES->value
    );

    Http::assertSentCount(1);
});

test('it does not send a request to the retail planning API when no integrations are found', function (): void {
    Http::fake();

    $masterProduct = MasterProduct::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'brand_id' => 1,
        'vendor_id' => 1,
        'variant_template_id' => 1,
        'department_id' => 1,
        'unit_of_measure_id' => 1,
        'name' => 'Test Product',
        'original_created_at' => now(),
    ]);

    $this->mock(IntegrationQueries::class, static function ($mock): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([]));
    });

    $this->masterProductRetailPlanningIntegrationService->manageMasterProduct(
        $masterProduct,
        IntegrationWebhookUrls::MASTER_PRODUCT_CREATE_OR_UPDATES->value
    );

    Http::assertSentCount(0);
});
