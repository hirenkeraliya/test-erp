<?php

declare(strict_types=1);

use App\Domains\Inventory\Events\InventoryUpdateEvent;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Listeners\InventoryUpdateListener;
use App\Domains\Location\LocationQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Inventory;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use App\Models\SaleChannelWebhookUrl;
use App\Services\WebspertIntegrationService;
use Illuminate\Support\Facades\Http;

test(
    'Inventory Update Listener Handles Event Gracefully When E-commerce Location Found and Triggers HTTP Request',
    function (): void {
        Http::fake();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'updated_at' => now(),
        ]);

        $saleChannel = SaleChannel::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'default_location_id' => 1,
            'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        ]);

        $saleChannelWebhookUrl = SaleChannelWebhookUrl::factory()->make([
            'id' => 1,
            'sale_channel_id' => $saleChannel->id,
            'webhook_url_type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        ]);

        $saleChannel->saleChannelWebhookUrls = collect([$saleChannelWebhookUrl]);

        $productChannelReference = ProductChannelReference::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'sale_channel_id' => $saleChannel->id,
            'external_product_id' => 1,
        ]);

        $inventoryUpdateListener = new InventoryUpdateListener();
        $inventoryUpdateEvent = new InventoryUpdateEvent($inventory);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('doesStoreExist')
                ->once()
                ->andReturn(true);
        });

        $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
            $mock->shouldReceive('getSaleChannels')
                ->once()
                ->andReturn(collect([$saleChannel]));
        });

        $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($productChannelReference): void {
            $mock->shouldReceive('getProductChannelReferenceByProductId')
                ->once()
                ->andReturn($productChannelReference);
            $mock->shouldReceive('getByProductIdAndSaleChannelIdForEcommerce')
                ->once()
                ->andReturn($productChannelReference);
        });

        $this->mock(WebspertIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('updateExternalProductStock')
                ->once();
        });

        $this->mock(InventoryQueries::class, static function ($mock) use ($inventory): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($inventory);
        });

        $inventoryUpdateListener->handle($inventoryUpdateEvent);
    }
);

test(
    'Inventory Update Listener Handles Event Gracefully When E-commerce Location Found and Does Not Triggers HTTP Request store is not matching',
    function (): void {
        Http::fake();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 2,
        ]);

        $saleChannel = SaleChannel::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'default_location_id' => 1,
            'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        ]);

        $saleChannelWebhookUrl = SaleChannelWebhookUrl::factory()->make([
            'id' => 1,
            'sale_channel_id' => $saleChannel->id,
            'webhook_url_type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        ]);

        $saleChannel->saleChannelWebhookUrls = collect([$saleChannelWebhookUrl]);

        $inventoryUpdateListener = new InventoryUpdateListener();
        $inventoryUpdateEvent = new InventoryUpdateEvent($inventory);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('doesStoreExist')
                ->once()
                ->andReturn(false);
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldNotReceive('getSaleChannels');
        });

        $this->mock(InventoryQueries::class, static function ($mock) use ($inventory): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($inventory);
        });

        $inventoryUpdateListener->handle($inventoryUpdateEvent);
    }
);
