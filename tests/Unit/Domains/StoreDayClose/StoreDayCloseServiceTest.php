<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreDayClosePayment\StoreDayClosePaymentQueries;
use App\Models\CloseCounterPayment;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Order;
use App\Models\StoreDayClose;
use App\Models\StoreManager;

test('addStoreDayClose method calls the methods of queries class as expected', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $storeDayClose = StoreDayClose::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
        'closed_by_store_manager_id' => $storeManager->id,
    ]);

    $counterCounterPayment = CloseCounterPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => 1,
    ]);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
    ]);

    $order->orderItems = collect([]);
    $order->payments = collect([]);

    $counterUpdate->payments = collect([$counterCounterPayment]);

    $counterUpdates = collect([$counterUpdate->toArray()]);

    $this->mock(StoreDayClosePaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdates): void {
        $mock->shouldReceive('getByStoreWithPaymentsFilterByDates')
            ->once()
            ->andReturn($counterUpdates);
    });

    $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getByLocationWithPaymentsFilterByDates')
            ->once()
            ->andReturn(collect([$order]));
        $mock->shouldReceive('updateLocationDayCloseId')
            ->once();
    });

    $this->mock(OrderPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('getOrderPaymentWithGivenTimeFrame')
            ->once()
            ->andReturn(collect([]));
    });

    $storeDayCloseQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use ($storeDayClose): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($storeDayClose);
    });

    $storeDayCloseService = resolve(StoreDayCloseService::class);
    $response = $storeDayCloseService->addStoreDayClose(
        $counterUpdateQueries,
        $storeDayCloseQueries,
        $location,
        null,
        $storeManager->id
    );

    expect($response)->toBeInstanceOf(StoreDayClose::class);
});
