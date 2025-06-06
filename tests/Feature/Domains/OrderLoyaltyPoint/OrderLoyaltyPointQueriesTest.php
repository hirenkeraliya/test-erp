<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderLoyaltyPoint\OrderLoyaltyPointQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;

beforeEach(function (): void {
    $this->orderLoyaltyPointQueries = new OrderLoyaltyPointQueries();
});

test('new sale loyalty point can be added', function (): void {
    $companyId = Company::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $order = Order::factory()->create([
        'location_id' => $location->id,
        'store_manager_id' => null,
        'member_id' => null,
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
    ]);

    $this->orderLoyaltyPointQueries->addNew(100, 10.20, $order->id);

    $this->assertDatabaseHas('order_loyalty_points', [
        'loyalty_points' => 100,
        'amount' => 10.20,
        'order_id' => $order->id,
    ]);
});
