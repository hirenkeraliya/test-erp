<?php

declare(strict_types=1);

use App\Domains\Inventory\Events\InventoryCreateEvent;
use App\Domains\Inventory\Listeners\InventoryCreateListener;
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
    'Inventory Create Listener Handles Event Gracefully When E-commerce Location Found and Triggers HTTP Request',
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
            'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
            'default_location_id' => 1,
        ]);

        $saleChannel->saleChannelWebhookUrls = collect([SaleChannelWebhookUrl::factory()->make([
            'id' => 1,
            'sale_channel_id' => 1,
            'webhook_url_type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        ])]);

        $inventoryCreateListener = new InventoryCreateListener();
        $inventoryCreateEvent = new InventoryCreateEvent($inventory);

        $productChannelReference = ProductChannelReference::factory()->make([
            'product_id' => 1,
            'sale_channel_id' => $saleChannel->id,
            'external_product_id' => 1,
        ]);

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

        $this->mock(WebspertIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('updateExternalProductStock')
                ->once();
        });

        $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($productChannelReference): void {
            $mock->shouldReceive('getProductChannelReferenceByProductId')
                ->once()
                ->andReturn($productChannelReference);
            $mock->shouldReceive('getByProductIdAndSaleChannelIdForEcommerce')
                ->once()
                ->andReturn($productChannelReference);
        });

        $inventoryCreateListener->handle($inventoryCreateEvent);
    }
);

test(
    'Inventory Create Listener Handles Event Gracefully When E-commerce Location Not Found and Does Not Trigger HTTP Request',
    function (): void {
        Http::fake();

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'updated_at' => now(),
        ]);

        SaleChannel::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'default_location_id' => 1,
        ]);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('doesStoreExist')
                ->once()
                ->andReturn(false);
        });

        $inventoryCreateListener = new InventoryCreateListener();
        $inventoryCreateEvent = new InventoryCreateEvent($inventory);

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldNotReceive('getSaleChannels');
        });

        $inventoryCreateListener->handle($inventoryCreateEvent);
        Http::assertNothingSent();
    }
);
