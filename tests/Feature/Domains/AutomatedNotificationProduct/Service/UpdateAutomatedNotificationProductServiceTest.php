<?php

declare(strict_types=1);

use App\Domains\AutomatedNotificationProduct\Services\UpdateAutomatedNotificationProductService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\AutomatedNotificationProduct;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;

test(
    'updateProduct method call and update Automated notification product and activity items',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $locationId = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
            'status' => Statuses::ACTIVE->value,
            'upc' => '44456465',
        ]);

        $productA = Product::factory()->create([
            'company_id' => $companyId,
            'status' => Statuses::ACTIVE->value,
            'upc' => '787978978',
        ]);

        $inventory = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $product->id,
            'stock' => 0,
        ]);

        $inventory->product = $product;

        $inventoryA = Inventory::factory()->create([
            'location_id' => $locationId,
            'product_id' => $productA->id,
            'stock' => 0,
        ]);

        $inventoryA->product = $productA;

        $automatedNotificationProduct = AutomatedNotificationProduct::factory()->create([
            'product_id' => $product->id,
        ]);

        $updateAutomatedNotificationProductService = resolve(UpdateAutomatedNotificationProductService::class);
        $updateAutomatedNotificationProductService->updateProduct($product->id, $productA->id);

        $this->assertDatabaseHas('automated_notification_products', [
            'id' => $automatedNotificationProduct->id,
            'automated_notification_id' => $automatedNotificationProduct->automated_notification_id,
            'product_id' => $productA->id,
        ]);
    }
);
