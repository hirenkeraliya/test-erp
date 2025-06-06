<?php

declare(strict_types=1);

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Services\ProductRetailPlanningIntegrationService;
use App\Models\Color;
use App\Models\Currency;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Size;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'connection_type' => IntegrationConnections::RETAIL_PLANNING->value,
    ]);

    $this->integration->integrationWebhookUrls = IntegrationWebhookUrl::factory(2)->make([
        'integration_id' => $this->integration->getKey(),
        'webhook_url_type_id' => IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value,
    ]);

    $this->productRetailPlanningIntegrationService = new ProductRetailPlanningIntegrationService();
});

test('it sends a request to the retail planning API for product creation', function (): void {
    Http::fake();
    Config::set('app.product_variant', false);
    $color = Color::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $size = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'Test Product',
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'status' => Statuses::ACTIVE->value,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        'original_created_at' => now(),
    ]);

    $product->color = $color;
    $product->size = $size;

    $currency = Currency::factory()->make([
        'id' => 1,
        'name' => 'Test Product',
        'code' => 'Usd',
        'country_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->mock(CurrencyQueries::class, function ($mock) use ($currency): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn($currency);
    });

    $this->productRetailPlanningIntegrationService->manageProduct(
        $product,
        IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value
    );

    Http::assertSentCount(1);
});

test('it sends a request to the retail planning API for product creation when product variant true', function (): void {
    Http::fake();
    Config::set('app.product_variant', true);

    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'Test Product',
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'status' => Statuses::ACTIVE->value,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        'original_created_at' => now(),
    ]);

    $productVariantValue = ProductVariantValue::factory()->make([
        'product_id' => $product->id,
        'attribute_id' => 1,
    ]);

    $product->productVariantValues = collect($productVariantValue);

    $currency = Currency::factory()->make([
        'id' => 1,
        'name' => 'Test Product',
        'code' => 'Usd',
        'country_id' => 1,
    ]);

    $integration = $this->integration;

    $this->mock(IntegrationQueries::class, static function ($mock) use ($integration): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([$integration]));
    });

    $this->mock(CurrencyQueries::class, function ($mock) use ($currency): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn($currency);
    });

    $this->productRetailPlanningIntegrationService->manageProduct(
        $product,
        IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value
    );

    Http::assertSentCount(1);
});

test('it does not send a request to the retail planning API when no integrations are found', function (): void {
    Http::fake();

    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'Test Product',
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $this->mock(IntegrationQueries::class, static function ($mock): void {
        $mock->shouldReceive('getIntegrationsByWebhookUrl')
            ->once()
            ->andReturn(collect([]));
    });

    $this->productRetailPlanningIntegrationService->manageProduct(
        $product,
        IntegrationWebhookUrls::PRODUCT_CREATE_OR_UPDATES->value
    );

    Http::assertSentCount(0);
});
