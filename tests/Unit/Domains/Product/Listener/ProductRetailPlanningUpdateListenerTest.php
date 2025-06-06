<?php

declare(strict_types=1);

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\Listeners\ProductRetailPlanningUpdateListener;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductRetailPlanningIntegrationService;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Size;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test(
    'Product Update Listener Handles Event Gracefully When RetailPlanning and E-commerce Location Found and Triggers HTTP Request',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        Http::fake();

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
            'updated_at' => now(),
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $productVariantValue = ProductVariantValue::factory()->make([
            'product_id' => $product->id,
            'attribute_id' => 1,
        ]);

        $product->productVariantValues = collect($productVariantValue);

        $product->categories = collect([]);
        $product->inventory = Inventory::factory()->make([
            'product_id' => 1,
            'location_id' => 1,
        ]);
        $product->brand = Brand::factory()->make();
        $product->color = Color::factory()->make([
            'company_id' => 1,
        ]);
        $product->size = Size::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(ProductRetailPlanningIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('manageProduct')
                ->once();
        });

        $productRetailPlanningUpdateListener = new ProductRetailPlanningUpdateListener();
        $productUpdateEvent = new ProductUpdateEvent($product);
        $productRetailPlanningUpdateListener->handle($productUpdateEvent);
    }
);

test(
    'Product Update Listener Handles Event Gracefully When RetailPlanning and E-commerce Location Found and Triggers HTTP Request when product_variant true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        Http::fake();

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
            'updated_at' => now(),
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $productVariantValue = ProductVariantValue::factory()->make([
            'product_id' => $product->id,
            'attribute_id' => 1,
        ]);

        $product->productVariantValues = collect($productVariantValue);

        $product->categories = collect([]);
        $product->inventory = Inventory::factory()->make([
            'product_id' => 1,
            'location_id' => 1,
        ]);
        $product->brand = Brand::factory()->make();
        $product->color = Color::factory()->make([
            'company_id' => 1,
        ]);
        $product->size = Size::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(ProductRetailPlanningIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('manageProduct')
                ->once();
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getByIdWithProductVariantValues')
                ->once()
                ->andReturn($product);
        });

        $productRetailPlanningUpdateListener = new ProductRetailPlanningUpdateListener();
        $productUpdateEvent = new ProductUpdateEvent($product);
        $productRetailPlanningUpdateListener->handle($productUpdateEvent);
    }
);
