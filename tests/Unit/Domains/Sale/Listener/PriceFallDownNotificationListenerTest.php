<?php

declare(strict_types=1);

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Events\SaleCreatedEvent;
use App\Domains\Sale\Listeners\PriceFallDownNotificationListener;
use App\Domains\Sale\SaleQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreManager;

test('Price Fall Down Notification Listener Listen as expected', function (): void {
    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
        'username' => 'store_manager_username1',
        'password' => '123456',
        'passcode' => '123456',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
        'can_manage_wholesale' => false,
        'store_ids' => [1],
        'brand_ids' => [1],
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
        'is_locked' => false,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $location->storeManagers = collect([$storeManager]);
    $counter->location = $location;
    $counterUpdate->counter = $counter;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'has_mismatch' => false,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_tax_amount' => 2.50,
        'price_paid_per_unit' => 10.50,
        'total_price_paid' => 100,
        'quantity' => 10,
        'original_price_per_unit' => 10,
    ]);

    $sale->saleItems = collect([$saleItem]);
    $sale->counterUpdate = $counterUpdate;

    $priceFallDownNotificationListener = new PriceFallDownNotificationListener();
    $saleCreatedEvent = new SaleCreatedEvent($sale);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadSaleItemAndOtherRelation')
            ->with($sale)
            ->once()
            ->andReturn($sale);
    });

    $priceFallDownNotificationListener->handle($saleCreatedEvent);
})->skip();
